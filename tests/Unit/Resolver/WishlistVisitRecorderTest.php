<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\Tests\Unit\Resolver;

use Bwlab\SyliusWishlistPlugin\Entity\Wishlist;
use Bwlab\SyliusWishlistPlugin\Resolver\WishlistVisitRecorder;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class WishlistVisitRecorderTest extends TestCase
{
    private Wishlist $wishlist;

    private \DateTimeImmutable $lastSeenAt;

    protected function setUp(): void
    {
        $this->lastSeenAt = new \DateTimeImmutable('2026-03-15 08:00:00');
        $this->wishlist = new Wishlist();
        $this->wishlist->setLastSeenAt($this->lastSeenAt);
        $this->wishlist->setLocaleCode('it_IT');
    }

    #[Test]
    public function same_day_and_same_locale_needs_no_write(): void
    {
        self::assertFalse(WishlistVisitRecorder::record($this->wishlist, new \DateTimeImmutable('2026-03-15 23:00:00'), 'it_IT'));

        self::assertSame($this->lastSeenAt, $this->wishlist->getLastSeenAt());
        self::assertSame('it_IT', $this->wishlist->getLocaleCode());
    }

    #[Test]
    public function new_day_updates_last_seen_at(): void
    {
        $now = new \DateTimeImmutable('2026-03-16 00:05:00');

        self::assertTrue(WishlistVisitRecorder::record($this->wishlist, $now, 'it_IT'));

        self::assertSame($now, $this->wishlist->getLastSeenAt());
    }

    #[Test]
    public function locale_change_needs_a_write(): void
    {
        self::assertTrue(WishlistVisitRecorder::record($this->wishlist, new \DateTimeImmutable('2026-03-15 12:00:00'), 'en_US'));

        self::assertSame('en_US', $this->wishlist->getLocaleCode());
        self::assertSame($this->lastSeenAt, $this->wishlist->getLastSeenAt());
    }

    #[Test]
    public function null_locale_does_not_overwrite(): void
    {
        self::assertFalse(WishlistVisitRecorder::record($this->wishlist, new \DateTimeImmutable('2026-03-15 12:00:00'), null));

        self::assertSame('it_IT', $this->wishlist->getLocaleCode());
    }

    #[Test]
    public function missing_last_seen_at_is_recorded(): void
    {
        $this->wishlist->setLastSeenAt(null);
        $now = new \DateTimeImmutable('2026-03-15 12:00:00');

        self::assertTrue(WishlistVisitRecorder::record($this->wishlist, $now, 'it_IT'));

        self::assertSame($now, $this->wishlist->getLastSeenAt());
    }
}
