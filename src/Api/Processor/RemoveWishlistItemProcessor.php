<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\Api\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Bwlab\SyliusWishlistPlugin\Api\Security\CurrentWishlistItemFinder;
use Bwlab\SyliusWishlistPlugin\Modifier\WishlistItemModifierInterface;
use Bwlab\SyliusWishlistPlugin\Repository\WishlistRepositoryInterface;

/** @implements ProcessorInterface<mixed, null> */
final class RemoveWishlistItemProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly CurrentWishlistItemFinder $finder,
        private readonly WishlistItemModifierInterface $modifier,
        private readonly WishlistRepositoryInterface $wishlistRepository,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): null
    {
        $item = $this->finder->find((int) $uriVariables['id']);
        $variant = $item->getVariant();
        \assert(null !== $variant);
        $this->modifier->removeVariant($this->finder->wishlist(), $variant);
        $this->wishlistRepository->add($this->finder->wishlist());

        return null;
    }
}
