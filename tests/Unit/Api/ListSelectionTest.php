<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\Tests\Unit\Api;

use ApiPlatform\Metadata\Post;
use Bwlab\SyliusWishlistPlugin\Api\Factory\WishlistViewFactory;
use Bwlab\SyliusWishlistPlugin\Api\Input\AddWishlistItem;
use Bwlab\SyliusWishlistPlugin\Api\Processor\AddWishlistItemProcessor;
use Bwlab\SyliusWishlistPlugin\Api\Security\CurrentWishlistItemFinder;
use Bwlab\SyliusWishlistPlugin\Checker\VariantAvailabilityCheckerInterface;
use Bwlab\SyliusWishlistPlugin\Checker\WishlistAvailability;
use Bwlab\SyliusWishlistPlugin\Entity\Wishlist;
use Bwlab\SyliusWishlistPlugin\Modifier\WishlistItemModifierInterface;
use Bwlab\SyliusWishlistPlugin\Repository\WishlistRepositoryInterface;
use Bwlab\SyliusWishlistPlugin\Resolver\CurrentWishlistResolverInterface;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Core\Calculator\ProductVariantPricesCalculatorInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Core\Repository\ProductVariantRepositoryInterface;
use Sylius\Component\Locale\Context\LocaleContextInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Contracts\Translation\TranslatorInterface;

/** Which list an operation works on: omitted = default list (phase 1), given = only the owner's list, else 404. */
final class ListSelectionTest extends TestCase
{
    private function finder(?int $expectedFindOwned, ?Wishlist $default, ?Wishlist $owned): CurrentWishlistItemFinder
    {
        $resolver = $this->createMock(CurrentWishlistResolverInterface::class);
        $resolver->method('find')->willReturn($default);
        $resolver->expects(null === $expectedFindOwned ? self::never() : self::once())->method('findOwned')->with($expectedFindOwned)->willReturn($owned);

        return new CurrentWishlistItemFinder($resolver);
    }

    public function testOmittedListIsTheDefaultList(): void
    {
        $default = new Wishlist();

        self::assertSame($default, $this->finder(null, $default, null)->wishlist());
    }

    public function testGivenListIsLookedUpAmongTheOwnersLists(): void
    {
        $other = new Wishlist();

        self::assertSame($other, $this->finder(7, new Wishlist(), $other)->wishlist(7));
    }

    public function testForeignOrMissingListIs404(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->finder(7, new Wishlist(), null)->wishlist(7);
    }

    /** @return array{AddWishlistItemProcessor, CurrentWishlistResolverInterface&\PHPUnit\Framework\MockObject\MockObject} */
    private function addProcessor(): array
    {
        $resolver = $this->createMock(CurrentWishlistResolverInterface::class);
        $variant = $this->createStub(ProductVariantInterface::class);
        $variants = $this->createStub(ProductVariantRepositoryInterface::class);
        $variants->method('findOneBy')->willReturn($variant);
        $availability = $this->createStub(VariantAvailabilityCheckerInterface::class);
        $availability->method('check')->willReturn(WishlistAvailability::Available);
        $channels = $this->createStub(ChannelContextInterface::class);
        $channels->method('getChannel')->willReturn($this->createStub(ChannelInterface::class));

        $processor = new AddWishlistItemProcessor(
            $resolver,
            $this->createStub(WishlistItemModifierInterface::class),
            $this->createStub(WishlistRepositoryInterface::class),
            $variants,
            $availability,
            $channels,
            new WishlistViewFactory(
                $availability,
                $this->createStub(ProductVariantPricesCalculatorInterface::class),
                '/api/v2/shop',
                $this->createStub(TranslatorInterface::class),
                $this->createStub(LocaleContextInterface::class),
            ),
        );

        return [$processor, $resolver];
    }

    public function testAddWithForeignListIs404AndNothingIsAdded(): void
    {
        [$processor, $resolver] = $this->addProcessor();
        $resolver->expects(self::once())->method('getTarget')->with(7)->willReturn(null);
        $input = new AddWishlistItem();
        $input->variantCode = 'V1';
        $input->list = '/api/v2/shop/wishlists/7';

        $this->expectException(NotFoundHttpException::class);
        $processor->process($input, new Post());
    }

    public function testAddWithoutListUsesTheDefaultTarget(): void
    {
        [$processor, $resolver] = $this->addProcessor();
        // getTarget(null) = default list (getOrCreate): the phase 1 behaviour. The stub returns null only to stop the test there (404).
        $resolver->expects(self::once())->method('getTarget')->with(null)->willReturn(null);
        $input = new AddWishlistItem();
        $input->variantCode = 'V1';

        $this->expectException(NotFoundHttpException::class);
        $processor->process($input, new Post());
    }
}
