<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\Api\Resource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use Bwlab\SyliusWishlistPlugin\Api\Provider\AdminChannelSettingsCollectionProvider;
use Bwlab\SyliusWishlistPlugin\Api\Provider\AdminChannelSettingsProvider;

/** Read-only: one row per channel with the EFFECTIVE values (channel settings, else the bundle defaults). */
#[ApiResource(
    shortName: 'AdminWishlistChannelSettings',
    operations: [
        new GetCollection(
            uriTemplate: '/admin/wishlist-channel-settings',
            name: 'bwlab_wishlist_api_admin_settings_get_collection',
            security: "is_granted('ROLE_API_ACCESS')",
            provider: AdminChannelSettingsCollectionProvider::class,
            paginationEnabled: false,
        ),
        new Get(
            uriTemplate: '/admin/wishlist-channel-settings/{channelCode}',
            name: 'bwlab_wishlist_api_admin_settings_get',
            security: "is_granted('ROLE_API_ACCESS')",
            provider: AdminChannelSettingsProvider::class,
        ),
    ],
)]
final class AdminWishlistChannelSettings
{
    #[ApiProperty(identifier: true)]
    public string $channelCode = '';

    /** Days a visitor wishlist lives after the last visit. */
    public int $guestLifetimeDays = 0;

    /** True when the channel has no saved settings and the bundle default applies. */
    public bool $usesDefault = true;
}
