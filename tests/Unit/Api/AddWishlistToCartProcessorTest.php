<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\Tests\Unit\Api;

use ApiPlatform\Metadata\Post;
use Bwlab\SyliusWishlistPlugin\Api\Cart\WishlistCartAdder;
use Bwlab\SyliusWishlistPlugin\Api\Input\AddWishlistToCart;
use Bwlab\SyliusWishlistPlugin\Api\Processor\AddWishlistToCartProcessor;
use Bwlab\SyliusWishlistPlugin\Api\Security\CurrentWishlistItemFinder;
use Bwlab\SyliusWishlistPlugin\Checker\VariantAvailabilityCheckerInterface;
use Bwlab\SyliusWishlistPlugin\Resolver\CurrentWishlistResolverInterface;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Sylius\Bundle\ApiBundle\Context\UserContextInterface;
use Sylius\Component\Core\Repository\OrderRepositoryInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

final class AddWishlistToCartProcessorTest extends TestCase
{
    public function testNeitherItemsNorAllIsRejected(): void
    {
        $processor = new AddWishlistToCartProcessor(
            new CurrentWishlistItemFinder($this->createStub(CurrentWishlistResolverInterface::class)),
            new WishlistCartAdder(
                $this->createStub(OrderRepositoryInterface::class),
                $this->createStub(VariantAvailabilityCheckerInterface::class),
                $this->createStub(ValidatorInterface::class),
                $this->createStub(MessageBusInterface::class),
                $this->createStub(UserContextInterface::class),
                $this->createStub(EntityManagerInterface::class),
            ),
            $this->createStub(ChannelContextInterface::class),
        );
        $input = new AddWishlistToCart();
        $input->orderTokenValue = 'cart';

        $this->expectException(UnprocessableEntityHttpException::class);
        $processor->process($input, new Post());
    }
}
