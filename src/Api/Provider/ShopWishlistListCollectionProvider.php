<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\Api\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Bwlab\SyliusWishlistPlugin\Api\Factory\WishlistViewFactory;
use Bwlab\SyliusWishlistPlugin\Api\Resource\ShopWishlistList;
use Bwlab\SyliusWishlistPlugin\Resolver\CurrentWishlistResolverInterface;

/** @implements ProviderInterface<ShopWishlistList> */
final class ShopWishlistListCollectionProvider implements ProviderInterface
{
    public function __construct(
        private readonly CurrentWishlistResolverInterface $resolver,
        private readonly WishlistViewFactory $viewFactory,
    ) {
    }

    /** @return list<ShopWishlistList> default list first; empty when the owner saved nothing yet (never creates) */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        return array_map(fn ($wishlist) => $this->viewFactory->shopList($wishlist, false), $this->resolver->findAll());
    }
}
