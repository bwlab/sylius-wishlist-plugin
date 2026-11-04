<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\Api\Resource;

use ApiPlatform\Metadata\ApiProperty;
use Symfony\Component\Serializer\Attribute\Groups;

/** Row data shared by the shop and admin API: same fields everywhere (merchant data included). */
abstract class WishlistItemFields
{
    #[ApiProperty(identifier: true)]
    #[Groups(['bwlab_wishlist:shop', 'bwlab_wishlist:admin:show'])]
    public ?int $id = null;

    #[Groups(['bwlab_wishlist:shop', 'bwlab_wishlist:admin:show'])]
    public string $variantCode = '';

    #[Groups(['bwlab_wishlist:shop', 'bwlab_wishlist:admin:show'])]
    public string $productCode = '';

    #[Groups(['bwlab_wishlist:shop', 'bwlab_wishlist:admin:show'])]
    public ?string $variantName = null;

    #[Groups(['bwlab_wishlist:shop', 'bwlab_wishlist:admin:show'])]
    public ?string $productName = null;

    /** IRI of the Sylius shop product variant. */
    #[Groups(['bwlab_wishlist:shop', 'bwlab_wishlist:admin:show'])]
    public string $variant = '';

    /** IRI of the Sylius shop product. */
    #[Groups(['bwlab_wishlist:shop', 'bwlab_wishlist:admin:show'])]
    public string $product = '';

    #[Groups(['bwlab_wishlist:shop', 'bwlab_wishlist:admin:show'])]
    public int $quantity = 1;

    #[Groups(['bwlab_wishlist:shop', 'bwlab_wishlist:admin:show'])]
    public ?\DateTimeImmutable $addedAt = null;

    /** pdp | plp | api */
    #[Groups(['bwlab_wishlist:shop', 'bwlab_wishlist:admin:show'])]
    public string $source = '';

    /** available | out_of_stock | unavailable */
    #[Groups(['bwlab_wishlist:shop', 'bwlab_wishlist:admin:show'])]
    public string $availability = '';

    /** Current channel price in minor units (null when the variant has no price in the channel). */
    #[Groups(['bwlab_wishlist:shop', 'bwlab_wishlist:admin:show'])]
    public ?int $price = null;

    #[Groups(['bwlab_wishlist:shop', 'bwlab_wishlist:admin:show'])]
    public ?int $originalPrice = null;

    #[Groups(['bwlab_wishlist:shop', 'bwlab_wishlist:admin:show'])]
    public ?string $currencyCode = null;

    /** Channel price in minor units when the product was saved (null: saved before 0.2.1 or no price then). */
    #[Groups(['bwlab_wishlist:shop', 'bwlab_wishlist:admin:show'])]
    public ?int $priceAtAdd = null;

    /** Currency of priceAtAdd (can differ from currencyCode if the channel currency changed since). */
    #[Groups(['bwlab_wishlist:shop', 'bwlab_wishlist:admin:show'])]
    public ?string $priceAtAddCurrencyCode = null;
}
