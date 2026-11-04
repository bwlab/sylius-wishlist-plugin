<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\Api\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\Pagination;
use ApiPlatform\State\Pagination\TraversablePaginator;
use ApiPlatform\State\ProviderInterface;
use Bwlab\SyliusWishlistPlugin\Api\Factory\AdminWishlistFactory;
use Bwlab\SyliusWishlistPlugin\Api\Resource\AdminWishlist;
use Bwlab\SyliusWishlistPlugin\Repository\WishlistRepositoryInterface;
use Bwlab\SyliusWishlistPlugin\Entity\WishlistInterface;
use Bwlab\SyliusWishlistPlugin\Entity\WishlistItemInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator as DoctrinePaginator;

/** @implements ProviderInterface<AdminWishlist> */
final class AdminWishlistCollectionProvider implements ProviderInterface
{
    public function __construct(
        private readonly WishlistRepositoryInterface $wishlistRepository,
        private readonly AdminWishlistFactory $factory,
        private readonly Pagination $pagination,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): TraversablePaginator
    {
        $filters = $context['filters'] ?? [];
        $queryBuilder = $this->wishlistRepository->createQueryBuilder('wishlist')
            ->innerJoin('wishlist.channel', 'channel')
            ->leftJoin('wishlist.customer', 'customer')
            ->addSelect('channel', 'customer')
            ->orderBy('wishlist.id', 'DESC');

        if (isset($filters['channel']) && \is_string($filters['channel']) && '' !== $filters['channel']) {
            $queryBuilder->andWhere('channel.code = :channel')->setParameter('channel', $filters['channel']);
        }
        if (isset($filters['customer']) && \is_string($filters['customer']) && '' !== $filters['customer']) {
            $queryBuilder->andWhere('customer.id = :customerId OR customer.email = :customerEmail')
                ->setParameter('customerId', ctype_digit($filters['customer']) ? (int) $filters['customer'] : 0)
                ->setParameter('customerEmail', $filters['customer']);
        }

        $page = $this->pagination->getPage($context);
        $limit = $this->pagination->getLimit($operation, $context);
        $queryBuilder->setFirstResult($this->pagination->getOffset($operation, $context))->setMaxResults($limit);
        $doctrinePaginator = new DoctrinePaginator($queryBuilder, false);

        $wishlists = iterator_to_array($doctrinePaginator, false);
        $counts = $this->countItems($wishlists);
        $views = [];
        foreach ($wishlists as $wishlist) {
            $views[] = $this->factory->create($wishlist, false, $counts[$wishlist->getId()] ?? 0);
        }

        return new TraversablePaginator(new \ArrayIterator($views), $page, $limit, \count($doctrinePaginator));
    }

    /**
     * One COUNT query for the whole page (instead of loading the rows of every wishlist).
     *
     * @param list<WishlistInterface> $wishlists
     *
     * @return array<int, int> wishlist id => rows
     */
    private function countItems(array $wishlists): array
    {
        if ([] === $wishlists) {
            return [];
        }

        $rows = $this->entityManager->createQueryBuilder()
            ->select('IDENTITY(item.wishlist) AS wishlistId', 'COUNT(item.id) AS total')
            ->from(WishlistItemInterface::class, 'item')
            ->where('item.wishlist IN (:wishlists)')
            ->groupBy('item.wishlist')
            ->setParameter('wishlists', $wishlists)
            ->getQuery()
            ->getArrayResult();

        return array_column($rows, 'total', 'wishlistId');
    }
}
