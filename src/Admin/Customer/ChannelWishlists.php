<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\Admin\Customer;

use Sylius\Component\Core\Model\ChannelInterface;

/** The lists of a customer in one channel, default list first. */
final class ChannelWishlists
{
    /** @param list<WishlistView> $wishlists */
    public function __construct(
        public readonly ChannelInterface $channel,
        public readonly array $wishlists,
    ) {
    }
}
