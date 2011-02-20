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

namespace Mondongo\MondongoBundle\Extension;

use Mondongo\Mondator\Extension;
use Mondongo\Mondator\Definition\Definition;
use Mondongo\Mondator\Definition\Method;
use Mondongo\Mondator\Output\Output;
use Mondongo\Inflector;

/**
 * DocumentForm extension.
 *
 * @package MondongoBundle
 * @author  Pablo Díez Pascual <pablodip@gmail.com>
 */
class DocumentForm extends Extension
{
    /**
     * {@inheritdoc}
     */
    protected function doProcess()
    {
        $this->processInitDefinitionsAndOutputs();

        $this->processFormAddFieldsMethods();
        $this->processFormAddDocumentFieldMethod();
        $this->processFormAddDocumentFieldsMethod();
        $this->processFormAddReferencesMethods();
        $this->processFormAddReferenceMethod();
        $this->processFormAddEmbeddedsMethods();
        $this->processFormAddEmbeddedMethod();
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
        $bundleNamespace = substr($this->class, 0, strrpos($this->class, '\\'));
        $bundleNamespace = substr($bundleNamespace, 0, strrpos($bundleNamespace, '\\'));

        $formClass = $bundleNamespace.'\Form\Document\\'.$className.'Form';
        $formBaseClass = 'Base\\'.str_replace('\\', '', $formClass);

        // form
        $this->definitions['form'] = $definition = new Definition($formClass);
        $definition->setParentClass('\\'.$formBaseClass);
        $definition->setDocComment(<<<EOF
/**
 * Form class for the {$this->class} document.
 */
EOF
        );

        $method = new Method('protected', 'configure', '', <<<EOF
        \$this->addDocumentFields(array(
        ));
EOF
        );
        $method->setDocComment(<<<EOF
    /**
     * {@inheritdoc}
     */
EOF
        );
        $definition->addMethod($method);

        // form base
        $this->definitions['form_base'] = $definition = new Definition($formBaseClass);
        $definition->setParentClass('\Mondongo\MondongoBundle\Form\MondongoForm');
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

        // form
        $this->outputs['form'] = new Output($this->outputs['document']->getDir().'/../Form/Document');

        // form_base
        $this->outputs['form_base'] = new Output($this->outputs['document_base']->getDir(), true);
    }

    /*
     * Form add fields methods.
     */
    protected function processFormAddFieldsMethods()
    {
        $code = '';
        foreach ($this->configClass['fields'] as $name => $field) {
            list($fieldClass, $fieldOptions) = $this->getFormFieldForDocumentField($name, $field);
            $fieldOptions = \Mondongo\Mondator\Dumper::exportArray($fieldOptions, 12);

            $method = new Method('public', 'add'.Inflector::camelize($name).'DocumentField', 'array $options = array()', <<<EOF
        \$this->add(new \\$fieldClass('$name', array_merge($fieldOptions, \$options)));
EOF
            );
            $method->setDocComment(<<<EOF
    /**
     * Add the field for the "$name" field.
     */
EOF
            );
            $this->definitions['form_base']->addMethod($method);
        }
    }

    protected function getFormFieldForDocumentField($name, $documentField)
    {
        $class   = null;
        $options = array();

        if (in_array($documentField['type'], array('reference_one', 'reference_many'))) {
            $reference = null;
            foreach ($this->configClass['references'] as $referenceName => $ref) {
                if ($name == $ref['field']) {
                    $reference = $ref;
                    break;
                }
            }
            if (null === $reference) {
                throw new \RuntimeException(sprintf('The reference for the field "%s" does not exists.', $name));
            }

            $class = 'Mondongo\MondongoBundle\Form\MondongoChoiceField';
            $options['class'] = $reference['class'];
            if ('one' == $reference['type']) {
                $options['add_empty'] = true;
            } elseif ('many' == $reference['type']) {
                $options['multiple'] = true;
            }
        }

        if (null === $class) {
            switch ($documentField['type'])
            {
                case 'date':
                    $class = 'Symfony\Component\Form\DateTimeField';
                    break;
                case 'integer':
                    $class = 'Symfony\Component\Form\IntegerField';
                    break;
                case 'float':
                    $class = 'Symfony\Component\Form\NumberField';
                    break;
                case 'string':
                default:
                    $class = 'Symfony\Component\Form\TextField';
            }
        }

        return array($class, $options);
    }

    /*
     * Form "addDocumentField" method.
     */
    protected function processFormAddDocumentFieldMethod()
    {
        $code = '';
        foreach ($this->configClass['fields'] as $name => $field) {
            $fieldMethod = 'add'.Inflector::camelize($name).'DocumentField';

            $code .= <<<EOF
        if ('$name' == \$name) {
            \$this->$fieldMethod(\$options); return;
        }
EOF;
        }
        $code .= <<<EOF

        throw new \InvalidArgumentException(sprintf('The document field "%s" does not exist.', \$name));
EOF;

        $method = new Method('public', 'addDocumentField', '$name, array $options = array()', $code);
        $method->setDocComment(<<<EOF
    /**
     * Add a document field.
     *
     * @param string \$name    The field name.
     * @param array  \$options The options (optional).
     */
EOF
        );
        $this->definitions['form_base']->addMethod($method);
    }

