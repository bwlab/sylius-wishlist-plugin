<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\DependencyInjection;

use Sylius\Bundle\CoreBundle\DependencyInjection\PrependDoctrineMigrationsTrait;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

final class BwlabSyliusWishlistExtension extends Extension implements PrependExtensionInterface
{
    use PrependDoctrineMigrationsTrait;

    public function load(array $configs, ContainerBuilder $container): void
    {
        $config = $this->processConfiguration(new Configuration(), $configs);

        $container->setParameter('bwlab_wishlist.guest_lifetime_days', $config['guest_lifetime_days']);
        $container->setParameter('bwlab_wishlist.cookie_name', $config['cookie_name']);

        // One file per area (domain, shop, identity, admin, api): parallel work without conflicts.
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../../config'));
        $loader->import('services/*.yaml');
    }

    public function prepend(ContainerBuilder $container): void
    {
        // Attribute mapping of src/Entity (key = bundle name, so Doctrine auto_mapping does not register it twice).
        $container->prependExtensionConfig('doctrine', [
            'orm' => [
                'mappings' => [
                    'BwlabSyliusWishlistPlugin' => [
                        'type' => 'attribute',
                        'is_bundle' => false,
                        'dir' => \dirname(__DIR__) . '/Entity',
                        'prefix' => 'Bwlab\SyliusWishlistPlugin\Entity',
                        'alias' => 'BwlabSyliusWishlistPlugin',
                    ],
                ],
            ],
        ]);

        // API Platform resources of the plugin (PHP attributes on DTOs in src/Api/Resource).
        $container->prependExtensionConfig('api_platform', [
            'mapping' => ['paths' => [\dirname(__DIR__) . '/Api/Resource']],
        ]);

        $this->prependDoctrineMigrations($container);
    }

    protected function getMigrationsNamespace(): string
    {
        return 'Bwlab\SyliusWishlistPlugin\Migrations';
    }

    protected function getMigrationsDirectory(): string
    {
        return '@BwlabSyliusWishlistPlugin/src/Migrations';
    }

    protected function getNamespacesOfMigrationsExecutedBefore(): array
    {
        return ['Sylius\Bundle\CoreBundle\Migrations'];
    }
}
