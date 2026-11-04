<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\Exception;

/** Forbidden list operation (e.g. deleting the default list). getMessage() is a translation key of the "validators" domain. */
final class WishlistOperationException extends \DomainException
{
    public const DELETE_DEFAULT = 'bwlab_wishlist.list.delete_default';
    public const SAME_LIST = 'bwlab_wishlist.list.move_same_list';
}
