<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\Tests\Unit\Api;

use Bwlab\SyliusWishlistPlugin\Api\EventListener\GuestTokenHeaderListener;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class GuestTokenHeaderListenerTest extends TestCase
{
    private function fire(string $path, ?string $header, int $type = HttpKernelInterface::MAIN_REQUEST): Request
    {
        $request = Request::create($path);
        if (null !== $header) {
            $request->headers->set('X-Wishlist-Token', $header);
        }
        (new GuestTokenHeaderListener('bwlab_wishlist', '/api/v2/shop'))(
            new RequestEvent($this->createStub(HttpKernelInterface::class), $request, $type),
        );

        return $request;
    }

    public function testHeaderBecomesGuestCookieOnShopApi(): void
    {
        self::assertSame('abc', $this->fire('/api/v2/shop/wishlist', 'abc')->cookies->get('bwlab_wishlist'));
    }

    public function testIgnoredOutsideShopApi(): void
    {
        self::assertFalse($this->fire('/en_US/wishlist', 'abc')->cookies->has('bwlab_wishlist'));
        self::assertFalse($this->fire('/api/v2/admin/wishlists', 'abc')->cookies->has('bwlab_wishlist'));
    }

    public function testNoHeaderNoCookie(): void
    {
        self::assertFalse($this->fire('/api/v2/shop/wishlist', null)->cookies->has('bwlab_wishlist'));
    }
}
