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
    /** DEFAULT list of the customer in the channel (phase 1 meaning). */
    public function findOneByCustomerAndChannel(CustomerInterface $customer, ChannelInterface $channel): ?WishlistInterface;

    /** DEFAULT list of the guest owner token in the channel (phase 1 meaning). */
    public function findOneGuestByTokenAndChannel(string $token, ChannelInterface $channel): ?WishlistInterface;

    /** @return list<WishlistInterface> all lists of the customer in the channel, default first, then by creation */
    public function findAllByCustomerAndChannel(CustomerInterface $customer, ChannelInterface $channel): array;

    /** @return list<WishlistInterface> all lists of the guest owner token in the channel, default first, then by creation */
    public function findAllGuestByTokenAndChannel(string $token, ChannelInterface $channel): array;
}
