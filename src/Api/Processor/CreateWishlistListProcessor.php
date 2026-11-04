<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\Api\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Bwlab\SyliusWishlistPlugin\Api\Error\WishlistErrorMapper;
use Bwlab\SyliusWishlistPlugin\Api\Factory\WishlistViewFactory;
use Bwlab\SyliusWishlistPlugin\Api\Input\CreateWishlistList;
use Bwlab\SyliusWishlistPlugin\Api\Resource\ShopWishlistList;
use Bwlab\SyliusWishlistPlugin\Exception\WishlistNameException;
use Bwlab\SyliusWishlistPlugin\Resolver\CurrentWishlistResolverInterface;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/** @implements ProcessorInterface<CreateWishlistList, ShopWishlistList> */
final class CreateWishlistListProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly CurrentWishlistResolverInterface $resolver,
        private readonly WishlistViewFactory $viewFactory,
        private readonly WishlistErrorMapper $errors,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ShopWishlistList
    {
        /** @var CreateWishlistList $data */
        try {
            $wishlist = $this->resolver->create((string) $data->name);
        } catch (WishlistNameException $e) {
            throw $this->errors->map($e);
        } catch (UniqueConstraintViolationException) {
            // Same name created by a parallel request (the EntityManager is closed by Doctrine, no retry here).
            throw new ConflictHttpException('The list was created by a concurrent request: retry.');
        }

        return $this->viewFactory->shopList($wishlist, false);
    }
}
