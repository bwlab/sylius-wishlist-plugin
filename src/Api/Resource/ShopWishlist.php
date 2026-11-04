<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\Api\Resource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use Bwlab\SyliusWishlistPlugin\Api\Input\AddWishlistToCart;
use Bwlab\SyliusWishlistPlugin\Api\Output\AddWishlistToCartResult;
use Bwlab\SyliusWishlistPlugin\Api\Processor\AddWishlistToCartProcessor;
use Bwlab\SyliusWishlistPlugin\Api\Provider\ShopWishlistProvider;
use Symfony\Component\Serializer\Attribute\Groups;

/** The wishlist of the current owner (customer JWT or X-Wishlist-Token header) in the current channel. */
#[ApiResource(
    shortName: 'Wishlist',
    operations: [
        new Get(
            uriTemplate: '/shop/wishlist',
            name: 'bwlab_wishlist_api_shop_get',
            provider: ShopWishlistProvider::class,
        ),
        new Post(
            uriTemplate: '/shop/wishlist/add-to-cart',
            name: 'bwlab_wishlist_api_shop_add_to_cart',
            status: 200,
            read: false,
            input: AddWishlistToCart::class,
            output: AddWishlistToCartResult::class,
            processor: AddWishlistToCartProcessor::class,
        ),
    ],
    normalizationContext: ['groups' => ['bwlab_wishlist:shop']],
)]
final class ShopWishlist
{
    /** Null until the first item is saved (nothing is stored for empty wishlists). */
    #[ApiProperty(identifier: true)]
    #[Groups(['bwlab_wishlist:shop'])]
    public ?string $token = null;

    /** Phase 2: id, name (translated) and flag of the default list; `listId` is null until the first item is saved. */
    #[Groups(['bwlab_wishlist:shop'])]
    public ?int $listId = null;

    #[Groups(['bwlab_wishlist:shop'])]
    public string $name = '';

    #[Groups(['bwlab_wishlist:shop'])]
    public bool $isDefault = true;

    #[Groups(['bwlab_wishlist:shop'])]
    public string $channelCode = '';

    #[Groups(['bwlab_wishlist:shop'])]
    public ?string $localeCode = null;

    #[Groups(['bwlab_wishlist:shop'])]
    public ?\DateTimeImmutable $createdAt = null;

    #[Groups(['bwlab_wishlist:shop'])]
    public ?\DateTimeImmutable $updatedAt = null;

    #[Groups(['bwlab_wishlist:shop'])]
    public ?\DateTimeImmutable $lastSeenAt = null;

    #[Groups(['bwlab_wishlist:shop'])]
    public int $itemsCount = 0;

    /** @var list<ShopWishlistItem> */
    #[Groups(['bwlab_wishlist:shop'])]
    public array $items = [];
}
