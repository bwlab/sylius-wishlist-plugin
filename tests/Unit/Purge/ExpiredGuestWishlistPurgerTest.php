<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\Tests\Unit\Purge;

use Bwlab\SyliusWishlistPlugin\Entity\WishlistInterface;
use Bwlab\SyliusWishlistPlugin\Entity\WishlistPurgeStat;
use Bwlab\SyliusWishlistPlugin\Provider\GuestLifetimeProviderInterface;
use Bwlab\SyliusWishlistPlugin\Purge\ExpiredGuestWishlistFinderInterface;
use Bwlab\SyliusWishlistPlugin\Purge\ExpiredGuestWishlistPurger;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Channel\Repository\ChannelRepositoryInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Resource\Doctrine\Persistence\RepositoryInterface;
use Sylius\Resource\Factory\FactoryInterface;

final class ExpiredGuestWishlistPurgerTest extends TestCase
{
    private \DateTimeImmutable $now;

    private ChannelInterface $channel;

    private ExpiredGuestWishlistFinderInterface&MockObject $finder;

    private RepositoryInterface&MockObject $statRepository;

    private EntityManagerInterface&MockObject $em;

    private ExpiredGuestWishlistPurger $purger;

    protected function setUp(): void
    {
        $this->now = new \DateTimeImmutable('2026-11-04 17:00:00');
        $this->channel = $this->createStub(ChannelInterface::class);
        $this->channel->method('getCode')->willReturn('WEB');

        $channels = $this->createStub(ChannelRepositoryInterface::class);
        $channels->method('findAll')->willReturn([$this->channel]);
        $lifetime = $this->createStub(GuestLifetimeProviderInterface::class);
        $lifetime->method('getGuestLifetimeDays')->willReturn(30);
        $this->finder = $this->createMock(ExpiredGuestWishlistFinderInterface::class);
        $this->statRepository = $this->createMock(RepositoryInterface::class);
        $factory = $this->createStub(FactoryInterface::class);
        $factory->method('createNew')->willReturnCallback(static fn () => new WishlistPurgeStat());
        $this->em = $this->createMock(EntityManagerInterface::class);

        $this->purger = new ExpiredGuestWishlistPurger($channels, $lifetime, $this->finder, $this->statRepository, $factory, $this->em);
    }

    #[Test]
    public function uses_the_channel_lifetime_as_cutoff_and_deletes_after_summing(): void
    {
        $wishlist = PurgeStatAggregatorTest::wishlist([[PurgeStatAggregatorTest::variant(7), 4]]);
        $this->finder->expects(self::once())->method('find')
            ->with($this->channel, new \DateTimeImmutable('2026-10-05 17:00:00'))
            ->willReturn([$wishlist]);
        $this->statRepository->method('findOneBy')->willReturn(null);

        $persisted = [];
        $this->em->method('persist')->willReturnCallback(static function ($stat) use (&$persisted): void {
            $persisted[] = $stat;
        });
        $this->em->expects(self::once())->method('remove')->with($wishlist);
        $this->em->expects(self::once())->method('flush');

        $result = $this->purger->purge($this->now, false);

        self::assertSame(['WEB' => 1], $result->purgedByChannel);
        self::assertCount(1, $persisted);
        self::assertSame(1, $persisted[0]->getItemsCount());
        self::assertSame(4, $persisted[0]->getQuantitySum());
        self::assertEquals(new \DateTimeImmutable('2026-11-04 00:00:00'), $persisted[0]->getPurgedAt());
    }

    #[Test]
    public function adds_to_the_existing_counter_of_the_same_day(): void
    {
        $existing = new WishlistPurgeStat();
        $existing->setItemsCount(2);
        $existing->setQuantitySum(5);
        $this->finder->method('find')->willReturn([PurgeStatAggregatorTest::wishlist([[PurgeStatAggregatorTest::variant(7), 3]])]);
        $this->statRepository->method('findOneBy')->willReturn($existing);

        $this->purger->purge($this->now, false);

        self::assertSame(3, $existing->getItemsCount());
        self::assertSame(8, $existing->getQuantitySum());
    }

    #[Test]
    public function dry_run_counts_but_writes_nothing(): void
    {
        $this->finder->method('find')->willReturn([PurgeStatAggregatorTest::wishlist([[PurgeStatAggregatorTest::variant(7), 3]])]);
        $this->em->expects(self::never())->method('remove');
        $this->em->expects(self::never())->method('persist');
        $this->em->expects(self::never())->method('flush');

        $result = $this->purger->purge($this->now, true);

        self::assertSame(1, $result->total());
        self::assertTrue($result->dryRun);
    }

    #[Test]
    public function never_deletes_a_customer_wishlist_even_if_the_finder_returns_it(): void
    {
        $customerWishlist = PurgeStatAggregatorTest::wishlist([[PurgeStatAggregatorTest::variant(7), 3]]);
        $customerWishlist->setCustomer($this->createStub(CustomerInterface::class));
        $this->finder->method('find')->willReturn([$customerWishlist]);
        $this->em->expects(self::never())->method('remove');
        $this->em->expects(self::never())->method('persist');

        self::assertSame(0, $this->purger->purge($this->now, false)->total());
    }
}
