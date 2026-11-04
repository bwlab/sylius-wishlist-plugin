<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\Checker;

use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Inventory\Checker\AvailabilityCheckerInterface;

/** Same rules as the Sylius cart: enabled product + variant, product in channel, stock via the inventory checker. */
final class VariantAvailabilityChecker implements VariantAvailabilityCheckerInterface
{
    public function __construct(private readonly AvailabilityCheckerInterface $availabilityChecker)
    {
    }

    public function check(ProductVariantInterface $variant, ChannelInterface $channel): WishlistAvailability
    {
        $product = $variant->getProduct();

        if (
            !$variant->isEnabled() ||
            null === $product ||
            !$product->isEnabled() ||
            !$product->hasChannel($channel)
        ) {
            return WishlistAvailability::Unavailable;
        }

        if (!$this->availabilityChecker->isStockAvailable($variant)) {
            return WishlistAvailability::OutOfStock;
        }

        return WishlistAvailability::Available;
    }
}
