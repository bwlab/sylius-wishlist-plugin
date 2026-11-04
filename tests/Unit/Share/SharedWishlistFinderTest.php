<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\Tests\Unit\Share;

use Bwlab\SyliusWishlistPlugin\Entity\Wishlist;
use Bwlab\SyliusWishlistPlugin\Repository\WishlistRepositoryInterface;
use Bwlab\SyliusWishlistPlugin\Share\SharedWishlistFinder;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;

final class SharedWishlistFinderTest extends TestCase
{
    private ChannelInterface $channel;

    protected function setUp(): void
    {
        $this->channel = $this->createStub(ChannelInterface::class);
    }

    private function shared(?ChannelInterface $channel = null, bool $customer = true): Wishlist
    {
        $list = new Wishlist();
        $list->setChannel($channel ?? $this->channel);
        $list->setCustomer($customer ? $this->createStub(CustomerInterface::class) : null);
        $list->enableSharing();

        return $list;
    }

    private function finder(?Wishlist $found): SharedWishlistFinder
    {
        $repository = $this->createStub(WishlistRepositoryInterface::class);
        $repository->method('findOneByShareToken')->willReturn($found);

        return new SharedWishlistFinder($repository);
    }

    #[Test]
    public function a_shared_list_of_the_current_channel_is_found(): void
    {
        $list = $this->shared();

        self::assertSame($list, $this->finder($list)->find((string) $list->getShareToken(), $this->channel));
    }

    #[Test]
    public function every_reason_for_not_showing_gives_null(): void
    {
        $token = str_repeat('a', 64);
        $off = $this->shared();
        $off->disableSharing();

        self::assertNull($this->finder(null)->find($token, $this->channel), 'unknown');
        self::assertNull($this->finder($off)->find($token, $this->channel), 'link off');
        self::assertNull($this->finder($this->shared(self::createStub(ChannelInterface::class)))->find($token, $this->channel), 'other channel');
        self::assertNull($this->finder($this->shared(null, false))->find($token, $this->channel), 'guest list');
    }

    #[Test]
    public function a_malformed_token_does_not_even_reach_the_repository(): void
    {
        $repository = $this->createMock(WishlistRepositoryInterface::class);
        $repository->expects(self::never())->method('findOneByShareToken');
        $finder = new SharedWishlistFinder($repository);

        foreach (['', 'abc', str_repeat('A', 64), str_repeat('a', 63), str_repeat('a', 65), "../../etc/passwd"] as $bad) {
            self::assertNull($finder->find($bad, $this->channel));
        }
    }
}
