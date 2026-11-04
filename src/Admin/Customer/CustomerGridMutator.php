<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\Admin\Customer;

use Sylius\Bundle\AdminBundle\Grid\CustomerGridInterface;
use Sylius\Bundle\GridBundle\Builder\Field\TwigField;
use Sylius\Component\Grid\Builder\GridBuilderInterface;
use Sylius\Component\Grid\Attribute\AsGridMutator;
use Sylius\Component\Grid\Mutator\GridMutatorInterface;

/** Adds the column "Wishlist products" to the admin customers grid. */
#[AsGridMutator(grid: CustomerGridInterface::NAME)]
final class CustomerGridMutator implements GridMutatorInterface
{
    public function __invoke(GridBuilderInterface $gridBuilder): void
    {
        $gridBuilder->addField(
            TwigField::create('wishlistItems', '@BwlabSyliusWishlistPlugin/admin/customer/grid_count.html.twig')
                ->setLabel('bwlab_wishlist.ui.admin.customer.grid_column')
                ->setPath('.')
                ->withOptions(['vars' => ['th_class' => 'w-1 text-center']]),
        );
    }
}
