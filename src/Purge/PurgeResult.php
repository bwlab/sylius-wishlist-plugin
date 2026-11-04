<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\Purge;

final class PurgeResult
{
    /** @param array<string, int> $purgedByChannel wishlists purged (or purgeable, in dry-run), by channel code */
    public function __construct(public readonly array $purgedByChannel, public readonly bool $dryRun)
    {
    }

    public function total(): int
    {
        return array_sum($this->purgedByChannel);
    }
}
