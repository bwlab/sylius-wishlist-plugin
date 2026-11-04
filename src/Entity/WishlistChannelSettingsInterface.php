<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\Entity;

use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Resource\Model\ResourceInterface;

interface WishlistChannelSettingsInterface extends ResourceInterface
{
    public function getChannel(): ?ChannelInterface;

    public function setChannel(?ChannelInterface $channel): void;

    /** Null means: use the bundle default (bwlab_sylius_wishlist.guest_lifetime_days). */
    public function getGuestLifetimeDays(): ?int;

    public function setGuestLifetimeDays(?int $guestLifetimeDays): void;
}
