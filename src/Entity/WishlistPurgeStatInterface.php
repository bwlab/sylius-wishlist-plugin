<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\Entity;

use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Resource\Model\ResourceInterface;

/** Anonymous counters of purged guest wishlist items, one row per (channel, variant, day). */
interface WishlistPurgeStatInterface extends ResourceInterface
{
    public function getChannel(): ?ChannelInterface;

    public function setChannel(?ChannelInterface $channel): void;

    public function getVariant(): ?ProductVariantInterface;

    public function setVariant(?ProductVariantInterface $variant): void;

    public function getPurgedAt(): ?\DateTimeImmutable;

    public function setPurgedAt(?\DateTimeImmutable $purgedAt): void;

    public function getItemsCount(): int;

    public function setItemsCount(int $itemsCount): void;

    public function getQuantitySum(): int;

    public function setQuantitySum(int $quantitySum): void;
}
