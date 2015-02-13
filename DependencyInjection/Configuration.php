<?php
namespace Xoeoro\HelperBundle\DependencyInjection;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('xoeoro_helper');

        $rootNode
            ->children()
            	->arrayNode('placeholder')->children()
	            	->arrayNode('public')
	            		->children()
	                        ->scalarNode('path')->defaultValue('uploads/media')->end()
	                    ->end()
	            	->end()
	            	->arrayNode('upload')
	            		->children()
	                        ->scalarNode('path')->defaultValue('%kernel.root_dir%/../web/%xoeoro.placeholder.public.path%')->end()
	                    ->end()
	            	->end()
	            ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}