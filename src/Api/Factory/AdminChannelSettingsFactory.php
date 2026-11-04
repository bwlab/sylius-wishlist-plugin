<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\Api\Factory;

use Bwlab\SyliusWishlistPlugin\Api\Resource\AdminWishlistChannelSettings;
use Bwlab\SyliusWishlistPlugin\Entity\WishlistChannelSettingsInterface;
use Bwlab\SyliusWishlistPlugin\Provider\GuestLifetimeProviderInterface;
use Sylius\Component\Channel\Model\ChannelInterface as BaseChannelInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Resource\Doctrine\Persistence\RepositoryInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final class AdminChannelSettingsFactory
{
    /** @param RepositoryInterface<WishlistChannelSettingsInterface> $settingsRepository */
    public function __construct(
        private readonly GuestLifetimeProviderInterface $guestLifetimeProvider,
        #[Autowire(service: 'bwlab_wishlist.repository.channel_settings')]
        private readonly RepositoryInterface $settingsRepository,
    ) {
    }

    public function create(BaseChannelInterface $channel): AdminWishlistChannelSettings
    {
        \assert($channel instanceof ChannelInterface);
        $settings = $this->settingsRepository->findOneBy(['channel' => $channel]);

        $view = new AdminWishlistChannelSettings();
        $view->channelCode = (string) $channel->getCode();
        $view->guestLifetimeDays = $this->guestLifetimeProvider->getGuestLifetimeDays($channel);
        $view->usesDefault = null === $settings?->getGuestLifetimeDays();

        return $view;
    }
}
