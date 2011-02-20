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

namespace Mondongo\MondongoBundle\Validator\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * MondongoChoiceValidator.
 *
 * @package MondongoBundle
 * @author  Pablo Díez Pascual <pablodip@gmail.com>
 */
class MondongoChoiceValidator extends ConstraintValidator
{
    /**
     * {@inheritdoc}
     */
    public function isValid($value, Constraint $constraint)
    {
        if (null === $value) {
            return true;
        }

        $isValid = true;

        // multiple
        if ($constraint->multiple) {
            if (!is_array($value)) {
                $isValid = false;
            } else {
                foreach ($value as $v) {
                    if (!$v instanceof \MongoId) {
                        $isValid = false;
                    }
                }

                if ($isValid) {
                    $count = \Mondongo\Container::get()
                        ->getRepository($constraint->class)
                        ->count(array_merge($constraint->query, array('_id' => array('$in' => $value))))
                    ;
                    if (count($value) != $count) {
                        $isValid = false;
                    }
                }
            }
        // unique
        } else {
            if (
                !$value instanceof \MongoId
                ||
                null === \Mondongo\Container::get()->getRepository($constraint->class)->findOne(array_merge($constraint->query, array('_id' => $value)))
            ) {
                $isValid = false;
            }
        }

        if (true === $isValid) {
            return true;
        }

        $this->setMessage($constraint->message);

        return false;
    }
}
