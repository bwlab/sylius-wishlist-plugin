<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\Provider;

use Bwlab\SyliusWishlistPlugin\Entity\WishlistChannelSettingsInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Resource\Doctrine\Persistence\RepositoryInterface;

final class GuestLifetimeProvider implements GuestLifetimeProviderInterface
{
    /** @param RepositoryInterface<WishlistChannelSettingsInterface> $channelSettingsRepository */
    public function __construct(
        private readonly RepositoryInterface $channelSettingsRepository,
        private readonly int $defaultGuestLifetimeDays,
    ) {
    }

    public function getGuestLifetimeDays(ChannelInterface $channel): int
    {
        /** @var WishlistChannelSettingsInterface|null $settings */
        $settings = $this->channelSettingsRepository->findOneBy(['channel' => $channel]);

        return $settings?->getGuestLifetimeDays() ?? $this->defaultGuestLifetimeDays;
    }
}
