<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\Api\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Bwlab\SyliusWishlistPlugin\Api\Factory\WishlistViewFactory;
use Bwlab\SyliusWishlistPlugin\Api\Resource\ShopWishlist;
use Bwlab\SyliusWishlistPlugin\Resolver\CurrentWishlistResolverInterface;
use Sylius\Component\Channel\Context\ChannelContextInterface;

/** @implements ProviderInterface<ShopWishlist> */
final class ShopWishlistProvider implements ProviderInterface
{
    public function __construct(
        private readonly CurrentWishlistResolverInterface $resolver,
        private readonly WishlistViewFactory $viewFactory,
        private readonly ChannelContextInterface $channelContext,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ShopWishlist
    {
        $wishlist = $this->resolver->find();
        if (null !== $wishlist) {
            return $this->viewFactory->shopWishlist($wishlist);
        }

        // Nothing stored yet: an empty wishlist, never an error.
        $empty = new ShopWishlist();
        $empty->channelCode = (string) $this->channelContext->getChannel()->getCode();

        return $empty;
    }
}
