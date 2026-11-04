<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\Twig;

use Bwlab\SyliusWishlistPlugin\Entity\WishlistInterface;
use Bwlab\SyliusWishlistPlugin\Resolver\CurrentWishlistResolverInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Attribute\AsTwigFunction;

final class WishlistExtension
{
    public function __construct(
        private readonly CurrentWishlistResolverInterface $resolver,
        private readonly TranslatorInterface $translator,
    ) {
    }

    /** Rows in ALL lists of the current owner (0 when there are none; never creates one). */
    #[AsTwigFunction('bwlab_wishlist_count')]
    public function count(): int
    {
        return array_sum(array_map(static fn (WishlistInterface $list): int => $list->countItems(), $this->resolver->findAll()));
    }

    /** Name to show: the untouched default list gets the translated "My Favorites"; any other name as the user typed it. */
    #[AsTwigFunction('bwlab_wishlist_list_name')]
    public function listName(WishlistInterface $list): string
    {
        return $list->isDefault() && WishlistInterface::DEFAULT_NAME === $list->getName()
            ? $this->translator->trans('bwlab_wishlist.ui.shop.default_list_name')
            : $list->getName();
    }
}
