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
use Doctrine\ORM\Tools\Pagination\Paginator as DoctrinePaginator;

/** @implements ProviderInterface<AdminWishlist> */
final class AdminWishlistCollectionProvider implements ProviderInterface
{
    public function __construct(
        private readonly WishlistRepositoryInterface $wishlistRepository,
        private readonly AdminWishlistFactory $factory,
        private readonly Pagination $pagination,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): TraversablePaginator
    {
        $filters = $context['filters'] ?? [];
        $queryBuilder = $this->wishlistRepository->createQueryBuilder('wishlist')
            ->innerJoin('wishlist.channel', 'channel')
            ->leftJoin('wishlist.customer', 'customer')
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

        $views = [];
        foreach ($doctrinePaginator as $wishlist) {
            $views[] = $this->factory->create($wishlist, false);
        }

        return new TraversablePaginator(new \ArrayIterator($views), $page, $limit, \count($doctrinePaginator));
    }
}
