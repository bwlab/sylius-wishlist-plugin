<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\Api\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Bwlab\SyliusWishlistPlugin\Api\Error\WishlistErrorMapper;
use Bwlab\SyliusWishlistPlugin\Exception\WishlistOperationException;
use Bwlab\SyliusWishlistPlugin\Resolver\CurrentWishlistResolverInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/** @implements ProcessorInterface<mixed, null> */
final class DeleteWishlistListProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly CurrentWishlistResolverInterface $resolver,
        private readonly WishlistErrorMapper $errors,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): null
    {
        $wishlist = $this->resolver->findOwned((int) $uriVariables['id']) ?? throw new NotFoundHttpException('Wishlist not found.');

        try {
            $this->resolver->delete($wishlist); // the default list: 409
        } catch (WishlistOperationException $e) {
            throw $this->errors->map($e);
        }

        return null;
    }
}
