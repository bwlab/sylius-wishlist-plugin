<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\Tests\Unit\Purge;

use Bwlab\SyliusWishlistPlugin\Purge\ExpiredGuestWishlistFinder;
use Bwlab\SyliusWishlistPlugin\Repository\WishlistRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Core\Model\ChannelInterface;

final class ExpiredGuestWishlistFinderTest extends TestCase
{
    #[Test]
    public function a_guest_is_expired_only_when_it_was_neither_seen_nor_changed_after_the_cutoff(): void
    {
        $having = null;
        $query = $this->createMock(Query::class);
        $query->method('getSingleColumnResult')->willReturn([]);
        $builder = $this->createMock(QueryBuilder::class);
        foreach (['select', 'andWhere', 'groupBy', 'setParameter'] as $method) {
            $builder->method($method)->willReturnSelf();
        }
        $builder->method('having')->willReturnCallback(function (string $dql) use (&$having, $builder) {
            $having = $dql;

            return $builder;
        });
        $builder->method('getQuery')->willReturn($query);
        $repository = $this->createMock(WishlistRepository::class);
        $repository->method('createQueryBuilder')->willReturn($builder);

        (new ExpiredGuestWishlistFinder($repository))->find($this->createMock(ChannelInterface::class), new \DateTimeImmutable());

        // Removing either condition would delete lists of guests who still use them.
        self::assertStringContainsString('lastSeenAt', (string) $having);
        self::assertStringContainsString('updatedAt', (string) $having);
    }
}
