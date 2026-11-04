<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\Entity;

use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Resource\Model\ResourceInterface;

interface WishlistItemInterface extends ResourceInterface
{
    public const SOURCE_PDP = 'pdp';

    public const SOURCE_PLP = 'plp';

    public const SOURCE_API = 'api';

    public const SOURCES = [self::SOURCE_PDP, self::SOURCE_PLP, self::SOURCE_API];

    public function getWishlist(): ?WishlistInterface;

    public function setWishlist(?WishlistInterface $wishlist): void;

    public function getVariant(): ?ProductVariantInterface;

    public function setVariant(?ProductVariantInterface $variant): void;

    public function getQuantity(): int;

    public function setQuantity(int $quantity): void;

    public function getAddedAt(): \DateTimeImmutable;

    public function setAddedAt(\DateTimeImmutable $addedAt): void;

    public function getSource(): string;

    public function setSource(string $source): void;

    /** Channel price in minor units when the row was saved (null: unknown). */
    public function getPriceAtAdd(): ?int;

    public function getCurrencyCode(): ?string;

    public function setPriceAtAdd(?int $priceAtAdd, ?string $currencyCode): void;
}
