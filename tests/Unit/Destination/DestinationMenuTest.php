<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\Tests\Unit\Destination;

use Bwlab\SyliusWishlistPlugin\Destination\DestinationMenu;
use Bwlab\SyliusWishlistPlugin\Entity\Wishlist;
use Bwlab\SyliusWishlistPlugin\Entity\WishlistInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class DestinationMenuTest extends TestCase
{
    private static function list(int $id, string $name, bool $default = false): Wishlist
    {
        $w = new Wishlist();
        (new \ReflectionProperty(Wishlist::class, 'id'))->setValue($w, $id);
        $w->setName($name);
        $w->setDefault($default);

        return $w;
    }

    #[Test]
    public function no_menu_with_zero_or_one_list(): void
    {
        self::assertSame([], DestinationMenu::build([], null, 'Fav'));
        self::assertSame([], DestinationMenu::build([self::list(1, WishlistInterface::DEFAULT_NAME, true)], 1, 'Fav'));
    }

    #[Test]
    public function default_is_shown_translated_and_the_last_used_is_selected(): void
    {
        $menu = DestinationMenu::build([
            self::list(1, WishlistInterface::DEFAULT_NAME, true),
            self::list(2, 'Compleanno'),
        ], 2, 'I miei preferiti');

        self::assertSame([
            ['id' => 1, 'name' => 'I miei preferiti', 'selected' => false],
            ['id' => 2, 'name' => 'Compleanno', 'selected' => true],
        ], $menu);
    }

    #[Test]
    public function a_renamed_default_keeps_its_own_name(): void
    {
        self::assertSame('Casa', DestinationMenu::label(self::list(1, 'Casa', true), 'I miei preferiti'));
        // A normal list that happens to be called like the placeholder is not translated.
        self::assertSame(WishlistInterface::DEFAULT_NAME, DestinationMenu::label(self::list(2, WishlistInterface::DEFAULT_NAME), 'I miei preferiti'));
    }

    #[Test]
    public function total_sums_the_rows_of_every_list(): void
    {
        self::assertSame(0, DestinationMenu::totalItems([]));
        self::assertSame(0, DestinationMenu::totalItems([self::list(1, 'A', true), self::list(2, 'B')]));
    }
}
