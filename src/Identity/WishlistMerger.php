<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\Identity;

use Bwlab\SyliusWishlistPlugin\Entity\WishlistInterface;
use Bwlab\SyliusWishlistPlugin\Modifier\WishlistItemModifier;
use Sylius\Component\Core\Model\CustomerInterface;

/** Pure merge rules: guest lists into the customer's ones. No I/O. */
final class WishlistMerger
{
    /**
     * Phase 1 rule, one list into another: duplicate variant → one row, greater quantity, oldest addedAt.
     *
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

            // The guest row is dropped (orphan removal).
            WishlistItemModifier::absorb($existing, $item);
        }

        return $customerWishlist;
    }

    /**
     * Phase 2 login rule: guest default → customer default; other guest lists pass to the account;
     * same name (case-insensitive) as an account list → the two lists merge; beyond MAX_LISTS → merged into the default.
     *
     * @param list<WishlistInterface> $guestLists all lists of the guest token in the channel
     * @param list<WishlistInterface> $customerLists all lists of the customer in the channel
     *
     * @return array{save: list<WishlistInterface>, delete: list<WishlistInterface>} lists to persist and guest lists to delete
     */
    public function mergeAll(array $guestLists, array $customerLists, CustomerInterface $customer): array
    {
        $save = [];
        $delete = [];
        $default = null;
        $byName = [];
        foreach ($customerLists as $list) {
            $byName[$list->getNameCanonical()] = $list;
            if ($list->isDefault()) {
                $default = $list;
            }
        }
        $count = \count($customerLists);

        // Default first, so the others can fall back on it.
        usort($guestLists, static fn (WishlistInterface $a, WishlistInterface $b): int => $b->isDefault() <=> $a->isDefault());

        foreach ($guestLists as $guest) {
            if ($guest->isDefault()) {
                $target = $default;
            } elseif (isset($byName[$guest->getNameCanonical()])) {
                $target = $byName[$guest->getNameCanonical()];
            } elseif ($count < WishlistInterface::MAX_LISTS) {
                $target = null;
            } else {
                $target = $default;
            }

            $survivor = $this->merge($guest, $target, $customer);
            if ($survivor !== $guest) {
                $delete[] = $guest;
            } else {
                $byName[$guest->getNameCanonical()] = $guest;
                ++$count;
                $default ??= $guest->isDefault() ? $guest : null;
            }
            if (!\in_array($survivor, $save, true)) {
                $save[] = $survivor;
            }
        }

        return ['save' => $save, 'delete' => $delete];
    }
}
