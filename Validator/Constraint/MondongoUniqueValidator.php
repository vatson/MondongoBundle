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

namespace Bundle\Mondongo\MondongoBundle\Validator\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * MondongoUniqueValidator.
 *
 * @package MondongoBundle
 * @author  Pablo Díez Pascual <pablodip@gmail.com>
 */
class MondongoUniqueValidator extends ConstraintValidator
{
    /**
     * {@inheritdoc}
     */
    public function isValid($value, Constraint $constraint)
    {
        $query = array();
        foreach ((array) $constraint->fields as $field) {
            $fieldValue = $value->get($field);

            // case sensitive
            if (
                is_string($fieldValue)
                &&
                (
                    false === $constraint->case_sensitive
                    ||
                    (is_array($constraint->case_sensitive) && !in_array($field, $constraint->case_sensitive))
                )
            ) {
                $fieldValue = new \MongoRegex(sprintf('/%s/i', $fieldValue));
            }

            $query[$field] = $fieldValue;
        }

        $result = \Mondongo\Container::get()
            ->getRepository(get_class($value))
            ->getCollection()
            ->findOne($query, array('_id' => 1))
        ;

        if (null === $result) {
            return true;
        }

        $this->setMessage($constraint->message);

        return false;
    }
}
