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

/**
 * OnlyBaseClasses extension.
 *
 * @package MondongoBundle
 * @author  Pablo Díez Pascual <pablodip@gmail.com>
 */
class OnlyBaseClasses extends Extension
{
    /**
     * @inheritdoc
     */
    protected function doProcess()
    {
        foreach ($this->definitions as $name => $definition) {
            if (strlen($name) < 5 || '_base' != substr($name, -5)) {
                unset($this->definitions[$name], $this->outputs[$name]);
            }
        }
    }
}
