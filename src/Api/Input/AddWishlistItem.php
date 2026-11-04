<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\Api\Input;

use Symfony\Component\Validator\Constraints as Assert;

final class AddWishlistItem
{
    #[Assert\NotBlank]
    public string $variantCode = '';

    #[Assert\Positive]
    public int $quantity = 1;
}
