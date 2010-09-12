<?php

namespace Bundle\MondongoBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

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
 * MondongoBuneld is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with MondongoBundle. If not, see <http://www.gnu.org/licenses/>.
 */

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
    public function configLoad($config, ContainerBuilder $container)
    {
        // definition
        $loader = new XmlFileLoader($container, __DIR__.'/../Resources/config');
        $loader->load('mondongo.xml');

        $mondongoDefinition = $container->getDefinition('mondongo');

        // Mondongo configurator
        $mondongoDefinition->setConfigurator(array('Bundle\MondongoBundle\DependencyInjection\MondongoExtension', 'mondongoConfigurator'));

        // override defaults parameters
        foreach (array(
            'class',
        ) as $parameter) {
            if (isset($config[$parameter])) {
                $container->setParameter('mondongo.'.$parameter, $config[$parameter]);
            }
        }

        // connections
        if (isset($config['connections'])) {
            foreach ($config['connections'] as $name => $connection) {
                // Mongo
                $definition = new Definition('Mongo', array(
                    isset($connection['server']) ? $connection['server'] : 'localhost',
                    isset($connection['options']) ? $connection['options'] : array(),
                ));

                $mongoDefinitionName = sprintf('mondongo.%s_connection.mongo', $name);
                $container->setDefinition($mongoDefinitionName, $definition);

                // MongoDB
                if (!isset($connection['database'])) {
                  throw new \InvalidArgumentException(sprintf('The database option of the "%s" connection does not exists and it is mandatory.'));
                }

                $definition = new Definition(null, array($connection['database']));
                $definition->setFactoryMethod('selectDB');
                $definition->setFactoryService($mongoDefinitionName);

                $databaseDefinitionName = sprintf('mondongo.%s_connection.database', $name);
                $container->setDefinition($databaseDefinitionName, $definition);

                // MondongoConnection
                $class = isset($connection['class']) ? $connection['class'] : 'MondongoConnection';

                $definition = new Definition($class, array(
                    new Reference($databaseDefinitionName),
                ));

                $connectionDefinitionName = sprintf('mondongo.%s_connection', $name);
                $container->setDefinition($connectionDefinitionName, $definition);

                // Mondongo
                $mondongoDefinition->addMethodCall('setConnection', array(
                    $name,
                    new Reference($connectionDefinitionName),
                ));
            }
        }
    }

    static public function mondongoConfigurator($mondongo)
    {
        // MondongoContainer
        \MondongoContainer::setDefault($mondongo);
    }

    /**
     * Returns the base path for the XSD files.
     *
     * @return string The XSD base path
     */
    public function getXsdValidationBasePath()
    {
        return false;
    }

    /**
     * Returns the namespace to be used for this extension (XML namespace).
     *
     * @return string The XML namespace
     */
    public function getNamespace()
    {
        return 'http://www.symfony-project.org/schema/dic/mondongo';
    }

    /**
     * Returns the recommended alias to use in XML.
     *
     * This alias is also the mandatory prefix to use when using YAML.
     *
     * @return string The alias
     */
    public function getAlias()
    {
        return 'mondongo';
    }
}
