<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\Repository;

use Bwlab\SyliusWishlistPlugin\Entity\WishlistInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Resource\Doctrine\Persistence\RepositoryInterface;

/** @extends RepositoryInterface<WishlistInterface> */
interface WishlistRepositoryInterface extends RepositoryInterface
{
    public function findOneByCustomerAndChannel(CustomerInterface $customer, ChannelInterface $channel): ?WishlistInterface;

    public function findOneGuestByTokenAndChannel(string $token, ChannelInterface $channel): ?WishlistInterface;
}
