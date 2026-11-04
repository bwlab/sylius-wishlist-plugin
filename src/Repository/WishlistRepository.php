<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\Repository;

use Bwlab\SyliusWishlistPlugin\Entity\WishlistInterface;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;

/** @extends EntityRepository<WishlistInterface> */
class WishlistRepository extends EntityRepository implements WishlistRepositoryInterface
{
    private const ORDER = ['default' => 'DESC', 'createdAt' => 'ASC', 'id' => 'ASC'];

    public function findOneByCustomerAndChannel(CustomerInterface $customer, ChannelInterface $channel): ?WishlistInterface
    {
        return $this->findOneBy(['customer' => $customer, 'channel' => $channel, 'default' => true]);
    }

    public function findOneGuestByTokenAndChannel(string $token, ChannelInterface $channel): ?WishlistInterface
    {
        return $this->findOneBy(['token' => $token, 'channel' => $channel, 'customer' => null, 'default' => true]);
    }

    public function findAllByCustomerAndChannel(CustomerInterface $customer, ChannelInterface $channel): array
    {
        return array_values($this->findBy(['customer' => $customer, 'channel' => $channel], self::ORDER));
    }

    public function findAllGuestByTokenAndChannel(string $token, ChannelInterface $channel): array
    {
        return array_values($this->findBy(['token' => $token, 'channel' => $channel, 'customer' => null], self::ORDER));
    }

    public function findAllWithItemsByCustomer(CustomerInterface $customer): array
    {
        return array_values($this->createQueryBuilder('w')
            ->addSelect('c', 'i', 'v', 'vt', 'p', 'pt', 'pc', 'pi')
            ->innerJoin('w.channel', 'c')
            ->leftJoin('w.items', 'i')
            ->leftJoin('i.variant', 'v')
            ->leftJoin('v.translations', 'vt')
            ->leftJoin('v.product', 'p')
            ->leftJoin('p.translations', 'pt')
            ->leftJoin('p.channels', 'pc')
            ->leftJoin('p.images', 'pi')
            ->andWhere('w.customer = :customer')
            ->setParameter('customer', $customer)
            ->getQuery()
            ->getResult());
    }

    public function countItemsByCustomer(CustomerInterface $customer): int
    {
        return (int) $this->getEntityManager()->createQuery(
            'SELECT COUNT(i.id) FROM ' . \Bwlab\SyliusWishlistPlugin\Entity\WishlistItemInterface::class . ' i JOIN i.wishlist w WHERE w.customer = :customer',
        )->setParameter('customer', $customer)->getSingleScalarResult();
    }

    public function findOneByShareToken(string $shareToken): ?WishlistInterface
    {
        return $this->findOneBy(['shareToken' => $shareToken]);
    }
}
