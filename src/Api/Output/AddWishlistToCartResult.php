<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\Api\Output;

use Symfony\Component\Serializer\Attribute\Groups;

/** Not an API resource. The rows stay in the wishlist. */
final class AddWishlistToCartResult
{
    /**
     * @param list<array{itemId: int, variantCode: string, quantity: int}> $added
     * @param list<array{itemId: int, variantCode: ?string, reason: string, message: ?string}> $skipped
     *        reason: out_of_stock | unavailable | rejected | not_in_wishlist
     */
    public function __construct(
        #[Groups(['bwlab_wishlist:shop'])]
        public string $orderTokenValue,
        #[Groups(['bwlab_wishlist:shop'])]
        public array $added,
        #[Groups(['bwlab_wishlist:shop'])]
        public array $skipped,
    ) {
    }
}
