<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\Api\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Bwlab\SyliusWishlistPlugin\Api\Factory\WishlistViewFactory;
use Bwlab\SyliusWishlistPlugin\Api\Input\ListReference;
use Bwlab\SyliusWishlistPlugin\Api\Resource\ShopWishlistItem;
use Bwlab\SyliusWishlistPlugin\Api\Security\CurrentWishlistItemFinder;

/** @implements ProviderInterface<ShopWishlistItem> */
final class ShopWishlistItemProvider implements ProviderInterface
{
    public function __construct(
        private readonly CurrentWishlistItemFinder $finder,
        private readonly WishlistViewFactory $viewFactory,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ShopWishlistItem
    {
        return $this->viewFactory->shopItem($this->finder->find((int) $uriVariables['id'], ListReference::fromRequest($context['request'] ?? null)));
    }
}
