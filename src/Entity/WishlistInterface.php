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
    /** Name of the default list (created at first save, migrated from phase 1). The UI may show it translated. */
    public const DEFAULT_NAME = 'My Favorites';

    public const NAME_MAX_LENGTH = 60;

    /** Maximum lists per owner and channel. */
    public const MAX_LISTS = 20;

    public function getName(): string;

    /** Trims the name and updates the canonical (lower-case) key. No validation here: see WishlistNamePolicy. */
    public function setName(string $name): void;

    public function getNameCanonical(): string;

    public function isDefault(): bool;

    public function setDefault(bool $default): void;

    public function getChannel(): ?ChannelInterface;

    public function setChannel(?ChannelInterface $channel): void;

    public function getCustomer(): ?CustomerInterface;

    public function setCustomer(?CustomerInterface $customer): void;

    public function isGuest(): bool;

    /** Owner token: shared by all lists of the same guest (cookie bwlab_wishlist). Irrelevant for customer lists. */
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
