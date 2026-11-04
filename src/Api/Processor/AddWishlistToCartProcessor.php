<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\Api\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Bwlab\SyliusWishlistPlugin\Api\Cart\WishlistCartAdder;
use Bwlab\SyliusWishlistPlugin\Api\Input\AddWishlistToCart;
use Bwlab\SyliusWishlistPlugin\Api\Input\ListReference;
use Bwlab\SyliusWishlistPlugin\Api\Output\AddWishlistToCartResult;
use Bwlab\SyliusWishlistPlugin\Api\Security\CurrentWishlistItemFinder;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/** @implements ProcessorInterface<AddWishlistToCart, AddWishlistToCartResult> */
final class AddWishlistToCartProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly CurrentWishlistItemFinder $finder,
        private readonly WishlistCartAdder $cartAdder,
        private readonly ChannelContextInterface $channelContext,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): AddWishlistToCartResult
    {
        /** @var AddWishlistToCart $data */
        if (!$data->all && [] === $data->itemIds) {
            throw new UnprocessableEntityHttpException('Send "itemIds" (selected rows) or "all": true.');
        }

        return $this->cartAdder->add(
            $this->finder->wishlist(ListReference::parse($data->list)),
            $this->channelContext->getChannel(),
            $data->orderTokenValue,
            $data->all ? null : $data->itemIds,
        );
    }
}
