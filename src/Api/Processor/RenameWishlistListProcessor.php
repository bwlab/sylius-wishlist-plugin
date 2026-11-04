<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\Api\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Bwlab\SyliusWishlistPlugin\Api\Error\WishlistErrorMapper;
use Bwlab\SyliusWishlistPlugin\Api\Factory\WishlistViewFactory;
use Bwlab\SyliusWishlistPlugin\Api\Input\RenameWishlistList;
use Bwlab\SyliusWishlistPlugin\Api\Resource\ShopWishlistList;
use Bwlab\SyliusWishlistPlugin\Exception\WishlistNameException;
use Bwlab\SyliusWishlistPlugin\Resolver\CurrentWishlistResolverInterface;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/** @implements ProcessorInterface<RenameWishlistList, ShopWishlistList> */
final class RenameWishlistListProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly CurrentWishlistResolverInterface $resolver,
        private readonly WishlistViewFactory $viewFactory,
        private readonly WishlistErrorMapper $errors,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ShopWishlistList
    {
        /** @var RenameWishlistList $data */
        $wishlist = $this->resolver->findOwned((int) $uriVariables['id']) ?? throw new NotFoundHttpException('Wishlist not found.');

        try {
            $this->resolver->rename($wishlist, (string) $data->name);
        } catch (WishlistNameException $e) {
            throw $this->errors->map($e);
        } catch (UniqueConstraintViolationException) {
            throw new ConflictHttpException('The list was renamed by a concurrent request: retry.');
        }

        return $this->viewFactory->shopList($wishlist, false);
    }
}
