<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\Purge;

use Bwlab\SyliusWishlistPlugin\Entity\WishlistPurgeStatInterface;
use Bwlab\SyliusWishlistPlugin\Provider\GuestLifetimeProviderInterface;
use Doctrine\ORM\EntityManagerInterface;
use Sylius\Component\Channel\Repository\ChannelRepositoryInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Resource\Doctrine\Persistence\RepositoryInterface;
use Sylius\Resource\Factory\FactoryInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/** Deletes expired GUEST wishlists, after adding their anonymous counters to WishlistPurgeStat. Never touches customer wishlists. */
final class ExpiredGuestWishlistPurger
{
    /**
     * @param RepositoryInterface<WishlistPurgeStatInterface> $statRepository
     * @param FactoryInterface<WishlistPurgeStatInterface> $statFactory
     */
    public function __construct(
        #[Autowire(service: 'sylius.repository.channel')]
        private readonly ChannelRepositoryInterface $channelRepository,
        private readonly GuestLifetimeProviderInterface $guestLifetimeProvider,
        private readonly ExpiredGuestWishlistFinderInterface $finder,
        #[Autowire(service: 'bwlab_wishlist.repository.purge_stat')]
        private readonly RepositoryInterface $statRepository,
        #[Autowire(service: 'bwlab_wishlist.factory.purge_stat')]
        private readonly FactoryInterface $statFactory,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function purge(\DateTimeImmutable $now, bool $dryRun): PurgeResult
    {
        $purged = [];
        /** @var ChannelInterface $channel */
        foreach ($this->channelRepository->findAll() as $channel) {
            $days = $this->guestLifetimeProvider->getGuestLifetimeDays($channel);
            // Defensive: even if the finder were wrong, a wishlist with a customer is never purged.
            $expired = array_values(array_filter(
                $this->finder->find($channel, $now->modify(sprintf('-%d days', $days))),
                static fn ($wishlist): bool => $wishlist->isGuest(),
            ));
            $purged[(string) $channel->getCode()] = \count($expired);

            if ($dryRun || [] === $expired) {
                continue;
            }

            foreach (PurgeStatAggregator::aggregate($expired) as $total) {
                $this->addToStat($channel, $total['variant'], $now->setTime(0, 0), $total['itemsCount'], $total['quantitySum']);
            }
            foreach ($expired as $wishlist) {
                $this->entityManager->remove($wishlist); // items go with it (orphanRemoval)
            }
            $this->entityManager->flush(); // one transaction per channel: counters and deletion together
        }

        return new PurgeResult($purged, $dryRun);
    }

    private function addToStat(ChannelInterface $channel, object $variant, \DateTimeImmutable $day, int $itemsCount, int $quantitySum): void
    {
        /** @var WishlistPurgeStatInterface|null $stat */
        $stat = $this->statRepository->findOneBy(['channel' => $channel, 'variant' => $variant, 'purgedAt' => $day]);
        if (null === $stat) {
            $stat = $this->statFactory->createNew();
            $stat->setChannel($channel);
            $stat->setVariant($variant);
            $stat->setPurgedAt($day);
        }
        $stat->setItemsCount($stat->getItemsCount() + $itemsCount);
        $stat->setQuantitySum($stat->getQuantitySum() + $quantitySum);
        $this->entityManager->persist($stat);
    }
}
