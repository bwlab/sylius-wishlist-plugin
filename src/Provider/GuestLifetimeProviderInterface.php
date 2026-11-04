<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\Provider;

use Sylius\Component\Core\Model\ChannelInterface;

interface GuestLifetimeProviderInterface
{
    /** Days a guest wishlist lives after the last visit: channel settings, else bundle default. */
    public function getGuestLifetimeDays(ChannelInterface $channel): int;
}
