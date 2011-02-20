<?php

namespace Mondongo\MondongoBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class MondongoMondatorPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (false === $container->hasDefinition('mondongo.mondator')) {
            return;
        }

        $mondatorDefinition = $container->getDefinition('mondongo.mondator');

        // core
        $definition = new Definition('Mondongo\Extension\Core');
        $definition->addArgument(array(
            'metadata_class'  => $container->getParameter('mondongo.metadata_class'),
            'metadata_output' => $container->getParameter('mondongo.metadata_output'),
        ));
        $container->setDefinition('mondongo.extension.core', $definition);

        $mondatorDefinition->addMethodCall('addExtension', array(new Reference('mondongo.extension.core')));

        // bundles
        $definition = new Definition('Mondongo\MondongoBundle\Extension\Bundles');
        $container->setDefinition('mondongo.extension.bundles', $definition);

        $mondatorDefinition->addMethodCall('addExtension', array(new Reference('mondongo.extension.bundles')));

        // custom
        foreach ($container->findTaggedServiceIds('mondongo.mondator.extension') as $id => $attributes) {
            $definition->addMethodCall('addExtension', array(new Reference($id)));
        }
    }
}
