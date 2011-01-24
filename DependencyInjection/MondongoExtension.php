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

namespace Bundle\Mondongo\MondongoBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * MondongoExtension.
 *
 * @package MondongoBundle
 * @author  Pablo Díez Pascual <pablodip@gmail.com>
 */
class MondongoExtension extends Extension
{
    /**
     * Loads the Mondongo configuration.
     *
     * @param array            $config    An array of settings.
     * @param ContainerBuilder $container A ContainerBuilder instance.
     *
     * @return void
     */
    public function configLoad(array $configs, ContainerBuilder $container)
    {
        $loader = new XmlFileLoader($container, __DIR__.'/../Resources/config');
        $loader->load('mondongo.xml');
        
        foreach ($configs as $config) {
            $this->doConfigLoad($config, $container);
        }
    }
    
    protected function doConfigLoad($config, $container)
    {
        // override defaults parameters
        foreach (array('class') as $parameter) {
            if (isset($config[$parameter])) {
                $container->setParameter('mondongo.'.$parameter, $config[$parameter]);
            }
        }

        // connections
        $defaultConnection = null;
        if (isset($config['connections'])) {
            foreach ($config['connections'] as $name => $connection) {
                // Mondongo\Connection
                $class = isset($connection['class']) ? $connection['class'] : 'Mondongo\Connection';

                $definition = new Definition($class, array(
                    $connection['server'],
                    $connection['database'],
                    isset($connection['options']) ? $connection['options'] : array(),
                ));

                $connectionDefinitionName = sprintf('mondongo.%s_connection', $name);
                $container->setDefinition($connectionDefinitionName, $definition);

                // ->setConnection
                $container->getDefinition('mondongo')->addMethodCall('setConnection', array(
                    $name,
                    new Reference($connectionDefinitionName),
                ));
                
                // default?
                if (isset($connection['default']) && $connection['default']) {
                    if (null !== $defaultConnection) {
                        throw new \RuntimeException('You can define only one connection like the default connection.');
                    }
                    $defaultConnection = $name;
                    $container->getDefinition('mondongo')->addMethodCall('setDefaultConnectionName', array($name));
                }
            }
        }

        // log
        if (isset($config['log']) && $config['log']) {
            $container->getDefinition('mondongo')->addArgument(array(new Reference('mondongo.logger'), 'logQuery'));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getXsdValidationBasePath()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getNamespace()
    {
        return 'http://www.symfony-project.org/schema/dic/mondongo';
    }

    /**
     * {@inheritdoc}
     */
    public function getAlias()
    {
        return 'mondongo';
    }
}
