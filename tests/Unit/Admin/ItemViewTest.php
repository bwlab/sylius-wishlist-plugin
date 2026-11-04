<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\Tests\Unit\Admin;

use Bwlab\SyliusWishlistPlugin\Admin\Customer\ItemView;
use Bwlab\SyliusWishlistPlugin\Checker\WishlistAvailability;
use Bwlab\SyliusWishlistPlugin\Entity\WishlistItem;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ItemViewTest extends TestCase
{
    /** @return iterable<string, array{?int, ?string, ?int, ?int}> */
    public static function cases(): iterable
    {
        yield 'dropped' => [1000, 'EUR', 800, -1];
        yield 'rose' => [1000, 'EUR', 1200, 1];
        yield 'same' => [1000, 'EUR', 1000, 0];
        yield 'no price at save' => [null, null, 800, null];
        yield 'no current price' => [1000, 'EUR', null, null];
        yield 'other currency' => [1000, 'USD', 800, null];
    }

    #[Test]
    #[DataProvider('cases')]
    public function it_compares_price_at_save_with_current(?int $before, ?string $currency, ?int $now, ?int $expected): void
    {
        $item = new WishlistItem();
        $item->setPriceAtAdd($before, $currency);

        self::assertSame($expected, (new ItemView($item, WishlistAvailability::Available, $now))->priceTrend('EUR'));
    }
}
