<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\Api\Security;

use Bwlab\SyliusWishlistPlugin\Entity\WishlistInterface;
use Bwlab\SyliusWishlistPlugin\Entity\WishlistItemInterface;
use Bwlab\SyliusWishlistPlugin\Resolver\CurrentWishlistResolverInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Single gate for every shop API access to a row: only rows of the CURRENT owner's wishlist are reachable.
 * Someone else's row and a missing row look the same (404), so ids cannot be probed.
 */
final class CurrentWishlistItemFinder
{
    public function __construct(private readonly CurrentWishlistResolverInterface $resolver)
    {
    }

    public function wishlist(): WishlistInterface
    {
        return $this->resolver->find() ?? throw new NotFoundHttpException('Wishlist not found.');
    }

    public function find(int $id): WishlistItemInterface
    {
        foreach ($this->wishlist()->getItems() as $item) {
            if ($item->getId() === $id) {
                return $item;
            }
        }

        throw new NotFoundHttpException('Wishlist item not found.');
    }
}
