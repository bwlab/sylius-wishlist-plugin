<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\Gift;

use Symfony\Component\HttpFoundation\Session\SessionInterface;

/** The friend's session remembers which wishlist rows went into the cart as gifts (row ids only, no personal data). */
final class GiftSession
{
    private const KEY = '_bwlab_wishlist_gifts';

    private const MAX = 50;

    public static function remember(SessionInterface $session, int $itemId): void
    {
        $ids = self::ids($session);
        $ids[] = $itemId;
        $session->set(self::KEY, \array_slice(array_values(array_unique($ids)), -self::MAX));
    }

    /** @return list<int> */
    public static function ids(SessionInterface $session): array
    {
        return array_values(array_map('intval', (array) $session->get(self::KEY, [])));
    }

    /** @param list<int> $itemIds */
    public static function forget(SessionInterface $session, array $itemIds): void
    {
        $session->set(self::KEY, array_values(array_diff(self::ids($session), $itemIds)));
    }
}
