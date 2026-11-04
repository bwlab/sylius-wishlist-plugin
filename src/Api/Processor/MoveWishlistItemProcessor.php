<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\Api\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Bwlab\SyliusWishlistPlugin\Api\Error\WishlistErrorMapper;
use Bwlab\SyliusWishlistPlugin\Api\Factory\WishlistViewFactory;
use Bwlab\SyliusWishlistPlugin\Api\Input\ListReference;
use Bwlab\SyliusWishlistPlugin\Api\Input\MoveWishlistItem;
use Bwlab\SyliusWishlistPlugin\Api\Resource\ShopWishlistItem;
use Bwlab\SyliusWishlistPlugin\Api\Security\CurrentWishlistItemFinder;
use Bwlab\SyliusWishlistPlugin\Exception\WishlistOperationException;
use Bwlab\SyliusWishlistPlugin\Modifier\WishlistItemModifierInterface;
use Bwlab\SyliusWishlistPlugin\Repository\WishlistRepositoryInterface;
use Bwlab\SyliusWishlistPlugin\Resolver\CurrentWishlistResolverInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/** @implements ProcessorInterface<MoveWishlistItem, ShopWishlistItem> */
final class MoveWishlistItemProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly CurrentWishlistItemFinder $finder,
        private readonly CurrentWishlistResolverInterface $resolver,
        private readonly WishlistItemModifierInterface $modifier,
        private readonly WishlistRepositoryInterface $wishlistRepository,
        private readonly WishlistViewFactory $viewFactory,
        private readonly WishlistErrorMapper $errors,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ShopWishlistItem
    {
        /** @var MoveWishlistItem $data */
        $targetId = ListReference::parse($data->targetList)
            ?? throw new UnprocessableEntityHttpException('Send "targetList": the id or the IRI of the destination list.');

        $sourceId = ListReference::fromRequest($context['request'] ?? null);
        $source = $this->finder->wishlist($sourceId);
        $item = $this->finder->find((int) $uriVariables['id'], $sourceId);
        // Both lists must be the owner's: another owner's or a missing list is the same 404.
        $target = $this->resolver->findOwned($targetId) ?? throw new NotFoundHttpException('Wishlist not found.');

        try {
            $moved = $this->modifier->moveItem($item, $target);
        } catch (WishlistOperationException $e) {
            throw $this->errors->map($e);
        }
        $this->wishlistRepository->add($source);
        $this->wishlistRepository->add($target);

        return $this->viewFactory->shopItem($moved);
    }
}
