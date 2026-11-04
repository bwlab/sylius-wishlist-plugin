<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\Purge;

use Bwlab\SyliusWishlistPlugin\Entity\WishlistInterface;
use Sylius\Component\Core\Model\ChannelInterface;

interface ExpiredGuestWishlistFinderInterface
{
    /**
     * Guest wishlists (no customer) of the channel last seen strictly before $cutoff.
     *
     * @return list<WishlistInterface>
     */
    public function find(ChannelInterface $channel, \DateTimeImmutable $cutoff): array;
}
