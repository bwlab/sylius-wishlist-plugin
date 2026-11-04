<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\Destination;

use Bwlab\SyliusWishlistPlugin\Entity\WishlistInterface;

/** Pure rules for the "which list?" menu next to the save buttons. No I/O. */
final class DestinationMenu
{
    /**
     * @param list<WishlistInterface> $lists all lists of the owner
     *
     * @return list<array{id: int, name: string, selected: bool}> empty with fewer than two lists: no menu is shown
     */
    public static function build(array $lists, ?int $selectedId, string $defaultLabel): array
    {
        if (\count($lists) < 2) {
            return [];
        }

        $options = [];
        foreach ($lists as $list) {
            $options[] = ['id' => (int) $list->getId(), 'name' => self::label($list, $defaultLabel), 'selected' => $list->getId() === $selectedId];
        }

        return $options;
    }

    /** The stored name of the default list is a placeholder: show it translated. */
    public static function label(WishlistInterface $list, string $defaultLabel): string
    {
        return $list->isDefault() && WishlistInterface::DEFAULT_NAME === $list->getName() ? $defaultLabel : $list->getName();
    }

    /** @param iterable<WishlistInterface> $lists */
    public static function totalItems(iterable $lists): int
    {
        $total = 0;
        foreach ($lists as $list) {
            $total += $list->countItems();
        }

        return $total;
    }
}
