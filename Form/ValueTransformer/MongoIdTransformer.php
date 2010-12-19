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

namespace Bundle\Mondongo\MondongoBundle\Form\ValueTransformer;

use Symfony\Component\Form\ValueTransformer\BaseValueTransformer;

/**
 * MondongoChoiceForm.
 *
 * @package MondongoBundle
 * @author  Pablo Díez Pascual <pablodip@gmail.com>
 */
class MongoIdTransformer extends BaseValueTransformer
{
    /**
     * Transform a \MongoId to string.
     *
     * @param \MongoId $value A \MongoId.
     *
     * @return string The string value of the \MongoId.
     */
    public function transform($value)
    {
        if (null === $value) {
            return '';
        }

        if (is_array($value)) {
            return array_map(array($this, 'doTransform'), $value);
        }

        return $this->doTransform($value);
    }

    public function doTransform($value)
    {
        if (!$value instanceof \MongoId) {
            throw new \InvalidArgumentException('The value must be an instance of \MongoId.');
        }

        return (string) $value;
    }

    /**
     * Returns a \MongoId from a string.
     *
     * @param string $value         The string.
     *
     * @return \MongoId The \MongoId with the string.
     */
    public function reverseTransform($value, $originalValue)
    {
        if (null === $value || '' === $value) {
            return null;
        }

        if (is_string($value)) {
            return $this->doReverseTransform($value);
        }

        if (is_array($value)) {
            return array_map(array($this, 'doReverseTransform'), $value);
        }

        throw new \InvalidArgumentException('The value must be a string or an array.');
    }

    public function doReverseTransform($value)
    {
        if (!is_string($value) || 24 != strlen($value)) {
            throw new \InvalidArgumentException('The value must be a string of 24 characters.');
        }

        return new \MongoId($value);
    }
}
