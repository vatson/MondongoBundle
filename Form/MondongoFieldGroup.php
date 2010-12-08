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

namespace Bundle\MondongoBundle\Form;

use Symfony\Component\Form\FieldGroup;
use Symfony\Component\Form\FieldInterface;

/**
 * MondongoFieldGroup.
 *
 * @package MondongoBundle
 * @author  Pablo Díez Pascual <pablodip@gmail.com>
 */
class MondongoFieldGroup extends FieldGroup
{
    /**
     * @inheritdoc
     */
    public function add(FieldInterface $field)
    {
        if ($this->isBound()) {
            throw new AlreadyBoundException('You cannot add fields after binding a form');
        }

        $this->fields[$field->getKey()] = $field;

        $field->setParent($this);
        $field->setLocale($this->locale);

        if (!$field instanceof MondongoForm) {
            $data = $this->getTransformedData();

            // if the property "data" is NULL, getTransformedData() returns an empty
            // string
            if (!empty($data) && $field->getPropertyPath() !== null) {
                $field->updateFromObject($data);
            }
        }

        return $field;
    }

    /**
     * @inheritdoc
     */
    public function bind($taintedData)
    {
        return parent::bind($taintedData);
    }
}