    /*
     * Form "addDocumentFields" method.
     */
    protected function processFormAddDocumentFieldsMethod()
    {
        $method = new Method('public', 'addDocumentFields', 'array $fields', <<<EOF
        foreach (\$fields as \$name => \$options) {
            if (is_numeric(\$name)) {
                \$name = \$options;
                \$options = array();
            }
            \$this->addDocumentField(\$name, \$options);
        }
EOF
        );
        $method->setDocComment(<<<EOF
    /**
     * Add document fields
     *
     * @param array \$fields An array of document fields.
     */
EOF
        );
        $this->definitions['form_base']->addMethod($method);
    }

    /*
     * Form add references methods.
     */
    protected function processFormAddReferencesMethods()
    {
        foreach ($this->configClass['references'] as $name => $reference) {
            $referenceSetter = 'set'.Inflector::camelize($name);
            $referenceGetter = 'get'.Inflector::camelize($name);
            $formClass = $this->getFormClassFromDocumentClass($reference['class']);

            // one
            if ('one' == $reference['type']) {
                $code = <<<EOF
        if (null === \$reference = \$this->getData()->$referenceGetter()) {
            \$reference = new \\{$reference['class']}();
            \$this->getData()->$referenceSetter(\$reference);
        }
        \$form = new \\$formClass('$name', \$reference);
        \$form->setPropertyPath('$name');
        \$this->add(\$form);
EOF;
            // many
            } else {
                $code = <<<EOF
        \$fieldGroup = new \Mondongo\MondongoBundle\Form\MondongoFieldGroup('$name');
        foreach (\$this->getData()->$referenceGetter() as \$key => \$reference) {
            \$form = new \\$formClass(\$key, \$reference);
            \$form->setPropertyPath(\$key);
            \$fieldGroup->add(\$form);
        }
        \$this->add(\$fieldGroup);
EOF;
            }

            $method = new Method('public', 'add'.Inflector::camelize($name).'Reference', '', $code);
            $method->setDocComment(<<<EOF
    /**
     * Add a field of a reference document.
     *
     * @param string \$name The reference name.
     */
EOF
            );
            $this->definitions['form_base']->addMethod($method);
        }
    }

    /*
     * Form "addReference" method.
     */
    protected function processFormAddReferenceMethod()
    {
        $code = '';
        foreach ($this->configClass['references'] as $name => $reference) {
            $addReferenceMethod = 'add'.Inflector::camelize($name).'Reference';
            $code .= <<<EOF
        if ('$name' == \$name) {
            \$this->$addReferenceMethod();
            return;
        }

EOF;
        }
        $code .= <<<EOF

        throw new \InvalidArgumentException(sprintf('The reference "%s" does not exists.', \$name));
EOF;

        $method = new Method('public', 'addReference', '$name', $code);
        $method->setDocComment(<<<EOF
    /**
     * Add a reference by name.
     *
     * @param string \$name The reference name.
     */
EOF
        );
        $this->definitions['form_base']->addMethod($method);
    }

    /*
     * Form add embeddeds methods
     */
    protected function processFormAddEmbeddedsMethods()
    {
        foreach ($this->configClass['embeddeds'] as $name => $embedded) {
            $embeddedGetter = 'get'.Inflector::camelize($name);
            $formClass = $this->getFormClassFromDocumentClass($embedded['class']);

            // one
            if ('one' == $embedded['type']) {
                $code = <<<EOF
        \$form = new \\$formClass('$name', \$this->getData()->$embeddedGetter());
        \$form->setPropertyPath('$name');
        \$this->add(\$form);
EOF;
            // many
            } else {
                $code = <<<EOF
        \$fieldGroup = new \Mondongo\MondongoBundle\Form\MondongoFieldGroup('$name');
        foreach (\$this->getData()->$embeddedGetter() as \$key => \$embedded) {
            \$form = new \\$formClass(\$key, \$embedded);
            \$form->setPropertyPath(\$key);
            \$fieldGroup->add(\$form);
        }
        \$this->add(\$fieldGroup);
EOF;
            }

            $method = new Method('public', 'add'.Inflector::camelize($name).'Embedded', '', $code);
            $method->setDocComment(<<<EOF
    /**
     * Add a field of an embedded document.
     *
     * @param string \$name The embedded name.
     */
EOF
            );
            $this->definitions['form_base']->addMethod($method);
        }
    }

    /*
     * Form "addEmbedded" method.
     */
    protected function processFormAddEmbeddedMethod()
    {
        $code = '';
        foreach ($this->configClass['embeddeds'] as $name => $embedded) {
            $addEmbeddedMethod = 'add'.Inflector::camelize($name).'Embedded';
            $code .= <<<EOF
        if ('$name' == \$name) {
            \$this->$addEmbeddedMethod();
            return;
        }

EOF;
        }
        $code .= <<<EOF

        throw new \InvalidArgumentException(sprintf('The embedded "%s" does not exists.', \$name));
EOF;

        $method = new Method('public', 'addEmbedded', '$name', $code);
        $method->setDocComment(<<<EOF
    /**
     * Add a embedded by name.
     *
     * @param string \$name The embedded name.
     */
EOF
        );
        $this->definitions['form_base']->addMethod($method);
    }

    protected function getFormClassFromDocumentClass($documentClass)
    {
        $className = substr($documentClass, strrpos($documentClass, '\\') + 1);
        $genBundleNamespace = substr($documentClass, 0, strrpos($documentClass, '\\'));
        $genBundleNamespace = substr($genBundleNamespace, 0, strrpos($genBundleNamespace, '\\'));

        return $genBundleNamespace.'\Form\Document\\'.$className.'Form';
    }
}
