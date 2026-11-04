<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\Tests\Unit\Policy;

use Bwlab\SyliusWishlistPlugin\Entity\Wishlist;
use Bwlab\SyliusWishlistPlugin\Exception\WishlistNameException;
use Bwlab\SyliusWishlistPlugin\Policy\WishlistNamePolicy;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class WishlistNamePolicyTest extends TestCase
{
    private static function list(string $name): Wishlist
    {
        $w = new Wishlist();
        $w->setName($name);

        return $w;
    }

    private static function errorOf(callable $fn): ?string
    {
        try {
            $fn();
        } catch (WishlistNameException $e) {
            return $e->getMessage();
        }

        return null;
    }

    #[Test]
    public function it_trims_and_accepts_a_valid_name(): void
    {
        self::assertSame('Birthday', WishlistNamePolicy::check('  Birthday ', [self::list('My Favorites')]));
    }

    #[Test]
    public function it_rejects_blank_too_long_duplicate_and_too_many(): void
    {
        self::assertSame(WishlistNameException::BLANK, self::errorOf(fn () => WishlistNamePolicy::check("  \t ", [])));
        self::assertSame(WishlistNameException::TOO_LONG, self::errorOf(fn () => WishlistNamePolicy::check(str_repeat('é', 61), [])));
        self::assertNull(self::errorOf(fn () => WishlistNamePolicy::check(str_repeat('é', 60), [])));
        self::assertSame(WishlistNameException::DUPLICATE, self::errorOf(fn () => WishlistNamePolicy::check('BIRTHDAY ', [self::list('Birthday')])));
        $twenty = array_map(fn (int $i) => self::list('L' . $i), range(1, 20));
        self::assertSame(WishlistNameException::MAX_LISTS, self::errorOf(fn () => WishlistNamePolicy::check('New', $twenty)));
    }

    #[Test]
    public function renaming_ignores_the_list_itself_and_the_maximum(): void
    {
        $birthday = self::list('Birthday');
        $twenty = array_merge([$birthday], array_map(fn (int $i) => self::list('L' . $i), range(1, 19)));

        self::assertSame('birthday', WishlistNamePolicy::check('birthday', $twenty, $birthday));
        self::assertSame(WishlistNameException::DUPLICATE, self::errorOf(fn () => WishlistNamePolicy::check('l1', $twenty, $birthday)));
    }
}
