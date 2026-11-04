<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\Admin\Customer;

use Bwlab\SyliusWishlistPlugin\Repository\WishlistRepositoryInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Twig\Attribute\AsTwigFunction;

/** Twig access for the admin customer page and grid. */
final class CustomerWishlistExtension
{
    public function __construct(
        private readonly CustomerWishlistReaderInterface $reader,
        #[Autowire(service: 'bwlab_wishlist.repository.wishlist')]
        private readonly WishlistRepositoryInterface $repository,
    ) {
    }

    /** @return list<ChannelWishlists> */
    #[AsTwigFunction('bwlab_wishlist_customer_lists')]
    public function lists(CustomerInterface $customer): array
    {
        return $this->reader->read($customer);
    }

    /** One COUNT per grid row (max 50 rows per page). */
    #[AsTwigFunction('bwlab_wishlist_customer_item_count')]
    public function itemCount(CustomerInterface $customer): int
    {
        return $this->repository->countItemsByCustomer($customer);
    }
}
