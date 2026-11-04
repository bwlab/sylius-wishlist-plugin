<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\Api\Input;

use Symfony\Component\Validator\Constraints as Assert;

/** Destination list of a move: id or IRI. The source list is the `list` query parameter (default list when omitted). */
final class MoveWishlistItem
{
    #[Assert\NotNull]
    public int|string|null $targetList = null;
}
