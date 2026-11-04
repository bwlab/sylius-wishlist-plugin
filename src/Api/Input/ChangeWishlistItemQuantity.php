<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\Api\Input;

use Symfony\Component\Validator\Constraints as Assert;

final class ChangeWishlistItemQuantity
{
    // Null (field missing) is a 422, never a silent reset to 1.
    #[Assert\NotNull]
    #[Assert\Range(min: 1, max: 9999, notInRangeMessage: 'sylius.cart_item.quantity.not_in_range')]
    public ?int $quantity = null;
}
