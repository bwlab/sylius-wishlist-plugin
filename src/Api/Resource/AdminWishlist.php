<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\Api\Resource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\QueryParameter;
use Bwlab\SyliusWishlistPlugin\Api\Provider\AdminWishlistCollectionProvider;
use Bwlab\SyliusWishlistPlugin\Api\Provider\AdminWishlistProvider;
use Symfony\Component\Serializer\Attribute\Groups;

/** Read-only: the merchant never edits a customer's wishlist. */
#[ApiResource(
    shortName: 'AdminWishlist',
    operations: [
        new GetCollection(
            uriTemplate: '/admin/wishlists',
            name: 'bwlab_wishlist_api_admin_get_collection',
            security: "is_granted('ROLE_API_ACCESS')",
            provider: AdminWishlistCollectionProvider::class,
            normalizationContext: ['groups' => ['bwlab_wishlist:admin:index']],
            parameters: [
                'customer' => new QueryParameter(description: 'Customer id or email (exact match)'),
                'channel' => new QueryParameter(description: 'Channel code'),
            ],
        ),
        new Get(
            uriTemplate: '/admin/wishlists/{id}',
            name: 'bwlab_wishlist_api_admin_get',
            security: "is_granted('ROLE_API_ACCESS')",
            provider: AdminWishlistProvider::class,
            normalizationContext: ['groups' => ['bwlab_wishlist:admin:index', 'bwlab_wishlist:admin:show']],
        ),
    ],
)]
final class AdminWishlist
{
    #[ApiProperty(identifier: true)]
    #[Groups(['bwlab_wishlist:admin:index'])]
    public ?int $id = null;

    /** Name of the list as stored (the default list is always "My Favorites"). */
    #[Groups(['bwlab_wishlist:admin:index'])]
    public string $name = '';

    #[Groups(['bwlab_wishlist:admin:index'])]
    public bool $isDefault = false;

    #[Groups(['bwlab_wishlist:admin:index'])]
    public string $channelCode = '';

    /** Null for a visitor wishlist. */
    #[Groups(['bwlab_wishlist:admin:index'])]
    public ?int $customerId = null;

    #[Groups(['bwlab_wishlist:admin:index'])]
    public ?string $customerEmail = null;

    #[Groups(['bwlab_wishlist:admin:index'])]
    public bool $guest = false;

    #[Groups(['bwlab_wishlist:admin:index'])]
    public ?string $localeCode = null;

    #[Groups(['bwlab_wishlist:admin:index'])]
    public ?\DateTimeImmutable $createdAt = null;

    #[Groups(['bwlab_wishlist:admin:index'])]
    public ?\DateTimeImmutable $updatedAt = null;

    #[Groups(['bwlab_wishlist:admin:index'])]
    public ?\DateTimeImmutable $lastSeenAt = null;

    #[Groups(['bwlab_wishlist:admin:index'])]
    public int $itemsCount = 0;

    /** @var list<AdminWishlistItem> */
    #[Groups(['bwlab_wishlist:admin:show'])]
    public array $items = [];
}
