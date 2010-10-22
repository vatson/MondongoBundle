<?php

/*
 * Copyright 2010 Pablo Díez Pascual <pablodip@gmail.com>
 *
 * This file is part of MondongoBundle.
 *
 * MondongoBundle is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * MondongoBuneld is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with MondongoBundle. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Bundle\MondongoBundle\DataCollector;

use Bundle\MondongoBundle\Logger\MondongoLogger;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Yaml\Yaml;

/**
 * MondongoDataCollector.
 *
 * @package MondongoBundle
 * @author  Pablo Díez Pascual <pablodip@gmail.com>
 */
class MondongoDataCollector extends DataCollector
{
    protected $logger;

    /**
     * Constructor.
     *
     * @param Bundle\MondongoBundle\Logger\MondongoLogger|null $logger A Mondongo logger (optional).
     *
     * @return void
     */
    public function __construct(MondongoLogger $logger = null)
    {
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function collect(Request $request, Response $response, \Exception $exception = null)
    {
        if ($this->logger) {
            $this->data['queries'] = $this->logger->getQueries();
        }
    }

    /**
     * Returns the queries.
     *
     * @return array The queries.
     */
    public function getQueries()
    {
        return $this->data['queries'];
    }

    /**
     * Returns the number of queries.
     *
     * @return integer The number of queries.
     */
    public function getNbQueries()
    {
        return count($this->data['queries']);
    }

    /**
     * Returns the queries formatted.
     *
     * @return array The queries formatted.
     */
    public function getFormattedQueries()
    {
        $formattedQueries = array();
        foreach ($this->getQueries() as $query) {
            $formattedQueries[] = Yaml::dump($query);
        }

        return $formattedQueries;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'mondongo';
    }
}
