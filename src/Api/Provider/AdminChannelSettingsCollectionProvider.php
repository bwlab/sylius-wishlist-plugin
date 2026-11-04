<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\Api\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Bwlab\SyliusWishlistPlugin\Api\Factory\AdminChannelSettingsFactory;
use Bwlab\SyliusWishlistPlugin\Api\Resource\AdminWishlistChannelSettings;
use Sylius\Component\Channel\Repository\ChannelRepositoryInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/** @implements ProviderInterface<AdminWishlistChannelSettings> */
final class AdminChannelSettingsCollectionProvider implements ProviderInterface
{
    public function __construct(
        #[Autowire(service: 'sylius.repository.channel')]
        private readonly ChannelRepositoryInterface $channelRepository,
        private readonly AdminChannelSettingsFactory $factory,
    ) {
    }

    /** @return list<AdminWishlistChannelSettings> */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        return array_map($this->factory->create(...), $this->channelRepository->findAll());
    }
}
