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
    public function findOneByCustomerAndChannel(CustomerInterface $customer, ChannelInterface $channel): ?WishlistInterface
    {
        return $this->findOneBy(['customer' => $customer, 'channel' => $channel]);
    }

    public function findOneGuestByTokenAndChannel(string $token, ChannelInterface $channel): ?WishlistInterface
    {
        return $this->findOneBy(['token' => $token, 'channel' => $channel, 'customer' => null]);
    }
}
