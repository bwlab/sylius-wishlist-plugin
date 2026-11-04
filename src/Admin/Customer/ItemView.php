<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\Admin\Customer;

use Bwlab\SyliusWishlistPlugin\Checker\WishlistAvailability;
use Bwlab\SyliusWishlistPlugin\Entity\WishlistItemInterface;

/** One row of a wishlist as shown in the admin customer page. */
final class ItemView
{
    public function __construct(
        public readonly WishlistItemInterface $item,
        public readonly WishlistAvailability $availability,
    ) {
    }
}
