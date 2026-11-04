<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\Tests\Unit\Modifier;

use Bwlab\SyliusWishlistPlugin\Entity\Wishlist;
use Bwlab\SyliusWishlistPlugin\Entity\WishlistItem;
use Bwlab\SyliusWishlistPlugin\Entity\WishlistItemInterface;
use Bwlab\SyliusWishlistPlugin\Modifier\WishlistItemModifier;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Core\Calculator\ProductVariantPricesCalculatorInterface;
use Sylius\Component\Core\Exception\MissingChannelConfigurationException;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Currency\Model\CurrencyInterface;
use Sylius\Resource\Factory\FactoryInterface;

final class WishlistItemModifierTest extends TestCase
{
    private \DateTimeImmutable $now;

    private WishlistItemModifier $modifier;

    private Wishlist $wishlist;

    private ProductVariantPricesCalculatorInterface $calculator;

    protected function setUp(): void
    {
        $this->now = new \DateTimeImmutable('2026-03-15 10:00:00');

        $this->calculator = $this->createStub(ProductVariantPricesCalculatorInterface::class);
        $factory = $this->createStub(FactoryInterface::class);
        $factory->method('createNew')->willReturnCallback(static fn (): WishlistItem => new WishlistItem());

        $this->modifier = new WishlistItemModifier($factory, $this->calculator, fn (): \DateTimeImmutable => $this->now);
        $this->wishlist = new Wishlist();
    }

    #[Test]
    public function it_adds_a_new_variant(): void
    {
        $variant = $this->createStub(ProductVariantInterface::class);

        self::assertTrue($this->modifier->addVariant($this->wishlist, $variant, 3, WishlistItemInterface::SOURCE_PLP));

        self::assertSame(1, $this->wishlist->countItems());
        $item = $this->wishlist->getItemForVariant($variant);
        self::assertNotNull($item);
        self::assertSame($variant, $item->getVariant());
        self::assertSame(3, $item->getQuantity());
        self::assertSame(WishlistItemInterface::SOURCE_PLP, $item->getSource());
        self::assertSame($this->now, $item->getAddedAt());
        self::assertSame($this->wishlist, $item->getWishlist());
        self::assertSame($this->now, $this->wishlist->getUpdatedAt());
    }

    #[Test]
    public function it_does_not_add_a_duplicate_variant(): void
    {
        $variant = $this->createStub(ProductVariantInterface::class);
        $this->modifier->addVariant($this->wishlist, $variant, 2);

        self::assertFalse($this->modifier->addVariant($this->wishlist, $variant, 5));

        self::assertSame(1, $this->wishlist->countItems());
        self::assertSame(2, $this->wishlist->getItemForVariant($variant)?->getQuantity());
    }

    #[Test]
    public function it_removes_a_variant(): void
    {
        $variant = $this->createStub(ProductVariantInterface::class);
        $this->modifier->addVariant($this->wishlist, $variant);
        $item = $this->wishlist->getItemForVariant($variant);

        $this->now = new \DateTimeImmutable('2026-03-16 09:00:00');
        $this->modifier->removeVariant($this->wishlist, $variant);

        self::assertSame(0, $this->wishlist->countItems());
        self::assertNull($item?->getWishlist());
        self::assertSame($this->now, $this->wishlist->getUpdatedAt());
    }

    #[Test]
    public function removing_a_missing_variant_does_nothing(): void
    {
        $this->modifier->removeVariant($this->wishlist, $this->createStub(ProductVariantInterface::class));

        self::assertSame(0, $this->wishlist->countItems());
        self::assertNull($this->wishlist->getUpdatedAt());
    }

    #[Test]
    public function it_changes_the_quantity(): void
    {
        $variant = $this->createStub(ProductVariantInterface::class);
        $this->modifier->addVariant($this->wishlist, $variant);
        $item = $this->wishlist->getItemForVariant($variant);
        self::assertNotNull($item);

        $this->now = new \DateTimeImmutable('2026-03-17 08:00:00');
        $this->modifier->changeQuantity($item, 4);

        self::assertSame(4, $item->getQuantity());
        self::assertSame($this->now, $this->wishlist->getUpdatedAt());
    }

    #[Test]
    public function adding_with_quantity_below_one_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        try {
            $this->modifier->addVariant($this->wishlist, $this->createStub(ProductVariantInterface::class), 0);
        } finally {
            self::assertSame(0, $this->wishlist->countItems());
        }
    }

    #[Test]
    public function changing_quantity_below_one_throws(): void
    {
        $variant = $this->createStub(ProductVariantInterface::class);
        $this->modifier->addVariant($this->wishlist, $variant, 2);
        $item = $this->wishlist->getItemForVariant($variant);
        self::assertNotNull($item);

        $this->expectException(\InvalidArgumentException::class);

        try {
            $this->modifier->changeQuantity($item, 0);
        } finally {
            self::assertSame(2, $item->getQuantity());
        }
    }

    #[Test]
    public function adding_with_unknown_source_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        try {
            $this->modifier->addVariant($this->wishlist, $this->createStub(ProductVariantInterface::class), 1, 'newsletter');
        } finally {
            self::assertSame(0, $this->wishlist->countItems());
        }
    }

    private function wishlistInChannel(): void
    {
        $currency = $this->createStub(CurrencyInterface::class);
        $currency->method('getCode')->willReturn('EUR');
        $channel = $this->createStub(ChannelInterface::class);
        $channel->method('getBaseCurrency')->willReturn($currency);
        $this->wishlist->setChannel($channel);
    }

    #[Test]
    public function it_records_the_channel_price_and_currency_when_saving(): void
    {
        $this->wishlistInChannel();
        $this->calculator->method('calculate')->willReturn(1990);
        $variant = $this->createStub(ProductVariantInterface::class);

        $this->modifier->addVariant($this->wishlist, $variant);

        $item = $this->wishlist->getItemForVariant($variant);
        self::assertSame(1990, $item?->getPriceAtAdd());
        self::assertSame('EUR', $item?->getCurrencyCode());
    }

    #[Test]
    public function it_leaves_the_price_null_when_the_channel_has_none(): void
    {
        $this->wishlistInChannel();
        $this->calculator->method('calculate')->willThrowException(new MissingChannelConfigurationException('no price'));
        $variant = $this->createStub(ProductVariantInterface::class);

        self::assertTrue($this->modifier->addVariant($this->wishlist, $variant));

        $item = $this->wishlist->getItemForVariant($variant);
        self::assertNull($item?->getPriceAtAdd());
        self::assertNull($item?->getCurrencyCode());
    }

    #[Test]
    public function changing_the_quantity_does_not_touch_the_price(): void
    {
        $this->wishlistInChannel();
        $this->calculator->method('calculate')->willReturn(1990);
        $variant = $this->createStub(ProductVariantInterface::class);
        $this->modifier->addVariant($this->wishlist, $variant);
        $item = $this->wishlist->getItemForVariant($variant);

        $this->modifier->changeQuantity($item, 4);

        self::assertSame(1990, $item->getPriceAtAdd());
    }
}
