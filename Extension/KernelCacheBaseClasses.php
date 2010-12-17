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
 * MondongoBundle is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with MondongoBundle. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Bundle\Mondongo\MondongoBundle\Extension;

use Mondongo\Mondator\Extension;

/**
 * KernelCacheBaseClasses extension.
 *
 * @package MondongoBundle
 * @author  Pablo Díez Pascual <pablodip@gmail.com>
 */
class KernelCacheBaseClasses extends Extension
{
    /**
     * {@inheritdoc}
     */
    protected function setup()
    {
        $this->addRequiredOption('base_classes_dir');
    }

    /**
     * {@inheritdoc}
     */
    protected function doProcess()
    {
        /*
         * Document
         */
        // definition
        $baseClass = 'Base\\'.str_replace('\\', '', $this->definitions['document']->getClass());
        $this->definitions['document_base']->setClass($baseClass);
        $this->definitions['document']->setParentClass('\\'.$baseClass);

        // output
        $this->outputs['document_base']->setDir($this->getOption('base_classes_dir'));

        /*
         * Repository
         */
        if (!$this->configClass['is_embedded']) {
            // definition
            $baseClass = 'Base\\'.str_replace('\\', '', $this->definitions['repository']->getClass());
            $this->definitions['repository_base']->setClass($baseClass);
            $this->definitions['repository']->setParentClass('\\'.$baseClass);

            // output
            $this->outputs['repository_base']->setDir($this->getOption('base_classes_dir'));
        }
    }
}
