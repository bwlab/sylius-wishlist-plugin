<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\Admin\Customer;

use Bwlab\SyliusWishlistPlugin\Repository\WishlistRepositoryInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final class CustomerWishlistReader implements CustomerWishlistReaderInterface
{
    public function __construct(
        #[Autowire(service: 'bwlab_wishlist.repository.wishlist')]
        private readonly WishlistRepositoryInterface $repository,
        private readonly CustomerWishlistGrouper $grouper,
    ) {
    }

    public function read(CustomerInterface $customer): array
    {
        return $this->grouper->group($this->repository->findAllWithItemsByCustomer($customer));
    }
}
