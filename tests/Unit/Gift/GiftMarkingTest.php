<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\Tests\Unit\Gift;

use Bwlab\SyliusWishlistPlugin\Entity\Wishlist;
use Bwlab\SyliusWishlistPlugin\Entity\WishlistItem;
use Bwlab\SyliusWishlistPlugin\Gift\GiftSelector;
use Bwlab\SyliusWishlistPlugin\Gift\GiftSession;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

final class GiftMarkingTest extends TestCase
{
    private function item(int $variantId, bool $shared = true): WishlistItem
    {
        $variant = $this->createStub(ProductVariantInterface::class);
        $variant->method('getId')->willReturn($variantId);
        $list = new Wishlist();
        if ($shared) {
            $list->enableSharing();
        }
        $item = new WishlistItem();
        $item->setVariant($variant);
        $list->addItem($item);

        return $item;
    }

    #[Test]
    public function only_rows_of_a_shared_list_whose_variant_is_in_the_order_are_selected(): void
    {
        $inOrder = $this->item(10);
        $notInOrder = $this->item(11);
        $linkOff = $this->item(10, false);

        self::assertSame([$inOrder], GiftSelector::select([$inOrder, $notInOrder, $linkOff], [10, 99]));
        self::assertSame([], GiftSelector::select([$inOrder], []));
    }

    #[Test]
    public function the_session_remembers_row_ids_without_repeats_and_forgets_them(): void
    {
        $session = new Session(new MockArraySessionStorage());
        GiftSession::remember($session, 5);
        GiftSession::remember($session, 7);
        GiftSession::remember($session, 5);
        self::assertSame([5, 7], GiftSession::ids($session));

        GiftSession::forget($session, [5]);
        self::assertSame([7], GiftSession::ids($session));
    }

    #[Test]
    public function the_session_keeps_at_most_50_ids(): void
    {
        $session = new Session(new MockArraySessionStorage());
        for ($i = 1; $i <= 60; ++$i) {
            GiftSession::remember($session, $i);
        }

        self::assertCount(50, GiftSession::ids($session));
        self::assertSame(60, GiftSession::ids($session)[49]);
    }
}
