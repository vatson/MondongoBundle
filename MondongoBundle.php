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

namespace Mondongo\MondongoBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Mondongo\Container as MondongoContainer;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Mondongo\MondongoBundle\DependencyInjection\Compiler\MondongoMondatorPass;

/**
 * MondongoBundle.
 *
 * @package MondongoBundle
 * @author  Pablo Díez Pascual <pablodip@gmail.com>
 */
class MondongoBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function boot()
    {
        MondongoContainer::setLoader('default', array($this, 'loadMondongo'));
        MondongoContainer::setDefaultName('default');
    }

    /**
     * {@inheritdoc}
     */
    public function shutdown()
    {
        MondongoContainer::clear();
    }

    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new MondongoMondatorPass());
    }

    /**
     * Load the Mondongo in the \Mondongo\Container.
     */
    public function loadMondongo()
    {
        return $this->container->get('mondongo');
    }

    /*
     * code from php at moechofe dot com (array_merge comment on php.net)
     */
    static public function arrayDeepMerge()
    {
        $numArgs = func_num_args();

        if (0 == $numArgs) {
            return false;
        }

        if (1 == $numArgs) {
            return func_get_arg(0);
        }

        if (2 == $numArgs) {
            $args = func_get_args();
            $args[2] = array();
            if (is_array($args[0]) && is_array($args[1])) {
                foreach (array_unique(array_merge(array_keys($args[0]),array_keys($args[1]))) as $key) {
                    $isKey0 = array_key_exists($key, $args[0]);
                    $isKey1 = array_key_exists($key, $args[1]);

                    if (is_int($key)) {
                        if ($isKey0) {
                            $args[2][] = $args[0][$key];
                        }
                        if ($isKey1) {
                            $args[2][] = $args[1][$key];
                        }
                    } elseif ($isKey0 && $isKey1 && is_array($args[0][$key]) && is_array($args[1][$key])) {
                        $args[2][$key] = static::arrayDeepMerge($args[0][$key], $args[1][$key]);
                    } else if ($isKey0 && $isKey1) {
                        $args[2][$key] = $args[1][$key];
                    } else if (!$isKey1) {
                        $args[2][$key] = $args[0][$key];
                    } else if (!$isKey0) {
                        $args[2][$key] = $args[1][$key];
                    }
                }
                return $args[2];
            } else {
              return $args[1];
            }
        }

        $args = func_get_args();
        $args[1] = static::arrayDeepMerge($args[0], $args[1]);
        array_shift($args);

        return call_user_func_array(array(get_called_class(), 'arrayDeepMerge'), $args);
    }
}
