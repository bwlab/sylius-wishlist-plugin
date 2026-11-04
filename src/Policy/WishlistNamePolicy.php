<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\Policy;

use Bwlab\SyliusWishlistPlugin\Entity\Wishlist;
use Bwlab\SyliusWishlistPlugin\Entity\WishlistInterface;
use Bwlab\SyliusWishlistPlugin\Exception\WishlistNameException;

/** Pure rules for list names and list count. No I/O. */
final class WishlistNamePolicy
{
    /**
     * @param iterable<WishlistInterface> $ownerLists all lists of the same owner and channel
     * @param WishlistInterface|null $renamed the list being renamed (excluded from the duplicate check), null when creating
     *
     * @return string the trimmed name
     *
     * @throws WishlistNameException
     */
    public static function check(string $name, iterable $ownerLists, ?WishlistInterface $renamed = null): string
    {
        $name = trim($name);
        if ('' === $name) {
            throw new WishlistNameException(WishlistNameException::BLANK);
        }
        if (mb_strlen($name) > WishlistInterface::NAME_MAX_LENGTH) {
            throw new WishlistNameException(WishlistNameException::TOO_LONG, ['%limit%' => WishlistInterface::NAME_MAX_LENGTH]);
        }

        $canonical = Wishlist::canonicalize($name);
        $count = 0;
        foreach ($ownerLists as $list) {
            ++$count;
            if ($list !== $renamed && $list->getNameCanonical() === $canonical) {
                throw new WishlistNameException(WishlistNameException::DUPLICATE, ['%name%' => $name]);
            }
        }
        if (null === $renamed && $count >= WishlistInterface::MAX_LISTS) {
            throw new WishlistNameException(WishlistNameException::MAX_LISTS, ['%limit%' => WishlistInterface::MAX_LISTS]);
        }

        return $name;
    }
}
