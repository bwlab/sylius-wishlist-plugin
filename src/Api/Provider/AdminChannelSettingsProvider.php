<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\Api\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Bwlab\SyliusWishlistPlugin\Api\Factory\AdminChannelSettingsFactory;
use Bwlab\SyliusWishlistPlugin\Api\Resource\AdminWishlistChannelSettings;
use Sylius\Component\Channel\Repository\ChannelRepositoryInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/** @implements ProviderInterface<AdminWishlistChannelSettings> */
final class AdminChannelSettingsProvider implements ProviderInterface
{
    public function __construct(
        #[Autowire(service: 'sylius.repository.channel')]
        private readonly ChannelRepositoryInterface $channelRepository,
        private readonly AdminChannelSettingsFactory $factory,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): AdminWishlistChannelSettings
    {
        $channel = $this->channelRepository->findOneByCode($uriVariables['channelCode']);
        if (null === $channel) {
            throw new NotFoundHttpException('Channel not found.');
        }

        return $this->factory->create($channel);
    }
}
