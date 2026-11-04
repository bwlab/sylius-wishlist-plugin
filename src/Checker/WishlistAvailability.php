<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\Checker;

enum WishlistAvailability: string
{
    case Available = 'available';
    case OutOfStock = 'out_of_stock';
    case Unavailable = 'unavailable';

    public function isPurchasable(): bool
    {
        return self::Available === $this;
    }

    /** Translation key, e.g. bwlab_wishlist.ui.availability.out_of_stock */
    public function translationKey(): string
    {
        return 'bwlab_wishlist.ui.availability.' . $this->value;
    }
}
