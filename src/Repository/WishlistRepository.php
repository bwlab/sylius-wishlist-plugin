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
}
