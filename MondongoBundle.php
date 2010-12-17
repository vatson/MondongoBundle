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

namespace Bundle\Mondongo\MondongoBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;

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
        \Mondongo\Container::clear();
        \Mondongo\Container::setLoader(array($this, 'loadMondongo'));

        $this->initializeBaseClasses();
        spl_autoload_register(array($this, 'loadBaseClasses'));
    }

    /**
     * {@inheritdoc}
     */
    public function shutdown()
    {
        spl_autoload_unregister(array($this, 'loadBaseClasses'));
    }

    /**
     * Load the Mondongo in the \Mondongo\Container.
     */
    public function loadMondongo()
    {
        return $this->container->get('mondongo');
    }

    /**
     * Returns the base classes dir.
     */
    public function getBaseClassesDir()
    {
        return $this->container->getParameter('kernel.cache_dir').'/mondongo/Base';
    }

    /**
     * Initialize the base classes.
     */
    protected function initializeBaseClasses()
    {
        $reload = false;
        if ($this->container->getParameter('kernel.debug')) {
            $hashFile = $this->getBaseClassesHashFile();
            if (!file_exists($hashFile) || file_get_contents($hashFile) !== $this->getConfigFilesHash()) {
                $reload = true;
            }
        }

        if ($reload || !file_exists($this->getBaseClassesDir())) {
            $this->generateBaseClasses();
        }
    }

    /**
     * Load the base classes.
     */
    public function loadBaseClasses($class)
    {
        if (0 === strpos($class, 'Base\\')) {
            $className = substr($class, 5);
            if (false === strpos($className, '\\')) {
                $file = $this->getBaseClassesDir().'/'.$className.'.php';
                if (file_exists($file)) {
                    require($file);
                }
            }
        }
    }

    /**
     * Generates all classes.
     */
    public function generateAllClasses()
    {
        $this->generateClasses(false);
    }

    /**
     * Generate only the base classes.
     */
    public function generateBaseClasses()
    {
        $this->generateClasses(true);
    }

    protected function generateClasses($onlyBaseClasses = true)
    {
        $configClasses = array();
        foreach ($this->container->get('kernel')->getBundles() as $bundle) {
            $bundleClass     = get_class($bundle);
            $bundleNamespace = substr($bundleClass, 0, strrpos($bundleClass, '\\'));
            $bundleName      = substr($bundleClass, strrpos($bundleClass, '\\') + 1);

            if (is_dir($dir = $bundle->getPath().'/Resources/config/mondongo')) {
                $finder = new Finder();
                foreach ($finder->files()->name('*.yml')->followLinks()->in($dir) as $file) {
                    foreach ((array) Yaml::load($file) as $class => $configClass) {
                        // class
                        if (0 === strpos($class, $bundleNamespace)) {
                            if (
                                0 !== strpos($class, $bundleNamespace.'\\Document')
                                ||
                                strlen($bundleNamespace.'\\Document') !== strrpos($class, '\\')
                            ) {
                                throw new \RuntimeException(sprintf('The class "%s" is not in the Document namespace of the bundle.', $class));
                            }
                        }

                        // outputs && bundle
                        if (0 === strpos($class, $bundleNamespace)) {
                            $configClass['output'] = $bundle->getPath().'/Document';
                        } else {
                            unset($configClass['output']);
                        }

                        // merge
                        if (!isset($configClasses[$class])) {
                            $configClasses[$class] = array();
                        }
                        $configClasses[$class] = static::arrayDeepMerge($configClasses[$class], $configClass);
                    }
                }
            }
        }

        $extensions = array(
            new \Mondongo\Extension\Core(),
            new \Bundle\Mondongo\MondongoBundle\Extension\KernelCacheBaseClasses(array('base_classes_dir' => $this->getBaseClassesDir())),
            new \Bundle\Mondongo\MondongoBundle\Extension\DocumentValidation(),
            new \Bundle\Mondongo\MondongoBundle\Extension\DocumentForm(),
        );
        foreach ($this->container->findTaggedServiceIds('mondongo.extension') as $id => $attributes) {
            $extensions[] = $this->container->get($id);
        }

        if ($onlyBaseClasses) {
            $extensions[] = new \Bundle\Mondongo\MondongoBundle\Extension\OnlyBaseClasses();
        }

        $mondator = new \Mondongo\Mondator\Mondator();
        $mondator->setConfigClasses($configClasses);
        $mondator->setExtensions($extensions);
        $mondator->process();

        $file = $this->getBaseClassesHashFile();
        $tmpFile = tempnam(dirname($file), basename($file));
        if (false === @file_put_contents($tmpFile, $this->getConfigFilesHash()) || !@rename($tmpFile, $file)) {
            throw new \RuntimeException(sprintf('Failed to write cache file "%s".', $file));
        }
        chmod($file, 0644);
    }

    protected function getBaseClassesHashFile()
    {
        return $this->getBaseClassesDir().'/hash';
    }

    protected function getConfigFilesHash()
    {
        $dirs = array();
        foreach ($this->container->get('kernel')->getBundles() as $bundle) {
            if (is_dir($dir = $bundle->getPath().'/Resources/config/mondongo')) {
                $dirs[] = $dir;
            }
        }

        if (!$dirs) {
            return array();
        }

        $finder = new Finder();
        $finder->files()->name('*.yml')->followLinks();

        $files = array();
        foreach ($finder->in($dirs) as $file) {
            $file = (string) $file;
            $files[$file] = filemtime($file);
        }

        return sha1(serialize($files));
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
