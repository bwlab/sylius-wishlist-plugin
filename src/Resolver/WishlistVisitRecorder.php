<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\Resolver;

use Bwlab\SyliusWishlistPlugin\Entity\WishlistInterface;

/** Pure: records last visit and locale; returns true when something must be saved (max one write per day or on locale change). */
final class WishlistVisitRecorder
{
    public static function record(WishlistInterface $wishlist, \DateTimeImmutable $now, ?string $localeCode): bool
    {
        $changed = false;

        if (null !== $localeCode && $wishlist->getLocaleCode() !== $localeCode) {
            $wishlist->setLocaleCode($localeCode);
            $changed = true;
        }

        $lastSeenAt = $wishlist->getLastSeenAt();
        if (null === $lastSeenAt || $lastSeenAt->format('Y-m-d') !== $now->format('Y-m-d')) {
            $wishlist->setLastSeenAt($now);
            $changed = true;
        }

        return $changed;
    }
}
