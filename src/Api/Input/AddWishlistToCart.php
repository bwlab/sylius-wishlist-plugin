<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\Api\Input;

use Symfony\Component\Validator\Constraints as Assert;

/** Either `itemIds` (selected rows) or `all: true` (every row; the not purchasable ones are reported as skipped). */
final class AddWishlistToCart
{
    #[Assert\NotBlank]
    public string $orderTokenValue = '';

    /** @var list<int> */
    #[Assert\All([new Assert\Type('int')])]
    public array $itemIds = [];

    public bool $all = false;

    /** Source list (id or IRI). Omitted = the default list. */
    public int|string|null $list = null;
}
