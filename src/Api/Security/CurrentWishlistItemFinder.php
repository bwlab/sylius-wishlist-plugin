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

    /** The default list when $listId is omitted (phase 1 behaviour), else that list if it is the owner's. */
    public function wishlist(?int $listId = null): WishlistInterface
    {
        $wishlist = null === $listId ? $this->resolver->find() : $this->resolver->findOwned($listId);

        return $wishlist ?? throw new NotFoundHttpException('Wishlist not found.');
    }

    public function find(int $id, ?int $listId = null): WishlistItemInterface
    {
        foreach ($this->wishlist($listId)->getItems() as $item) {
            if ($item->getId() === $id) {
                return $item;
            }
        }

        throw new NotFoundHttpException('Wishlist item not found.');
    }
}
