<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\Entity;

use Doctrine\Common\Collections\Collection;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Resource\Model\ResourceInterface;
use Sylius\Resource\Model\TimestampableInterface;

interface WishlistInterface extends ResourceInterface, TimestampableInterface
{
    public function getChannel(): ?ChannelInterface;

    public function setChannel(?ChannelInterface $channel): void;

    public function getCustomer(): ?CustomerInterface;

    public function setCustomer(?CustomerInterface $customer): void;

    public function isGuest(): bool;

    public function getToken(): string;

    public function setToken(string $token): void;

    public function getLocaleCode(): ?string;

    public function setLocaleCode(?string $localeCode): void;

    public function getLastSeenAt(): ?\DateTimeImmutable;

    public function setLastSeenAt(?\DateTimeImmutable $lastSeenAt): void;

    /** @return Collection<int, WishlistItemInterface> */
    public function getItems(): Collection;

    public function getItemForVariant(ProductVariantInterface $variant): ?WishlistItemInterface;

    public function hasItem(WishlistItemInterface $item): bool;

    public function addItem(WishlistItemInterface $item): void;

    public function removeItem(WishlistItemInterface $item): void;

    public function countItems(): int;
}
