<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\Checker;

use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;

interface VariantAvailabilityCheckerInterface
{
    public function check(ProductVariantInterface $variant, ChannelInterface $channel): WishlistAvailability;
}
