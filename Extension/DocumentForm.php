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

namespace Bundle\MondongoBundle\Extension;

use Mondongo\Mondator\Extension;
use Mondongo\Mondator\Definition\Definition;
use Mondongo\Mondator\Definition\Method;
use Mondongo\Mondator\Output\Output;

/**
 * DocumentForm extension.
 *
 * @package MondongoBundle
 * @author  Pablo Díez Pascual <pablodip@gmail.com>
 */
class DocumentForm extends Extension
{
    /**
     * @inheritdoc
     */
    protected function doProcess()
    {
        $this->processInitDefinitionsAndOutputs();

        $this->processFormConfigureMethod();
    }

    /*
     * Init definitions and outputs.
     */
    protected function processInitDefinitionsAndOutputs()
    {
        /*
         * Definitions.
         */

        $className = substr($this->class, strrpos($this->class, '\\') + 1);
        $genBundleNamespace = substr($this->class, 0, strrpos($this->class, '\\'));
        $genBundleNamespace = substr($genBundleNamespace, 0, strrpos($genBundleNamespace, '\\'));

        $classes = array(
            'form'        => '%gen_bundle_namespace%\Form\Document\%class_name%Form',
            'form_bundle' => '%bundle_namespace%\Form\Document\%class_name%Form',
            'form_base'   => '%gen_bundle_namespace%\Form\Document\Base\%class_name%Form',
        );
        foreach ($classes as &$class) {
            $class = strtr($class, array(
                '%gen_bundle_namespace%' => $genBundleNamespace,
                '%bundle_namespace%'     => substr($this->configClass['bundle_class'], 0, strrpos($this->configClass['bundle_class'], '\\')),
                '%class_name%'           => $className,
            ));
        }

        // form
        $this->definitions['form'] = $definition = new Definition($classes['form']);
        $definition->setParentClass('\\'.$classes['form_bundle']);
        $definition->setDocComment(<<<EOF
/**
 * Form class for the {$this->class} document.
 */
EOF
        );

        // form bundle
        $this->definitions['form_bundle'] = $definition = new Definition($classes['form_bundle']);
        $definition->setParentClass('\\'.$classes['form_base']);
        $definition->setIsAbstract(true);
        $definition->setDocComment(<<<EOF
/**
 * Form bundle class for the {$this->class} document.
 */
EOF
        );

        // form base
        $this->definitions['form_base'] = $definition = new Definition($classes['form_base']);
        $definition->setParentClass('\Symfony\Component\Form\Form');
        $definition->setIsAbstract(true);
        $definition->setDocComment(<<<EOF
/**
 * Form base class for the {$this->class} document.
 */
EOF
        );

        /*
         * Outputs.
         */

        $this->outputs['form'] = new Output(dirname($this->outputs['document']->getDir()).'/Form/Document');

        $this->outputs['form_bundle'] = new Output(dirname($this->outputs['document_bundle']->getDir()).'/Form/Document');

        $this->outputs['form_base'] = new Output($this->outputs['form']->getDir().'/Base', true);
    }

    /**
     * Form "configure"
     */
    protected function processFormConfigureMethod()
    {
        $code = '';
        foreach ($this->configClass['fields'] as $name => $field) {
            $fieldClass = $this->getFieldClassForType($field['type']);
            $code .= <<<EOF
        \$this->add(new \\$fieldClass('$name'));

EOF;
        }

        $method = new Method('protected', 'configure', '', $code);
        $method->setDocComment(<<<EOF
    /**
     * {@inheritDoc}
     */
EOF
        );

        $this->definitions['form_base']->addMethod($method);
    }

    protected function getFieldClassForType($type)
    {
        switch ($type)
        {
            case 'date':
                return 'Symfony\Component\Form\DateTimeField';
            case 'integer':
                return 'Symfony\Component\Form\IntegerField';
            case 'float':
                return 'Symfony\Component\Form\NumberField';
            case 'string':
            default:
                return 'Symfony\Component\Form\TextField';
        }
    }
}
