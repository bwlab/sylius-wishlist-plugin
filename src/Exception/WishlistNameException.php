<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\Exception;

/** Invalid list name or too many lists. getMessage() is a translation key of the "validators" domain. */
final class WishlistNameException extends \DomainException
{
    public const BLANK = 'bwlab_wishlist.list.name.not_blank';
    public const TOO_LONG = 'bwlab_wishlist.list.name.max_length';
    public const DUPLICATE = 'bwlab_wishlist.list.name.unique';
    public const MAX_LISTS = 'bwlab_wishlist.list.max_lists';

    /** @param array<string, string|int> $parameters translation parameters */
    public function __construct(string $messageKey, public readonly array $parameters = [])
    {
        parent::__construct($messageKey);
    }
}
