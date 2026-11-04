<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\Share;

use Sylius\Component\Core\Model\CustomerInterface;

/** What the public page may say about the owner: the first name and nothing else. */
final class SharedWishlistTitle
{
    private const MAX_FIRST_NAME = 40;

    public static function firstName(?CustomerInterface $customer): ?string
    {
        $name = trim((string) $customer?->getFirstName());

        return '' === $name ? null : mb_substr($name, 0, self::MAX_FIRST_NAME);
    }

    /** "<list> · <first name>" (just the list name when the owner has no first name). */
    public static function compose(string $listName, ?string $firstName): string
    {
        return null === $firstName ? $listName : $listName . ' · ' . $firstName;
    }
}
