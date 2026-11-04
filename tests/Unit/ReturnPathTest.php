<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\Tests\Unit;

use Bwlab\SyliusWishlistPlugin\Http\ReturnPath;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ReturnPathTest extends TestCase
{
    #[DataProvider('cases')]
    public function testSanitize(mixed $input, string $expected): void
    {
        self::assertSame($expected, ReturnPath::sanitize($input, '/f'));
    }

    /** @return iterable<string, array{mixed, string}> */
    public static function cases(): iterable
    {
        yield 'local path' => ['/en_US/products/a?x=1', '/en_US/products/a?x=1'];
        yield 'external' => ['https://evil.test', '/f'];
        yield 'protocol relative' => ['//evil.test', '/f'];
        yield 'backslash' => ['/\\evil.test', '/f'];
        yield 'newline' => ["/a\nb", '/f'];
        yield 'null' => [null, '/f'];
    }
}
