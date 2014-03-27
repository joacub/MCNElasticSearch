<?php
/**
 * Copyright (c) 2011-2014 Antoine Hedgecock.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 *   * Redistributions of source code must retain the above copyright
 *     notice, this list of conditions and the following disclaimer.
 *
 *   * Redistributions in binary form must reproduce the above copyright
 *     notice, this list of conditions and the following disclaimer in
 *     the documentation and/or other materials provided with the
 *     distribution.
 *
 *   * Neither the names of the copyright holders nor the names of the
 *     contributors may be used to endorse or promote products derived
 *     from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
 * FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
 * ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @author      Antoine Hedgecock <antoine@pmg.se>
 *
 * @copyright   2011-2014 Antoine Hedgecock
 * @license     http://www.opensource.org/licenses/bsd-license.php  BSD License
 */

namespace MCNElasticSearch\Controller;

use Zend\Console\ColorInterface;
use Zend\Console\Prompt;
use MCNElasticSearch\Service\MappingServiceInterface;
use Zend\EventManager\Event;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\Console\Adapter\AdapterInterface as Console;
use MCNElasticSearch\Service\DocumentServiceInterface;
use Doctrine\ORM\EntityManager;
use MCNElasticSearch\Service\MetadataServiceInterface;
use Doctrine\ORM\Query;
use DoctrineModule\Stdlib\Hydrator\DoctrineObject;
use Doctrine\ORM\PersistentCollection;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;

/**
 * Class MappingController
 *
 * @method \Zend\Console\Request getRequest
 */
class IndexController extends AbstractActionController
{
    /**
     * @var \MCNElasticSearch\Service\DocumentServiceInterface
     */
    protected $service;
    
    /**
    * @var \MCNElasticSearch\Service\MetadataServiceInterface
    */
    protected $metadataService;

    /**
     * @var \Zend\Console\Adapter\AdapterInterface
     */
    protected $console;
    
    /**
     * 
     * @var EntityManager
     */
    protected $em;

    /**
     * @param \Zend\Console\Adapter\AdapterInterface            $console
     * @param \MCNElasticSearch\Service\MappingServiceInterface $service
     */
    public function __construct(Console $console, MetadataServiceInterface $metadataService, DocumentServiceInterface $service, EntityManager $em)
    {
        $this->console = $console;
        $this->service = $service;
        $this->metadataService = $metadataService;
        $this->em = $em;
    }

    /**
     * Display a simple prompt
     *
     * Simple utility to display the prompt that is then mocked to simplify testing instead of inject a prompt bloating
     * the application.
     *
     * @codeCoverageIgnore
     *
     * @param string $message
     *
     * @return bool
     */
    protected function prompt($message = 'Are you sure you want to delete everything ?')
    {
        return (new Prompt\Confirm($message))->show();
    }

    /**
     * Report the progress of ongoing commands
     *
     * @param Event $event
     *
     * @return void
     */
    public function progress(Event $event)
    {
        /**
         * @var $response array
         * @var $metadata \MCNElasticSearch\Options\MetadataOptions
         */
        $response = $event->getParam('response');
        $metadata = $event->getParam('metadata');

        if (isset($response['acknowledged']) && $response['acknowledged']) {
            $this->console->write('[Success] ', ColorInterface::GREEN);
            $this->console->writeLine($metadata->getType());
        } else {
            $this->console->write('[Error] ', ColorInterface::RED);
            $this->console->writeLine(sprintf('%s: %s', $metadata->getType(), $response['error']));
            $this->console->write(json_encode($event->getParam('mapping'), JSON_PRETTY_PRINT));
            $this->console->writeLine();
        }
    }

    public function reIndexAction()
    {
//         $this->service->getEventManager()->attach('create', [$this, 'progress']);
        
        ignore_user_abort(true);
        set_time_limit(0);
        ini_set("memory_limit", "1000M");
        
        $allMetadata = $this->metadataService->getAllMetadata();
        $hydrator = new DoctrineObject($this->em);
        foreach($allMetadata as $entity => $metadataEs) {
            $repo = $this->em->getRepository($entity);
            
            $metadata = $this->em->getMetadataFactory()->getMetadataFor($entity);
            $metadata instanceof ClassMetadata;
            $associationNames = $metadata->getAssociationNames();
            
            $lastId = 0;
            while (true) {
                $qb = $repo->createQueryBuilder('root');
                $qb->where('root.id > ' . $lastId);
                $qb->setMaxResults(1000);
                
                foreach($associationNames as $k => $assocName) {
                    if($metadata->isSingleValuedAssociation($assocName)) {
                        $qb->leftJoin('root.' . $assocName, $assocName . $k);
                        $qb->addSelect($assocName . $k);
                    }
                }
                
                $objects = $qb->getQuery()->getResult();
                
                if(!count($objects)) {
                    break;
                }
                
                foreach($objects as $object) {
                    
                    foreach($associationNames as $k => $assocName) {
                        if($metadata->isSingleValuedAssociation($assocName)) {
                            $getter = 'get' . ucfirst($assocName);
                            $setter = 'set' . ucfirst($assocName); 
                            $data = $object->$getter();
                            
                            if(is_array($data) || $data instanceof PersistentCollection) {
                                foreach($data as $k => $v) {
                                    $data[$k] = $hydrator->extract($v);
            //                         $data[$k] = array('id' => $v->getId());
                                }
                            } else {
                                if($data) {
                                    $data = $hydrator->extract($data);
            //                         $data = array('id' => $data->getId());
                                }
                            }
                        
                            $object->$setter($data);
                                    }
                    }
                    
                    $this->service->update($object);
                    $lastId = $object->getId();
                    $this->em->clear($object);
                    $this->em->detach($object);
                }
            }
            
            
        }
        
    }
}
