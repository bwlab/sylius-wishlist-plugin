<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\Menu;

use Knp\Menu\ItemInterface;
use Sylius\Bundle\AdminBundle\Menu\Provider\MenuProviderInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/** Adds "Wishlist > Settings" to the admin main menu (after Marketing, before Configuration). */
#[AutoconfigureTag('sylius_admin.main_menu_provider', ['priority' => -550])]
final class WishlistMenuProvider implements MenuProviderInterface
{
    public function __invoke(ItemInterface $menu): void
    {
        $wishlist = $menu
            ->addChild('bwlab_wishlist')
            ->setLabel('bwlab_wishlist.ui.admin.menu.wishlist')
            ->setLabelAttribute('icon', 'tabler:heart-handshake')
        ;

        $wishlist
            ->addChild('bwlab_wishlist_settings', ['route' => 'bwlab_wishlist_admin_settings'])
            ->setLabel('bwlab_wishlist.ui.admin.menu.settings')
            ->setLabelAttribute('icon', 'tabler:settings')
        ;
    }
}
