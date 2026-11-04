<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\Tests\Unit\Api;

use Bwlab\SyliusWishlistPlugin\Api\Input\ListReference;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

final class ListReferenceTest extends TestCase
{
    /** @return iterable<string, array{int|string|null, ?int}> */
    public static function valid(): iterable
    {
        yield 'omitted = default list' => [null, null];
        yield 'empty string = default list' => ['', null];
        yield 'int id' => [5, 5];
        yield 'string id' => ['12', 12];
        yield 'iri' => ['/api/v2/shop/wishlists/7', 7];
    }

    #[DataProvider('valid')]
    public function testParse(int|string|null $value, ?int $expected): void
    {
        self::assertSame($expected, ListReference::parse($value));
    }

    #[DataProvider('invalid')]
    public function testInvalidIsRejected(int|string $value): void
    {
        $this->expectException(UnprocessableEntityHttpException::class);
        ListReference::parse($value);
    }

    /** @return iterable<string, array{int|string}> */
    public static function invalid(): iterable
    {
        yield 'zero' => [0];
        yield 'negative' => [-3];
        yield 'text' => ['abc'];
        yield 'iri of something else' => ['/api/v2/shop/orders/7'];
        yield 'iri with trailing path' => ['/api/v2/shop/wishlists/7/items'];
    }

    public function testFromRequestReadsTheQueryParameter(): void
    {
        self::assertSame(4, ListReference::fromRequest(new Request(['list' => '4'])));
        self::assertNull(ListReference::fromRequest(new Request()));
        self::assertNull(ListReference::fromRequest(null));
    }
}
