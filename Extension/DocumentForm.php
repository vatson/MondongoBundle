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

        $this->processConfigurableConfigureMethod();
        $this->processConfigurableBundleConfigureMethod();
        $this->processConfigurableBaseConfigureMethod();

        $this->processFormConfigureMethod();
        $this->processFieldGroupConfigureMethod();
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
            'form'                     => '%gen_bundle_namespace%\Form\Document\%class_name%Form',
            'form_field_group'         => '%gen_bundle_namespace%\Form\Document\%class_name%FieldGroup',
            'form_configurable'        => '%gen_bundle_namespace%\Form\Document\%class_name%Configurable',
            'form_configurable_bundle' => '%bundle_namespace%\Form\Document\%class_name%Configurable',
            'form_configurable_base'   => '%gen_bundle_namespace%\Form\Document\Base\%class_name%Configurable',
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
        $definition->setParentClass('\Symfony\Component\Form\Form');
        $definition->setDocComment(<<<EOF
/**
 * Form class for the {$this->class} document.
 */
EOF
        );

        // form field group
        $this->definitions['form_field_group'] = $definition = new Definition($classes['form_field_group']);
        $definition->setParentClass('\Symfony\Component\Form\FieldGroup');
        $definition->setDocComment(<<<EOF
/**
 * Form field group class for the {$this->class} document.
 */
EOF
        );

        // form_configurable
        $this->definitions['form_configurable'] = $definition = new Definition($classes['form_configurable']);
        $definition->setParentClass('\\'.$classes['form_configurable_bundle']);
        $definition->setDocComment(<<<EOF
/**
 * Form configurable class for the {$this->class} document.
 */
EOF
        );

        // form_configurable_bundle
        $this->definitions['form_configurable_bundle'] = $definition = new Definition($classes['form_configurable_bundle']);
        $definition->setParentClass('\\'.$classes['form_configurable_base']);
        $definition->setIsAbstract(true);
        $definition->setDocComment(<<<EOF
/**
 * Form configurable bundle class for the {$this->class} document.
 */
EOF
        );

        // form_configurable_base
        $this->definitions['form_configurable_base'] = $definition = new Definition($classes['form_configurable_base']);
        $definition->setIsAbstract(true);
        $definition->setDocComment(<<<EOF
/**
 * Form configurable base class for the {$this->class} document.
 */
EOF
        );

        /*
         * Outputs.
         */
        $genBundleFormDocumentDir = dirname($this->outputs['document']->getDir()).'/Form/Document';
        $bundleFormDocumentDir    = dirname($this->outputs['document_bundle']->getDir()).'/Form/Document';

        $this->outputs['form'] = new Output($genBundleFormDocumentDir);

        $this->outputs['form_field_group'] = new Output($genBundleFormDocumentDir);

        $this->outputs['form_configurable'] = new Output($genBundleFormDocumentDir);

        $this->outputs['form_configurable_bundle'] = new Output($bundleFormDocumentDir);

        $this->outputs['form_configurable_base'] = new Output($genBundleFormDocumentDir.'/Base', true);
    }

    /**
     * Configurable "configure" method.
     */
    protected function processConfigurableConfigureMethod()
    {
        $method = new Method('public', 'configure', '\Symfony\Component\Form\FieldGroup $fieldGroup', <<<EOF
        parent::configure(\$fieldGroup);
EOF
        );
        $method->setIsStatic(true);
        $method->setDocComment(<<<EOF
    /**
     * Configure the form.
     *
     * @param \Symfony\Component\Form\FieldGroup
     */
EOF
        );

        $this->definitions['form_configurable']->addMethod($method);
    }

    /**
     * Configurable bundle "configure" method.
     */
    protected function processConfigurableBundleConfigureMethod()
    {
        $method = new Method('public', 'configure', '\Symfony\Component\Form\FieldGroup $fieldGroup', <<<EOF
        parent::configure(\$fieldGroup);
EOF
        );
        $method->setIsStatic(true);
        $method->setDocComment(<<<EOF
    /**
     * Configure the form.
     *
     * @param \Symfony\Component\Form\FieldGroup
     */
EOF
        );

        $this->definitions['form_configurable_bundle']->addMethod($method);
    }

    /**
     * Configurable base "configure" method.
     */
    protected function processConfigurableBaseConfigureMethod()
    {
        $code = '';
        foreach ($this->configClass['fields'] as $name => $field) {
            $fieldClass = $this->getFieldClassForType($field['type']);
            $code .= <<<EOF
        \$fieldGroup->add(new \\$fieldClass('$name'));

EOF;
        }

        $method = new Method('public', 'configure', '\Symfony\Component\Form\FieldGroup $fieldGroup', $code);
        $method->setIsStatic(true);
        $method->setDocComment(<<<EOF
    /**
     * Configure the form.
     *
     * @param \Symfony\Component\Form\FieldGroup
     */
EOF
        );

        $this->definitions['form_configurable_base']->addMethod($method);
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

    /**
     * Form "configure" method.
     */
    protected function processFormConfigureMethod()
    {
        $configurableClass = $this->definitions['form_configurable']->getClass();

        $method = new Method('protected', 'configure', '', <<<EOF
        \\$configurableClass::configure(\$this);
EOF
        );
        $method->setDocComment(<<<EOF
    /**
     * {@inheritDoc}
     */
EOF
        );

        $this->definitions['form']->addMethod($method);
    }

    /**
     * FieldGroup "configure" method.
     */
    protected function processFieldGroupConfigureMethod()
    {
        $configurableClass = $this->definitions['form_configurable']->getClass();

        $method = new Method('protected', 'configure', '', <<<EOF
        \\$configurableClass::configure(\$this);
EOF
        );
        $method->setDocComment(<<<EOF
    /**
     * {@inheritDoc}
     */
EOF
        );

        $this->definitions['form_field_group']->addMethod($method);
    }
}
