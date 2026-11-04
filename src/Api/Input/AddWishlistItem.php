<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\Api\Input;

use Symfony\Component\Validator\Constraints as Assert;

final class AddWishlistItem
{
    #[Assert\NotBlank]
    public string $variantCode = '';

    // Same range as a cart row: 1..sylius.order_item_quantity_modifier.limit (Sylius default 9999).
    #[Assert\NotNull]
    #[Assert\Range(min: 1, max: 9999, notInRangeMessage: 'sylius.cart_item.quantity.not_in_range')]
    public int $quantity = 1;
}
