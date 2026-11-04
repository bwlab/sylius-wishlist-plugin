<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\Admin\Customer;

use Bwlab\SyliusWishlistPlugin\Entity\WishlistInterface;

/** One list with its rows, newest first. */
final class WishlistView
{
    /** @param list<ItemView> $items */
    public function __construct(
        public readonly WishlistInterface $wishlist,
        public readonly array $items,
    ) {
    }
}
