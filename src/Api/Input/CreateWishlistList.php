<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\Api\Input;

use Symfony\Component\Validator\Constraints as Assert;

final class CreateWishlistList
{
    // Length, duplicates and the 20 lists limit are checked by the domain (WishlistNamePolicy): one source of rules.
    #[Assert\NotNull]
    public ?string $name = null;
}
