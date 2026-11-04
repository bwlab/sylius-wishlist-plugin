<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\Tests\Unit\Api;

use Bwlab\SyliusWishlistPlugin\Api\Security\CurrentWishlistItemFinder;
use Bwlab\SyliusWishlistPlugin\Entity\Wishlist;
use Bwlab\SyliusWishlistPlugin\Entity\WishlistItem;
use Bwlab\SyliusWishlistPlugin\Resolver\CurrentWishlistResolverInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/** Authorization: a row is reachable only through the wishlist of the CURRENT owner. */
final class CurrentWishlistItemFinderTest extends TestCase
{
    private function item(int $id): WishlistItem
    {
        $item = new WishlistItem();
        (new \ReflectionProperty($item, 'id'))->setValue($item, $id);

        return $item;
    }

    private function finderFor(?Wishlist $current): CurrentWishlistItemFinder
    {
        $resolver = $this->createStub(CurrentWishlistResolverInterface::class);
        $resolver->method('find')->willReturn($current);

        return new CurrentWishlistItemFinder($resolver);
    }

    public function testFindsOwnRow(): void
    {
        $mine = new Wishlist();
        $mine->addItem($row = $this->item(7));

        self::assertSame($row, $this->finderFor($mine)->find(7));
    }

    public function testRowOfAnotherWishlistIsNotFound(): void
    {
        $mine = new Wishlist();
        $mine->addItem($this->item(7));
        $theirs = new Wishlist();
        $theirs->addItem($this->item(8));

        $this->expectException(NotFoundHttpException::class);
        $this->finderFor($mine)->find(8);
    }

    public function testNoWishlistMeansNotFound(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->finderFor(null)->find(1);
    }
}
