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

namespace MCNElasticSearch\Options;

use MCNElasticSearch\Service\Document\Writer\Adapter\Immediate;
use Zend\Stdlib\AbstractOptions;

/**
 * Class MetadataOptions
 */
class MetadataOptions extends AbstractOptions
{
    /**
     * Property to use as the document id
     *
     * @var string
     */
    protected $id = 'id';

    /**
     * Type name
     *
     * @var string|null
     */
    protected $type;

    /**
     * Index name
     *
     * @var string|null
     */
    protected $index;

    /**
     * The name of the hydrator to load from the hydrator manager
     *
     * @var string|null
     */
    protected $hydrator;

    /**
     * If the object has a parent object
     *
     * @var array|null
     */
    protected $parent = null;

    /**
     * @var string|null
     */
    protected $routing = null;

    /**
     * The writer to use for pushing data to elastic search
     *
     * @var string
     */
    protected $writer = Immediate::class;

    /**
     * @var array
     */
    protected $mapping = [];
    
    protected $settings = [];

    /**
     * @param string $hydrator
     */
    public function setHydrator($hydrator)
    {
        $this->hydrator = (string) $hydrator;
    }

    /**
     * @return string
     */
    public function getHydrator()
    {
        return $this->hydrator;
    }

    /**
     * @param string $id
     */
    public function setId($id)
    {
        $this->id = (string) $id;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = (string) $type;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $index
     */
    public function setIndex($index)
    {
        $this->index = (string) $index;
    }

    /**
     * @return string
     */
    public function getIndex()
    {
        return $this->index;
    }

    /**
     * @param string $writer
     */
    public function setWriter($writer)
    {
        $this->writer = $writer;
    }

    /**
     * @return string
     */
    public function getWriter()
    {
        return $this->writer;
    }

    /**
     * @param array $mapping
     */
    public function setMapping(array $mapping)
    {
        $this->mapping = $mapping;
    }

    /**
     * @return array
     */
    public function getMapping()
    {
        return $this->mapping;
    }
    
    public function setSettings(array $settings)
    {
        $this->settings = $settings;
    }
    
    public function getSettings()
    {
        return $this->settings;
    }

    /**
     * @return array|null
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * @param array|null $parent
     */
    public function setParent(array $parent)
    {
        if (! isset($parent['accessor']) || !isset($parent['getter'])) {
            throw new Exception\InvalidArgumentException('Missing accessor or getter on the parent association');
        }

        $this->parent = $parent;
    }

    /**
     * @return null|string
     */
    public function getRouting()
    {
        return $this->routing;
    }

    /**
     * @param null|string $routing
     */
    public function setRouting($routing)
    {
        $this->routing = $routing;
    }
}
