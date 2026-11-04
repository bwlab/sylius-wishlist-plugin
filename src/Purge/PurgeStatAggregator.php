<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\Purge;

use Bwlab\SyliusWishlistPlugin\Entity\WishlistInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;

/** Sums, per variant, the anonymous counters of the items of the given wishlists. Pure: no I/O. */
final class PurgeStatAggregator
{
    /**
     * @param iterable<WishlistInterface> $wishlists
     *
     * @return array<int, array{variant: ProductVariantInterface, itemsCount: int, quantitySum: int}> keyed by variant id
     */
    public static function aggregate(iterable $wishlists): array
    {
        $totals = [];
        foreach ($wishlists as $wishlist) {
            foreach ($wishlist->getItems() as $item) {
                $variant = $item->getVariant();
                if (null === $variant) {
                    continue;
                }
                $id = (int) $variant->getId();
                $totals[$id] ??= ['variant' => $variant, 'itemsCount' => 0, 'quantitySum' => 0];
                ++$totals[$id]['itemsCount'];
                $totals[$id]['quantitySum'] += $item->getQuantity();
            }
        }

        return $totals;
    }
}
