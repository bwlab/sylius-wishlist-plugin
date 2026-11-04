<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\Api\Resource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use Bwlab\SyliusWishlistPlugin\Api\Input\AddWishlistItem;
use Bwlab\SyliusWishlistPlugin\Api\Input\ChangeWishlistItemQuantity;
use Bwlab\SyliusWishlistPlugin\Api\Processor\AddWishlistItemProcessor;
use Bwlab\SyliusWishlistPlugin\Api\Processor\ChangeWishlistItemQuantityProcessor;
use Bwlab\SyliusWishlistPlugin\Api\Processor\RemoveWishlistItemProcessor;
use Bwlab\SyliusWishlistPlugin\Api\Provider\ShopWishlistItemProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'WishlistItem',
    operations: [
        new Get(
            uriTemplate: '/shop/wishlist/items/{id}',
            name: 'bwlab_wishlist_api_shop_item_get',
            provider: ShopWishlistItemProvider::class,
        ),
        new Post(
            uriTemplate: '/shop/wishlist/items',
            name: 'bwlab_wishlist_api_shop_item_post',
            status: 200, // idempotent: adding a variant already present returns the existing row
            read: false,
            input: AddWishlistItem::class,
            processor: AddWishlistItemProcessor::class,
        ),
        new Patch(
            uriTemplate: '/shop/wishlist/items/{id}',
            name: 'bwlab_wishlist_api_shop_item_patch',
            read: false,
            input: ChangeWishlistItemQuantity::class,
            processor: ChangeWishlistItemQuantityProcessor::class,
        ),
        new Delete(
            uriTemplate: '/shop/wishlist/items/{id}',
            name: 'bwlab_wishlist_api_shop_item_delete',
            read: false,
            processor: RemoveWishlistItemProcessor::class,
        ),
    ],
    normalizationContext: ['groups' => ['bwlab_wishlist:shop']],
)]
final class ShopWishlistItem extends WishlistItemFields
{
    /** Needed by guests: send it back in the X-Wishlist-Token header. */
    #[Groups(['bwlab_wishlist:shop'])]
    public string $wishlistToken = '';
}
