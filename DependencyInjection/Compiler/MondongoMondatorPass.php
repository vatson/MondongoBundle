<?php

namespace Bundle\Mondongo\MondongoBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

class MondongoMondatorPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (false === $container->hasDefinition('mondongo.mondator')) {
            return;
        }

        $definition = $container->getDefinition('mondongo.mondator');

        foreach ($container->findTaggedServiceIds('mondongo.mondator.extension') as $id => $attributes) {
            $definition->addMethodCall('addExtension', array(new Reference($id)));
        }
    }
}