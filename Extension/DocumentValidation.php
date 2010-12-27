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
use Mondongo\Mondator\Definition\Method;
use Mondongo\Mondator\Dumper;
use Mondongo\Inflector;

/**
 * DocumentValidation extension.
 *
 * @package MondongoBundle
 * @author  Pablo Díez Pascual <pablodip@gmail.com>
 */
class DocumentValidation extends Extension
{
    /**
     * {@inheritdoc}
     */
    protected function doProcess()
    {
        $validation = array(
            'constraints' => array(),
            'getters'     => array(),
        );

        // constraints
        if (isset($this->configClass['validation'])) {
            $validation['constraints'] = $this->configClass['validation'];
        }

        // getters
        foreach ($this->configClass['fields'] as $name => $field) {
            // base
            $baseFieldValidation = array();
            if (in_array($field['type'], array('reference_one', 'reference_many'))) {
                foreach ($this->configClass['references'] as $referenceName => $reference) {
                    if ($reference['field'] == $name) {
                        break;
                    }
                }
                $baseFieldValidation[] = array('Bundle\Mondongo\MondongoBundle\Validator\Constraint\MondongoChoice' => array(
                    'class'    => $reference['class'],
                    'multiple' => 'many' == $reference['type'],
                ));
            }

            // custom
            $customFieldValidation = array();
            // notnull
            if (isset($field['notnull']) && $field['notnull']) {
                $customFieldValidation[] = array('NotNull' => null);
            }
            // notblank
            if (isset($field['notblank']) && $field['notblank']) {
                $customFieldValidation[] = array('NotBlank' => null);
            }
            // explicit
            if (isset($field['validation']) && $field['validation']) {
                $customFieldValidation = array_merge($customFieldValidation, $field['validation']);
            }

            // merge
            $validation['getters'][Inflector::camelize($name)] = array_merge_recursive($customFieldValidation, $baseFieldValidation);
        }

        $validation = Dumper::exportArray($validation, 12);

        $method = new Method('public', 'loadValidatorMetadata', '\Symfony\Component\Validator\Mapping\ClassMetadata $metadata', <<<EOF
        \$validation = $validation;

        foreach (\Bundle\Mondongo\MondongoBundle\Extension\DocumentValidation::parseNodes(\$validation['constraints']) as \$constraint) {
            \$metadata->addConstraint(\$constraint);
        }

        foreach (\$validation['getters'] as \$getter => \$constraints) {
            foreach (\Bundle\Mondongo\MondongoBundle\Extension\DocumentValidation::parseNodes(\$constraints) as \$constraint) {
                \$metadata->addGetterConstraint(\$getter, \$constraint);
            }
        }

        return true;
EOF
        );
        $method->setIsStatic(true);
        $method->setDocComment(<<<EOF
    /**
     * Map the validation.
     *
     * @param \Symfony\Component\Validator\Mapping\ClassMetadata \$metadata The metadata class.
     */
EOF
        );

        $this->definitions['document_base']->addMethod($method);
    }

    /*
     * Code from Symfony\Component\Validator\Mapping\Loader\YamlFileLoader
     */
    static public function parseNodes(array $nodes)
    {
        $values = array();

        foreach ($nodes as $name => $childNodes) {
            if (is_numeric($name) && is_array($childNodes) && count($childNodes) == 1) {
                $options = current($childNodes);

                if (is_array($options)) {
                    $options = static::parseNodes($options);
                }

                $values[] = static::newConstraint(key($childNodes), $options);
            } else {
                if (is_array($childNodes)) {
                    $childNodes = static::parseNodes($childNodes);
                }

                $values[$name] = $childNodes;
            }
        }

        return $values;
    }

    /*
     * Code from Symfony\Component\Validator\Mapping\Loader\FileLoader
     */
    static protected function newConstraint($name, $options)
    {
        if (false !== strpos($name, '\\') && class_exists($name)) {
            $className = (string) $name;
        } elseif ('MondongoChoice' == $name) {
            $className = 'Bundle\Mondongo\MondongoBundle\Validator\Constraint\MondongoChoice';
        } elseif ('MondongoUnique' == $name) {
            $className = 'Bundle\Mondongo\MondongoBundle\Validator\Constraint\MondongoUnique';
        } else {
            $className = 'Symfony\\Component\\Validator\\Constraints\\'.$name;
        }

        return new $className($options);
    }
}
