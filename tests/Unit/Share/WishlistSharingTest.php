<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\Tests\Unit\Share;

use Bwlab\SyliusWishlistPlugin\Entity\Wishlist;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class WishlistSharingTest extends TestCase
{
    #[Test]
    public function a_new_list_is_not_shared(): void
    {
        $list = new Wishlist();

        self::assertFalse($list->isShared());
        self::assertNull($list->getShareToken());
        self::assertNull($list->getSharedAt());
    }

    #[Test]
    public function turning_the_link_on_gives_a_64_hex_token_different_from_the_owner_token(): void
    {
        $list = new Wishlist();
        $list->enableSharing();

        self::assertMatchesRegularExpression('/^[0-9a-f]{64}$/', (string) $list->getShareToken());
        self::assertNotSame($list->getToken(), $list->getShareToken());
        self::assertNotNull($list->getSharedAt());
    }

    #[Test]
    public function turning_it_on_twice_keeps_the_same_link(): void
    {
        $list = new Wishlist();
        $list->enableSharing();
        $first = $list->getShareToken();
        $list->enableSharing();

        self::assertSame($first, $list->getShareToken());
    }

    #[Test]
    public function renew_replaces_the_token(): void
    {
        $list = new Wishlist();
        $list->enableSharing();
        $old = $list->getShareToken();
        $list->renewShareToken();

        self::assertNotSame($old, $list->getShareToken());
        self::assertTrue($list->isShared());
    }

    #[Test]
    public function turning_it_off_forgets_the_token_and_the_next_link_is_new(): void
    {
        $list = new Wishlist();
        $list->enableSharing();
        $old = $list->getShareToken();
        $list->disableSharing();

        self::assertFalse($list->isShared());
        self::assertNull($list->getShareToken());
        $list->enableSharing();
        self::assertNotSame($old, $list->getShareToken());
    }

    #[Test]
    public function tokens_do_not_repeat(): void
    {
        $tokens = [];
        for ($i = 0; $i < 200; ++$i) {
            $list = new Wishlist();
            $list->enableSharing();
            $tokens[] = $list->getShareToken();
        }

        self::assertCount(200, array_unique($tokens));
    }
}
