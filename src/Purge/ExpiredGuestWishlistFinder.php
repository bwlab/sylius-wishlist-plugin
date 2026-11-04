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
        // Wishlists never visited again fall back to their creation date.
        return $this->wishlistRepository->createQueryBuilder('w')
            ->andWhere('w.channel = :channel')
            ->andWhere('w.customer IS NULL')
            ->andWhere('COALESCE(w.lastSeenAt, w.createdAt) < :cutoff')
            ->setParameter('channel', $channel)
            ->setParameter('cutoff', $cutoff)
            ->getQuery()
            ->getResult()
        ;
    }
}
