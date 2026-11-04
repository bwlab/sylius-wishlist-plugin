<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\Gift;

use Bwlab\SyliusWishlistPlugin\Entity\WishlistItemInterface;

/** Which remembered gift rows an order really contains. Pure: no I/O. */
final class GiftSelector
{
    /**
     * @param iterable<WishlistItemInterface> $candidates rows the friend put in the cart (found by id)
     * @param list<int>                       $orderVariantIds variants of the completed order
     *
     * @return list<WishlistItemInterface> rows of a still-shared list whose variant is in the order
     */
    public static function select(iterable $candidates, array $orderVariantIds): array
    {
        $selected = [];
        foreach ($candidates as $item) {
            $variantId = $item->getVariant()?->getId();
            if (null !== $variantId && $item->getWishlist()?->isShared() && \in_array($variantId, $orderVariantIds, true)) {
                $selected[] = $item;
            }
        }

        return $selected;
    }
}
