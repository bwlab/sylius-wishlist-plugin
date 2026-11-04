<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\Tests\Unit\Admin;

use Bwlab\SyliusWishlistPlugin\Admin\Customer\CustomerWishlistGrouper;
use Bwlab\SyliusWishlistPlugin\Checker\VariantAvailabilityCheckerInterface;
use Bwlab\SyliusWishlistPlugin\Checker\WishlistAvailability;
use Bwlab\SyliusWishlistPlugin\Entity\Wishlist;
use Bwlab\SyliusWishlistPlugin\Entity\WishlistItem;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;

final class CustomerWishlistGrouperTest extends TestCase
{
    private function grouper(WishlistAvailability $availability = WishlistAvailability::Available): CustomerWishlistGrouper
    {
        $checker = $this->createStub(VariantAvailabilityCheckerInterface::class);
        $checker->method('check')->willReturn($availability);

        return new CustomerWishlistGrouper($checker, $this->createStub(\Sylius\Component\Core\Calculator\ProductVariantPricesCalculatorInterface::class));
    }

    private function channel(string $code, string $name): ChannelInterface
    {
        $channel = $this->createStub(ChannelInterface::class);
        $channel->method('getCode')->willReturn($code);
        $channel->method('getName')->willReturn($name);

        return $channel;
    }

    private function list(int $id, ChannelInterface $channel, string $name, bool $default, string $created): Wishlist
    {
        $w = new Wishlist();
        (new \ReflectionProperty(Wishlist::class, 'id'))->setValue($w, $id);
        $w->setName($name);
        $w->setDefault($default);
        $w->setChannel($channel);
        $w->setCreatedAt(new \DateTimeImmutable($created));

        return $w;
    }

    private function item(Wishlist $w, int $id, string $added, bool $withVariant = true): void
    {
        $i = new WishlistItem();
        (new \ReflectionProperty(WishlistItem::class, 'id'))->setValue($i, $id);
        $i->setAddedAt(new \DateTimeImmutable($added));
        if ($withVariant) {
            $i->setVariant($this->createStub(ProductVariantInterface::class));
        }
        $w->addItem($i);
    }

    #[Test]
    public function groups_by_channel_sorted_by_name_default_first_then_creation(): void
    {
        $b = $this->channel('B', 'Beta');
        $a = $this->channel('A', 'alpha');
        $groups = $this->grouper()->group([
            $this->list(1, $b, 'Zeta', false, '2026-01-02'),
            $this->list(2, $a, 'Second', false, '2026-01-03'),
            $this->list(3, $b, 'Favs', true, '2026-01-05'),
            $this->list(4, $a, 'First', false, '2026-01-01'),
            $this->list(5, $b, 'Early', false, '2026-01-01'),
        ]);

        self::assertSame(['A', 'B'], array_map(static fn ($g) => $g->channel->getCode(), $groups));
        self::assertSame([4, 2], array_map(static fn ($v) => $v->wishlist->getId(), $groups[0]->wishlists));
        self::assertSame([3, 5, 1], array_map(static fn ($v) => $v->wishlist->getId(), $groups[1]->wishlists));
    }

    #[Test]
    public function rows_are_newest_first_and_carry_availability_and_rows_without_variant_are_skipped(): void
    {
        $w = $this->list(1, $this->channel('A', 'A'), 'X', true, '2026-01-01');
        $this->item($w, 10, '2026-02-01');
        $this->item($w, 11, '2026-03-01');
        $this->item($w, 12, '2026-04-01', false);

        $rows = $this->grouper(WishlistAvailability::OutOfStock)->group([$w])[0]->wishlists[0]->items;

        self::assertSame([11, 10], array_map(static fn ($r) => $r->item->getId(), $rows));
        self::assertSame(WishlistAvailability::OutOfStock, $rows[0]->availability);
    }

    #[Test]
    public function no_lists_gives_no_groups(): void
    {
        self::assertSame([], $this->grouper()->group([]));
    }
}
