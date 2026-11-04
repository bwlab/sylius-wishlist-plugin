<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\Tests\Unit\Identity;

use Bwlab\SyliusWishlistPlugin\Entity\Wishlist;
use Bwlab\SyliusWishlistPlugin\Entity\WishlistItem;
use Bwlab\SyliusWishlistPlugin\Identity\WishlistMerger;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;

final class WishlistMergerTest extends TestCase
{
    private function item(Wishlist $w, ProductVariantInterface $v, int $qty, string $addedAt): WishlistItem
    {
        $i = new WishlistItem();
        $i->setVariant($v);
        $i->setQuantity($qty);
        $i->setAddedAt(new \DateTimeImmutable($addedAt));
        $w->addItem($i);

        return $i;
    }

    #[Test]
    public function without_customer_wishlist_the_guest_one_is_bound_to_the_customer(): void
    {
        $customer = $this->createStub(CustomerInterface::class);
        $guest = new Wishlist();
        $this->item($guest, $this->createStub(ProductVariantInterface::class), 2, '2026-01-01');

        $result = (new WishlistMerger())->merge($guest, null, $customer);

        self::assertSame($guest, $result);
        self::assertSame($customer, $guest->getCustomer());
        self::assertSame(1, $guest->countItems());
    }

    #[Test]
    public function items_move_and_duplicates_keep_greater_quantity_and_oldest_date(): void
    {
        $customer = $this->createStub(CustomerInterface::class);
        $a = $this->createStub(ProductVariantInterface::class);
        $b = $this->createStub(ProductVariantInterface::class);
        $guest = new Wishlist();
        $target = new Wishlist();
        $target->setCustomer($customer);
        $this->item($guest, $a, 5, '2026-01-01');
        $this->item($guest, $b, 1, '2026-02-01');
        $this->item($target, $a, 2, '2026-03-01');

        $result = (new WishlistMerger())->merge($guest, $target, $customer);

        self::assertSame($target, $result);
        self::assertSame(2, $target->countItems());
        self::assertSame(0, $guest->countItems());
        self::assertSame(5, $target->getItemForVariant($a)?->getQuantity());
        self::assertEquals(new \DateTimeImmutable('2026-01-01'), $target->getItemForVariant($a)?->getAddedAt());
        self::assertSame($target, $target->getItemForVariant($b)?->getWishlist());
    }

    #[Test]
    public function existing_greater_quantity_is_kept(): void
    {
        $customer = $this->createStub(CustomerInterface::class);
        $a = $this->createStub(ProductVariantInterface::class);
        $guest = new Wishlist();
        $target = new Wishlist();
        $this->item($guest, $a, 1, '2026-05-01');
        $this->item($target, $a, 4, '2026-03-01');

        (new WishlistMerger())->merge($guest, $target, $customer);

        self::assertSame(4, $target->getItemForVariant($a)?->getQuantity());
        self::assertEquals(new \DateTimeImmutable('2026-03-01'), $target->getItemForVariant($a)?->getAddedAt());
        self::assertSame(1, $target->countItems());
    }
}
