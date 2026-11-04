<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\Api\EventListener;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Guest transport for the shop API: header `X-Wishlist-Token`. It is exposed to the domain resolver as the guest cookie,
 * so the API uses exactly the same resolver as the shop pages. A customer JWT always wins over the token.
 */
#[AsEventListener(event: KernelEvents::REQUEST, priority: 32)]
final class GuestTokenHeaderListener
{
    public const HEADER = 'X-Wishlist-Token';

    public function __construct(
        #[Autowire('%bwlab_wishlist.cookie_name%')]
        private readonly string $cookieName,
        #[Autowire('%sylius.security.api_shop_route%')]
        private readonly string $apiShopRoute,
    ) {
    }

    public function __invoke(RequestEvent $event): void
    {
        $request = $event->getRequest();
        if (!$event->isMainRequest() || !str_starts_with($request->getPathInfo(), $this->apiShopRoute . '/')) {
            return;
        }

        $token = $request->headers->get(self::HEADER);
        if (\is_string($token) && '' !== $token) {
            $request->cookies->set($this->cookieName, $token);
        }
    }
}
