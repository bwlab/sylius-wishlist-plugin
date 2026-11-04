<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\Api\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Bwlab\SyliusWishlistPlugin\Api\Factory\WishlistViewFactory;
use Bwlab\SyliusWishlistPlugin\Api\Resource\ShopWishlistList;
use Bwlab\SyliusWishlistPlugin\Resolver\CurrentWishlistResolverInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/** @implements ProviderInterface<ShopWishlistList> */
final class ShopWishlistListProvider implements ProviderInterface
{
    public function __construct(
        private readonly CurrentWishlistResolverInterface $resolver,
        private readonly WishlistViewFactory $viewFactory,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ShopWishlistList
    {
        $wishlist = $this->resolver->findOwned((int) $uriVariables['id']) ?? throw new NotFoundHttpException('Wishlist not found.');

        return $this->viewFactory->shopList($wishlist, true);
    }
}
