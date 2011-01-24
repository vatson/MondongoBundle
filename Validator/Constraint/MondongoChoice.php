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

/**
 * MondongoChoice.
 *
 * @package MondongoBundle
 * @author  Pablo Díez Pascual <pablodip@gmail.com>
 */
class MondongoChoice extends Constraint
{
    public $message = 'This value is not valid.';
    public $class;
    public $multiple = false;
    public $query = array();

    /**
     * {@inheritdoc}
     */
    public function requiredOptions()
    {
        return array('class');
    }
    
    /**
      * {@inheritDoc}
      */
     public function targets()
     {
         return self::PROPERTY_CONSTRAINT;
     }
}
