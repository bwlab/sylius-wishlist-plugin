<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\Api\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Bwlab\SyliusWishlistPlugin\Api\Factory\AdminWishlistFactory;
use Bwlab\SyliusWishlistPlugin\Api\Resource\AdminWishlist;
use Bwlab\SyliusWishlistPlugin\Repository\WishlistRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/** @implements ProviderInterface<AdminWishlist> */
final class AdminWishlistProvider implements ProviderInterface
{
    public function __construct(
        private readonly WishlistRepositoryInterface $wishlistRepository,
        private readonly AdminWishlistFactory $factory,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): AdminWishlist
    {
        $wishlist = $this->wishlistRepository->find($uriVariables['id']);
        if (null === $wishlist) {
            throw new NotFoundHttpException('Wishlist not found.');
        }

        return $this->factory->create($wishlist, true);
    }
}
