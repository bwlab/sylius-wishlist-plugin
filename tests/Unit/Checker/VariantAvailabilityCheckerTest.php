<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\Tests\Unit\Checker;

use Bwlab\SyliusWishlistPlugin\Checker\VariantAvailabilityChecker;
use Bwlab\SyliusWishlistPlugin\Checker\WishlistAvailability;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Inventory\Checker\AvailabilityCheckerInterface;

final class VariantAvailabilityCheckerTest extends TestCase
{
    private ChannelInterface $channel;

    protected function setUp(): void
    {
        $this->channel = $this->createStub(ChannelInterface::class);
    }

    #[Test]
    public function disabled_variant_is_unavailable(): void
    {
        $variant = $this->variant(false, $this->product(true, true));

        self::assertSame(WishlistAvailability::Unavailable, $this->checker(true)->check($variant, $this->channel));
    }

    #[Test]
    public function variant_without_product_is_unavailable(): void
    {
        $variant = $this->variant(true, null);

        self::assertSame(WishlistAvailability::Unavailable, $this->checker(true)->check($variant, $this->channel));
    }

    #[Test]
    public function disabled_product_is_unavailable(): void
    {
        $variant = $this->variant(true, $this->product(false, true));

        self::assertSame(WishlistAvailability::Unavailable, $this->checker(true)->check($variant, $this->channel));
    }

    #[Test]
    public function product_not_in_channel_is_unavailable(): void
    {
        $variant = $this->variant(true, $this->product(true, false));

        self::assertSame(WishlistAvailability::Unavailable, $this->checker(true)->check($variant, $this->channel));
    }

    #[Test]
    public function stock_not_available_is_out_of_stock(): void
    {
        $variant = $this->variant(true, $this->product(true, true));

        self::assertSame(WishlistAvailability::OutOfStock, $this->checker(false)->check($variant, $this->channel));
    }

    #[Test]
    public function enabled_variant_in_channel_with_stock_is_available(): void
    {
        $variant = $this->variant(true, $this->product(true, true));

        $result = $this->checker(true)->check($variant, $this->channel);

        self::assertSame(WishlistAvailability::Available, $result);
        self::assertTrue($result->isPurchasable());
    }

    #[Test]
    public function unavailable_variant_does_not_query_stock(): void
    {
        $availabilityChecker = $this->createMock(AvailabilityCheckerInterface::class);
        $availabilityChecker->expects(self::never())->method('isStockAvailable');

        $checker = new VariantAvailabilityChecker($availabilityChecker);

        self::assertSame(
            WishlistAvailability::Unavailable,
            $checker->check($this->variant(false, null), $this->channel),
        );
    }

    private function checker(bool $stockAvailable): VariantAvailabilityChecker
    {
        $availabilityChecker = $this->createStub(AvailabilityCheckerInterface::class);
        $availabilityChecker->method('isStockAvailable')->willReturn($stockAvailable);

        return new VariantAvailabilityChecker($availabilityChecker);
    }

    private function product(bool $enabled, bool $inChannel): ProductInterface
    {
        $product = $this->createStub(ProductInterface::class);
        $product->method('isEnabled')->willReturn($enabled);
        $product->method('hasChannel')->willReturnCallback(fn (ChannelInterface $channel): bool => $inChannel && $channel === $this->channel);

        return $product;
    }

    private function variant(bool $enabled, ?ProductInterface $product): ProductVariantInterface
    {
        $variant = $this->createStub(ProductVariantInterface::class);
        $variant->method('isEnabled')->willReturn($enabled);
        $variant->method('getProduct')->willReturn($product);

        return $variant;
    }
}
