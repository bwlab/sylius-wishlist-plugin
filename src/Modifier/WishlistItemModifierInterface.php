<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\Modifier;

use Bwlab\SyliusWishlistPlugin\Entity\WishlistInterface;
use Bwlab\SyliusWishlistPlugin\Entity\WishlistItemInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;

/** In-memory changes only: the caller persists with the wishlist repository (add()). */
interface WishlistItemModifierInterface
{
    /** @return bool true if a new row was added, false if the variant was already in the wishlist */
    public function addVariant(
        WishlistInterface $wishlist,
        ProductVariantInterface $variant,
        int $quantity = 1,
        string $source = WishlistItemInterface::SOURCE_PDP,
    ): bool;

    public function removeVariant(WishlistInterface $wishlist, ProductVariantInterface $variant): void;

    public function changeQuantity(WishlistItemInterface $item, int $quantity): void;

    /**
     * Moves the row to $target (another list of the same owner: the caller checks ownership).
     * If $target already has the variant: the source row is removed, the target row keeps the greater quantity and the oldest addedAt.
     * Otherwise the row itself moves, keeping addedAt, quantity and source.
     *
     * @return WishlistItemInterface the row now in $target
     *
     * @throws \Bwlab\SyliusWishlistPlugin\Exception\WishlistOperationException when $target is already the row's list
     */
    public function moveItem(WishlistItemInterface $item, WishlistInterface $target): WishlistItemInterface;
}
