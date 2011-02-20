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

namespace Mondongo\MondongoBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;

/**
 * GenerateCommand.
 *
 * @package MondongoBundle
 * @author  Pablo Díez Pascual <pablodip@gmail.com>
 */
class GenerateCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('mondongo:generate')
            ->setDescription('Generate classes from config classes')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /*$mondongo = $this->container->get('mondongo');
        print_r($mondongo);
        exit();*/

        $output->writeln('processing config classes');

        $modelDir = $this->container->getParameter('kernel.root_dir').'/../src/Model';

        $configClasses = array();

        // application
        if (is_dir($dir = $this->container->getParameter('kernel.root_dir').'/config/mondongo')) {
            $finder = new Finder();
            foreach ($finder->files()->name('*.yml')->followLinks()->in($dir) as $file) {
                foreach ((array) Yaml::load($file) as $class => $configClass) {
                    // class
                    if (0 !== strpos($class, 'Model\\')) {
                        throw new \RuntimeException('The Mondongo documents must been in the "Model\" namespace.');
                    }

                    // config class
                    $configClass['output'] = $modelDir.'/'.str_replace('\\', '/', substr(substr($class, 0, strrpos($class, '\\')), 6));
                    $configClass['bundle_name']      = null;
                    $configClass['bundle_namespace'] = null;
                    $configClass['bundle_dir']       = null;

                    $configClasses[$class] = $configClass;
                }
            }
        }

        // bundles
        $configClassesPending = array();
        foreach ($this->container->get('kernel')->getBundles() as $bundle) {
            $bundleModelNamespace = 'Model\\'.$bundle->getName();

            if (is_dir($dir = $bundle->getPath().'/Resources/config/mondongo')) {
                $finder = new Finder();
                foreach ($finder->files()->name('*.yml')->followLinks()->in($dir) as $file) {
                    foreach ((array) Yaml::load($file) as $class => $configClass) {
                        // class
                        if (0 !== strpos($class, 'Model\\')) {
                            throw new \RuntimeException('The Mondongo documents must been in the "Model\" namespace.');
                        }
                        if (0 !== strpos($class, $bundleModelNamespace)) {
                            unset($configClass['output'], $configClass['bundle_name'], $configClass['bundle_dir']);
                            $configClassesPending[] = array('class' => $class, 'config_class' => $configClass);
                            continue;
                        }

                        // config class
                        $configClass['output'] = $modelDir.'/'.str_replace('\\', '/', substr(substr($class, 0, strrpos($class, '\\')), 6));
                        $configClass['bundle_name']      = $bundle->getName();
                        $configClass['bundle_namespace'] = $bundle->getNamespace();
                        $configClass['bundle_dir']       = $bundle->getPath();

                        $configClasses[$class] = $configClass;
                    }
                }
            }
        }

        // merge bundles
        foreach ($configClassesPending as $pending) {
            if (!isset($configClasses[$pending['class']])) {
                throw new \RuntimeException(sprintf('The class "%s" does not exist.', $pending['class']));
            }

            $configClasses[$pending['class']] = array_merge_recursive($pending['config_class'], $configClasses[$pending['class']]);
        }

        $output->writeln('generating classes');

        $mondator = $this->container->get('mondongo.mondator');
        $mondator->setConfigClasses($configClasses);
        $mondator->process();
    }
}
