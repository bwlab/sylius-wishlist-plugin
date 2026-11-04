<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\Resolver;

use Bwlab\SyliusWishlistPlugin\Entity\WishlistInterface;

interface CurrentWishlistResolverInterface
{
    /** Request attribute holding the resolved wishlist (read by the cookie listener). */
    public const REQUEST_ATTRIBUTE = '_bwlab_wishlist';

    /** Existing wishlist of the current owner (customer or guest token) in the current channel. Never creates. */
    public function find(): ?WishlistInterface;

    /** Same as find(), or a new not-yet-persisted wishlist: persist it with the repository after adding the first item. */
    public function getOrCreate(): WishlistInterface;
}
