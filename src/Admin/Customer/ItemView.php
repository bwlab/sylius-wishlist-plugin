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
        /** Current channel price in minor units (null: no price in the channel). */
        public readonly ?int $currentPrice = null,
    ) {
    }

    /** -1 dropped, 1 rose, 0 same, null when the price at save or the current one is unknown or in another currency. */
    public function priceTrend(?string $currentCurrency): ?int
    {
        $before = $this->item->getPriceAtAdd();
        if (null === $before || null === $this->currentPrice || $this->item->getCurrencyCode() !== $currentCurrency) {
            return null;
        }

        return $this->currentPrice <=> $before;
    }
}
