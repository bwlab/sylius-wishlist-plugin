<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\Tests\Unit\Twig;

use Bwlab\SyliusWishlistPlugin\Entity\WishlistInterface;
use Bwlab\SyliusWishlistPlugin\Resolver\CurrentWishlistResolverInterface;
use Bwlab\SyliusWishlistPlugin\Twig\WishlistExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

final class WishlistExtensionTest extends TestCase
{
    public function testCountSumsRowsOfAllLists(): void
    {
        $resolver = $this->createStub(CurrentWishlistResolverInterface::class);
        $resolver->method('findAll')->willReturn([$this->list('A', true, 2), $this->list('B', false, 3)]);

        self::assertSame(5, (new WishlistExtension($resolver, $this->translator()))->count());
    }

    public function testCountIsZeroWithoutLists(): void
    {
        $resolver = $this->createStub(CurrentWishlistResolverInterface::class);
        $resolver->method('findAll')->willReturn([]);

        self::assertSame(0, (new WishlistExtension($resolver, $this->translator()))->count());
    }

    public function testDefaultListNameIsTranslatedOthersAreNot(): void
    {
        $extension = new WishlistExtension($this->createStub(CurrentWishlistResolverInterface::class), $this->translator());

        self::assertSame('[bwlab_wishlist.ui.shop.default_list_name]', $extension->listName($this->list(WishlistInterface::DEFAULT_NAME, true, 0)));
        self::assertSame('Birthday', $extension->listName($this->list('Birthday', false, 0)));
        // a renamed default list keeps the user's name; a normal list named like the default is not translated
        self::assertSame('Mine', $extension->listName($this->list('Mine', true, 0)));
        self::assertSame('My Favorites', $extension->listName($this->list('My Favorites', false, 0)));
    }

    private function list(string $name, bool $default, int $count): WishlistInterface
    {
        $list = $this->createStub(WishlistInterface::class);
        $list->method('getName')->willReturn($name);
        $list->method('isDefault')->willReturn($default);
        $list->method('countItems')->willReturn($count);

        return $list;
    }

    private function translator(): TranslatorInterface
    {
        $translator = $this->createStub(TranslatorInterface::class);
        $translator->method('trans')->willReturnCallback(static fn (string $id): string => '['.$id.']');

        return $translator;
    }
}
