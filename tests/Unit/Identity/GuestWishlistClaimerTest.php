<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\Tests\Unit\Identity;

use Bwlab\SyliusWishlistPlugin\Entity\Wishlist;
use Bwlab\SyliusWishlistPlugin\Identity\GuestWishlistClaimer;
use Bwlab\SyliusWishlistPlugin\Identity\WishlistMerger;
use Bwlab\SyliusWishlistPlugin\Repository\WishlistRepositoryInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

final class GuestWishlistClaimerTest extends TestCase
{
    private function claimer(WishlistRepositoryInterface $repo, Request $request): GuestWishlistClaimer
    {
        $channel = $this->createStub(ChannelInterface::class);
        $channels = $this->createStub(ChannelContextInterface::class);
        $channels->method('getChannel')->willReturn($channel);
        $stack = new RequestStack();
        $stack->push($request);

        return new GuestWishlistClaimer($repo, new WishlistMerger(), $channels, $stack, 'bwlab_wishlist');
    }

    private static function defaultList(): Wishlist
    {
        $wishlist = new Wishlist();
        $wishlist->setDefault(true);

        return $wishlist;
    }

    #[Test]
    public function a_failing_save_keeps_the_cookie_and_does_not_throw(): void
    {
        $repo = $this->createStub(WishlistRepositoryInterface::class);
        $repo->method('findAllGuestByTokenAndChannel')->willReturn([self::defaultList()]);
        $repo->method('add')->willThrowException(new \RuntimeException('unique violation'));
        $request = Request::create('/', cookies: ['bwlab_wishlist' => 'tok']);

        $this->claimer($repo, $request)->claim($this->createStub(CustomerInterface::class));

        self::assertFalse($request->attributes->has(GuestWishlistClaimer::CLEAR_COOKIE_ATTRIBUTE));
    }

    #[Test]
    public function a_successful_claim_asks_to_clear_the_cookie(): void
    {
        $repo = $this->createStub(WishlistRepositoryInterface::class);
        $repo->method('findAllGuestByTokenAndChannel')->willReturn([self::defaultList()]);
        $request = Request::create('/', cookies: ['bwlab_wishlist' => 'tok']);

        $this->claimer($repo, $request)->claim($this->createStub(CustomerInterface::class));

        self::assertTrue($request->attributes->get(GuestWishlistClaimer::CLEAR_COOKIE_ATTRIBUTE));
    }
}
