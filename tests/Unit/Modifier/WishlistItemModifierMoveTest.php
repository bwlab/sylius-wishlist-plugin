<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\Tests\Unit\Modifier;

use Bwlab\SyliusWishlistPlugin\Entity\Wishlist;
use Bwlab\SyliusWishlistPlugin\Entity\WishlistItem;
use Bwlab\SyliusWishlistPlugin\Exception\WishlistOperationException;
use Bwlab\SyliusWishlistPlugin\Modifier\WishlistItemModifier;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Resource\Factory\FactoryInterface;

final class WishlistItemModifierMoveTest extends TestCase
{
    private function modifier(): WishlistItemModifier
    {
        return new WishlistItemModifier($this->createStub(FactoryInterface::class), fn () => new \DateTimeImmutable('2026-11-04 12:00'));
    }

    private static function item(Wishlist $w, ProductVariantInterface $v, int $qty, string $addedAt): WishlistItem
    {
        $i = new WishlistItem();
        $i->setVariant($v);
        $i->setQuantity($qty);
        $i->setAddedAt(new \DateTimeImmutable($addedAt));
        $w->addItem($i);

        return $i;
    }

    #[Test]
    public function the_row_moves_keeping_added_at(): void
    {
        $from = new Wishlist();
        $to = new Wishlist();
        $item = self::item($from, $this->createStub(ProductVariantInterface::class), 2, '2026-01-01');

        self::assertSame($item, $this->modifier()->moveItem($item, $to));
        self::assertSame(0, $from->countItems());
        self::assertSame($to, $item->getWishlist());
        self::assertEquals(new \DateTimeImmutable('2026-01-01'), $item->getAddedAt());
    }

    #[Test]
    public function a_duplicate_in_the_target_keeps_greater_quantity_and_oldest_date(): void
    {
        $v = $this->createStub(ProductVariantInterface::class);
        $from = new Wishlist();
        $to = new Wishlist();
        $moved = self::item($from, $v, 5, '2026-03-01');
        $kept = self::item($to, $v, 2, '2026-01-01');

        self::assertSame($kept, $this->modifier()->moveItem($moved, $to));
        self::assertSame(0, $from->countItems());
        self::assertSame(1, $to->countItems());
        self::assertSame(5, $kept->getQuantity());
        self::assertEquals(new \DateTimeImmutable('2026-01-01'), $kept->getAddedAt());
    }

    #[Test]
    public function moving_into_the_same_list_throws(): void
    {
        $w = new Wishlist();
        $item = self::item($w, $this->createStub(ProductVariantInterface::class), 1, '2026-01-01');

        $this->expectException(WishlistOperationException::class);
        $this->modifier()->moveItem($item, $w);
    }
}
