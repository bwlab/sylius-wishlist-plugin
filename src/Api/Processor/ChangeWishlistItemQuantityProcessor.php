<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\Api\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Bwlab\SyliusWishlistPlugin\Api\Factory\WishlistViewFactory;
use Bwlab\SyliusWishlistPlugin\Api\Input\ChangeWishlistItemQuantity;
use Bwlab\SyliusWishlistPlugin\Api\Resource\ShopWishlistItem;
use Bwlab\SyliusWishlistPlugin\Api\Security\CurrentWishlistItemFinder;
use Bwlab\SyliusWishlistPlugin\Modifier\WishlistItemModifierInterface;
use Bwlab\SyliusWishlistPlugin\Repository\WishlistRepositoryInterface;

/** @implements ProcessorInterface<ChangeWishlistItemQuantity, ShopWishlistItem> */
final class ChangeWishlistItemQuantityProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly CurrentWishlistItemFinder $finder,
        private readonly WishlistItemModifierInterface $modifier,
        private readonly WishlistRepositoryInterface $wishlistRepository,
        private readonly WishlistViewFactory $viewFactory,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ShopWishlistItem
    {
        /** @var ChangeWishlistItemQuantity $data */
        $item = $this->finder->find((int) $uriVariables['id']);
        $this->modifier->changeQuantity($item, (int) $data->quantity);
        $this->wishlistRepository->add($this->finder->wishlist());

        return $this->viewFactory->shopItem($item);
    }
}
