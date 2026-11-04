<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\Api\Output;

/** Not an API resource. The rows stay in the wishlist. */
final class AddWishlistToCartResult
{
    /**
     * @param list<array{itemId: int, variantCode: string, quantity: int}> $added
     * @param list<array{itemId: int, variantCode: ?string, reason: string, message: ?string}> $skipped
     *        reason: out_of_stock | unavailable | rejected | not_in_wishlist
     */
    public function __construct(
        public string $orderTokenValue,
        public array $added,
        public array $skipped,
    ) {
    }
}
