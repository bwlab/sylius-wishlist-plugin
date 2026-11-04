<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\Admin\Customer;

use Sylius\Component\Core\Model\CustomerInterface;

interface CustomerWishlistReaderInterface
{
    /** Read-only view of the customer's lists, grouped by channel. Empty when the customer has none. @return list<ChannelWishlists> */
    public function read(CustomerInterface $customer): array;
}
