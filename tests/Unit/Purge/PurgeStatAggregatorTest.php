<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\Tests\Unit\Purge;

use Bwlab\SyliusWishlistPlugin\Entity\Wishlist;
use Bwlab\SyliusWishlistPlugin\Entity\WishlistItem;
use Bwlab\SyliusWishlistPlugin\Purge\PurgeStatAggregator;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Core\Model\ProductVariantInterface;

final class PurgeStatAggregatorTest extends TestCase
{
    #[Test]
    public function sums_items_and_quantities_per_variant_across_wishlists(): void
    {
        $a = $this->variant(1);
        $b = $this->variant(2);

        $result = PurgeStatAggregator::aggregate([
            $this->wishlist([[$a, 2], [$b, 1]]),
            $this->wishlist([[$a, 3]]),
        ]);

        self::assertSame([1, 2], array_keys($result));
        self::assertSame(2, $result[1]['itemsCount']);
        self::assertSame(5, $result[1]['quantitySum']);
        self::assertSame(1, $result[2]['itemsCount']);
        self::assertSame(1, $result[2]['quantitySum']);
        self::assertSame($a, $result[1]['variant']);
    }

    #[Test]
    public function empty_input_gives_no_rows(): void
    {
        self::assertSame([], PurgeStatAggregator::aggregate([]));
        self::assertSame([], PurgeStatAggregator::aggregate([new Wishlist()]));
    }

    /** @param list<array{ProductVariantInterface, int}> $rows */
    public static function wishlist(array $rows): Wishlist
    {
        $wishlist = new Wishlist();
        foreach ($rows as [$variant, $quantity]) {
            $item = new WishlistItem();
            $item->setVariant($variant);
            $item->setQuantity($quantity);
            $wishlist->addItem($item);
        }

        return $wishlist;
    }

    public static function variant(int $id): ProductVariantInterface
    {
        $variant = (new self('x'))->createStub(ProductVariantInterface::class);
        $variant->method('getId')->willReturn($id);

        return $variant;
    }
}
