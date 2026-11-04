<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\Identity;

use Bwlab\SyliusWishlistPlugin\Entity\WishlistInterface;
use Sylius\Component\Core\Model\CustomerInterface;

/** Pure merge rule: guest wishlist into the customer's one. No I/O. */
final class WishlistMerger
{
    /**
     * @return WishlistInterface the surviving wishlist; when it is not $guest, the caller must delete $guest
     */
    public function merge(WishlistInterface $guest, ?WishlistInterface $customerWishlist, CustomerInterface $customer): WishlistInterface
    {
        if (null === $customerWishlist) {
            $guest->setCustomer($customer);

            return $guest;
        }

        foreach ($guest->getItems()->toArray() as $item) {
            $variant = $item->getVariant();
            $existing = null !== $variant ? $customerWishlist->getItemForVariant($variant) : null;

            $guest->removeItem($item);
            if (null === $existing) {
                $customerWishlist->addItem($item);

                continue;
            }

            // Duplicate variant: one row, greater quantity, oldest addedAt. The guest row is dropped (orphan removal).
            $existing->setQuantity(max($existing->getQuantity(), $item->getQuantity()));
            $existing->setAddedAt(min($existing->getAddedAt(), $item->getAddedAt()));
        }

        return $customerWishlist;
    }
}
