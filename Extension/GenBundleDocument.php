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

namespace Bundle\MondongoBundle\Extension;

use Mondongo\Mondator\Definition\Definition;
use Mondongo\Mondator\Extension;
use Mondongo\Mondator\Output\Output;

/**
 * GenBundleDocument extension.
 *
 * @package MondongoBundle
 * @author  Pablo Díez Pascual <pablodip@gmail.com>
 */
class GenBundleDocument extends Extension
{
    /**
     * @inheritdoc
     */
    protected function setup()
    {
        $this->addRequiredOption('gen_dir');
    }

    /**
     * @inheritdoc
     */
    protected function doProcess()
    {
        foreach (array('bundle_name', 'bundle_dir') as $parameter) {
            if (!isset($this->configClass[$parameter])) {
                throw new \RuntimeException(sprintf('The class "%s" does not have the "%s" parameter.', $this->class, $parameter));
            }
        }

        /*
         * Definitions.
         */
        $classes = array(
            'document'          => 'Gen\%bundle_name%\Document\%class%',
            'document_bundle'   => $this->definitions['document']->getClass(),
            'document_base'     => 'Gen\%bundle_name%\Document\Base\%class%',
            'repository'        => 'Gen\%bundle_name%\Document\%class%Repository',
            'repository_bundle' => $this->definitions['repository']->getClass(),
            'repository_base'   => 'Gen\%bundle_name%\Document\Base\%class%Repository',
        );
        foreach ($classes as &$class) {
            $class = strtr($class, array(
                '%bundle_name%' => $this->configClass['bundle_name'],
                '%class%'       => substr($this->class, strrpos($this->class, '\\') + 1),
            ));
        }

        // document
        $this->definitions['document']->setClass($classes['document']);
        $this->definitions['document']->setParentClass('\\'.$classes['document_bundle']);
        $this->definitions['document']->setDocComment(<<<EOF
/**
 * {$classes['document']} document.
 */
EOF
        );

        $this->definitions['document_bundle'] = new Definition($classes['document_bundle']);
        $this->definitions['document_bundle']->setParentClass('\\'.$classes['document_base']);
        $this->definitions['document_bundle']->setIsAbstract(true);
        $this->definitions['document_bundle']->setDocComment(<<<EOF
/**
 * {$classes['document']} document bundle.
 */
EOF
        );

        $this->definitions['document_base']->setClass($classes['document_base']);
        $this->definitions['document_base']->setDocComment(<<<EOF
/**
 * {$classes['document']} document base.
 */
EOF
        );

        // repository
        $this->definitions['repository']->setClass($classes['repository']);
        $this->definitions['repository']->setParentClass('\\'.$classes['repository_bundle']);
        $this->definitions['repository']->setDocComment(<<<EOF
/**
 * {$classes['document']} document repository
 */
EOF
        );

        $this->definitions['repository_bundle'] = new Definition($classes['repository_bundle']);
        $this->definitions['repository_bundle']->setParentClass('\\'.$classes['repository_base']);
        $this->definitions['repository_bundle']->setDocComment(<<<EOF
/**
 * {$classes['document']} document repository bundle
 */
EOF
        );

        $this->definitions['repository_base']->setClass($classes['repository_base']);
        $this->definitions['repository_base']->setDocComment(<<<EOF
/**
 * {$classes['document']} document repository base
 */
EOF
        );

        /*
         * Outputs.
         */
        $dirs = array(
            'document'        => '%gen_dir%/%bundle_name%/Document',
            'document_bundle' => '%bundle_dir%/Document',
            'document_base'   => '%gen_dir%/%bundle_name%/Document/Base',
        );
        foreach ($dirs as &$dir) {
            $dir = strtr($dir, array(
                '%gen_dir%'     => $this->getOption('gen_dir'),
                '%bundle_dir%'  => $this->configClass['bundle_dir'],
                '%bundle_name%' => $this->configClass['bundle_name'],
            ));
        }

        // document
        $this->outputs['document']->setDir($dirs['document']);
        $this->outputs['document_bundle'] = new Output($dirs['document_bundle']);
        $this->outputs['document_base']->setDir($dirs['document_base']);

        // repository
        $this->outputs['repository']->setDir($dirs['document']);
        $this->outputs['repository_bundle'] = new Output($dirs['document_bundle']);
        $this->outputs['repository_base']->setDir($dirs['document_base']);
    }
}
