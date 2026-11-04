<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\Api\Input;

use Symfony\Component\Validator\Constraints as Assert;

final class RenameWishlistList
{
    #[Assert\NotNull]
    public ?string $name = null;
}
