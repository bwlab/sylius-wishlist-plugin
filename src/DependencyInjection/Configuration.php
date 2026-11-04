<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('bwlab_sylius_wishlist');

        $treeBuilder->getRootNode()
            ->children()
                ->integerNode('guest_lifetime_days')->min(1)->defaultValue(90)->end()
                ->scalarNode('cookie_name')->cannotBeEmpty()->defaultValue('bwlab_wishlist')->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
