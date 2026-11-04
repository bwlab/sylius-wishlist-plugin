<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\Purge;

use Bwlab\SyliusWishlistPlugin\Repository\WishlistRepository;
use Sylius\Component\Core\Model\ChannelInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final class ExpiredGuestWishlistFinder implements ExpiredGuestWishlistFinderInterface
{
    public function __construct(
        #[Autowire(service: 'bwlab_wishlist.repository.wishlist')]
        private readonly WishlistRepository $wishlistRepository,
    ) {
    }

    public function find(ChannelInterface $channel, \DateTimeImmutable $cutoff): array
    {
        // Expiry is per OWNER (guest token): all its lists go when none was seen after the cutoff. Never visited → creation date.
        $tokens = $this->wishlistRepository->createQueryBuilder('o')
            ->select('o.token')
            ->andWhere('o.channel = :channel')
            ->andWhere('o.customer IS NULL')
            ->groupBy('o.token')
            ->having('MAX(COALESCE(o.lastSeenAt, o.createdAt)) < :cutoff AND MAX(COALESCE(o.updatedAt, o.createdAt)) < :cutoff')
            ->setParameter('channel', $channel)
            ->setParameter('cutoff', $cutoff)
            ->getQuery()
            ->getSingleColumnResult()
        ;
        if ([] === $tokens) {
            return [];
        }

        return $this->wishlistRepository->createQueryBuilder('w')
            ->andWhere('w.channel = :channel')
            ->andWhere('w.customer IS NULL')
            ->andWhere('w.token IN (:tokens)')
            ->setParameter('channel', $channel)
            ->setParameter('tokens', $tokens)
            ->getQuery()
            ->getResult()
        ;
    }
}
