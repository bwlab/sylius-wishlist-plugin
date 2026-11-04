<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\Twig;

use Bwlab\SyliusWishlistPlugin\Resolver\CurrentWishlistResolverInterface;
use Twig\Attribute\AsTwigFunction;

final class WishlistExtension
{
    public function __construct(private readonly CurrentWishlistResolverInterface $resolver)
    {
    }

    /** Number of rows in the current wishlist (0 when there is none; never creates one). */
    #[AsTwigFunction('bwlab_wishlist_count')]
    public function count(): int
    {
        return $this->resolver->find()?->countItems() ?? 0;
    }
}
