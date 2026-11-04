<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\Api\Resource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use Bwlab\SyliusWishlistPlugin\Api\Input\CreateWishlistList;
use Bwlab\SyliusWishlistPlugin\Api\Input\RenameWishlistList;
use Bwlab\SyliusWishlistPlugin\Api\Processor\CreateWishlistListProcessor;
use Bwlab\SyliusWishlistPlugin\Api\Processor\DeleteWishlistListProcessor;
use Bwlab\SyliusWishlistPlugin\Api\Processor\RenameWishlistListProcessor;
use Bwlab\SyliusWishlistPlugin\Api\Provider\ShopWishlistListCollectionProvider;
use Bwlab\SyliusWishlistPlugin\Api\Provider\ShopWishlistListProvider;
use Symfony\Component\Serializer\Attribute\Groups;

/** One named list of the current owner (customer JWT or X-Wishlist-Token header) in the current channel. Max 20 per owner: no pagination. */
#[ApiResource(
    shortName: 'WishlistList',
    operations: [
        new GetCollection(
            uriTemplate: '/shop/wishlists',
            name: 'bwlab_wishlist_api_shop_list_get_collection',
            paginationEnabled: false,
            provider: ShopWishlistListCollectionProvider::class,
            normalizationContext: ['groups' => ['bwlab_wishlist:shop:list']],
        ),
        new Get(
            uriTemplate: '/shop/wishlists/{id}',
            name: 'bwlab_wishlist_api_shop_list_get',
            provider: ShopWishlistListProvider::class,
        ),
        new Post(
            uriTemplate: '/shop/wishlists',
            name: 'bwlab_wishlist_api_shop_list_post',
            read: false,
            input: CreateWishlistList::class,
            processor: CreateWishlistListProcessor::class,
            normalizationContext: ['groups' => ['bwlab_wishlist:shop:list']],
        ),
        new Patch(
            uriTemplate: '/shop/wishlists/{id}',
            name: 'bwlab_wishlist_api_shop_list_patch',
            read: false,
            input: RenameWishlistList::class,
            processor: RenameWishlistListProcessor::class,
            normalizationContext: ['groups' => ['bwlab_wishlist:shop:list']],
        ),
        new Delete(
            uriTemplate: '/shop/wishlists/{id}',
            name: 'bwlab_wishlist_api_shop_list_delete',
            read: false,
            processor: DeleteWishlistListProcessor::class,
        ),
    ],
    normalizationContext: ['groups' => ['bwlab_wishlist:shop']],
)]
final class ShopWishlistList
{
    #[ApiProperty(identifier: true)]
    #[Groups(['bwlab_wishlist:shop', 'bwlab_wishlist:shop:list'])]
    public ?int $id = null;

    /** The default list is returned translated ("My Favorites" / "I miei preferiti"). */
    #[Groups(['bwlab_wishlist:shop', 'bwlab_wishlist:shop:list'])]
    public string $name = '';

    #[Groups(['bwlab_wishlist:shop', 'bwlab_wishlist:shop:list'])]
    public bool $isDefault = false;

    #[Groups(['bwlab_wishlist:shop', 'bwlab_wishlist:shop:list'])]
    public int $itemsCount = 0;

    /** Visitor owner: send it back in the X-Wishlist-Token header (shared by all the lists of the owner). */
    #[Groups(['bwlab_wishlist:shop', 'bwlab_wishlist:shop:list'])]
    public string $wishlistToken = '';

    #[Groups(['bwlab_wishlist:shop', 'bwlab_wishlist:shop:list'])]
    public ?\DateTimeImmutable $createdAt = null;

    #[Groups(['bwlab_wishlist:shop', 'bwlab_wishlist:shop:list'])]
    public ?\DateTimeImmutable $updatedAt = null;

    /** @var list<ShopWishlistItem> only in the detail */
    #[Groups(['bwlab_wishlist:shop'])]
    public array $items = [];
}
