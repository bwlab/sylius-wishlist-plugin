<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\Tests\Unit\Resolver;

use Bwlab\SyliusWishlistPlugin\Entity\Wishlist;
use Bwlab\SyliusWishlistPlugin\Exception\WishlistOperationException;
use Bwlab\SyliusWishlistPlugin\Repository\WishlistRepositoryInterface;
use Bwlab\SyliusWishlistPlugin\Resolver\CurrentWishlistResolver;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Customer\Context\CustomerContextInterface;
use Sylius\Component\Locale\Context\LocaleContextInterface;
use Sylius\Resource\Factory\FactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

final class CurrentWishlistResolverListsTest extends TestCase
{
    /** @param list<Wishlist> $guestLists */
    private function resolver(WishlistRepositoryInterface $repo, ?string $cookie = 'tok'): CurrentWishlistResolver
    {
        $channels = $this->createStub(ChannelContextInterface::class);
        $channels->method('getChannel')->willReturn($this->createStub(ChannelInterface::class));
        $locale = $this->createStub(LocaleContextInterface::class);
        $locale->method('getLocaleCode')->willReturn('en_US');
        $factory = $this->createStub(FactoryInterface::class);
        $factory->method('createNew')->willReturnCallback(static fn () => new Wishlist());
        $stack = new RequestStack();
        $stack->push(Request::create('/', cookies: null === $cookie ? [] : ['bwlab_wishlist' => $cookie]));

        return new CurrentWishlistResolver($repo, $factory, $channels, $this->createStub(CustomerContextInterface::class), $locale, $stack, 'bwlab_wishlist');
    }

    private static function withId(Wishlist $w, int $id): Wishlist
    {
        (new \ReflectionProperty(Wishlist::class, 'id'))->setValue($w, $id);

        return $w;
    }

    #[Test]
    public function find_owned_only_returns_lists_of_the_current_owner(): void
    {
        $repo = $this->createStub(WishlistRepositoryInterface::class);
        $repo->method('findAllGuestByTokenAndChannel')->willReturn([self::withId(new Wishlist(), 7)]);

        self::assertSame(7, $this->resolver($repo)->findOwned(7)?->getId());
        self::assertNull($this->resolver($repo)->findOwned(8));
        self::assertSame([], $this->resolver($repo, null)->findAll());
    }

    #[Test]
    public function create_persists_a_new_default_first_and_shares_its_owner_token(): void
    {
        $saved = [];
        $repo = $this->createMock(WishlistRepositoryInterface::class);
        $repo->method('findAllGuestByTokenAndChannel')->willReturn([]);
        $repo->method('add')->willReturnCallback(static function ($w) use (&$saved): void { $saved[] = $w; });

        $list = $this->resolver($repo)->create(' Birthday ');

        self::assertCount(2, $saved);
        self::assertTrue($saved[0]->isDefault());
        self::assertSame('My Favorites', $saved[0]->getName());
        self::assertSame($list, $saved[1]);
        self::assertSame('Birthday', $list->getName());
        self::assertFalse($list->isDefault());
        self::assertSame($saved[0]->getToken(), $list->getToken());
    }

    #[Test]
    public function the_default_list_cannot_be_deleted(): void
    {
        $default = new Wishlist();
        $default->setDefault(true);
        $repo = $this->createMock(WishlistRepositoryInterface::class);
        $repo->expects(self::never())->method('remove');

        $this->expectException(WishlistOperationException::class);
        $this->resolver($repo)->delete($default);
    }
}
