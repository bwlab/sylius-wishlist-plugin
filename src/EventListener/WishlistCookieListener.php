<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\EventListener;

use Bwlab\SyliusWishlistPlugin\Entity\WishlistInterface;
use Bwlab\SyliusWishlistPlugin\Provider\GuestLifetimeProviderInterface;
use Bwlab\SyliusWishlistPlugin\Repository\WishlistRepositoryInterface;
use Bwlab\SyliusWishlistPlugin\Resolver\CurrentWishlistResolver;
use Bwlab\SyliusWishlistPlugin\Resolver\CurrentWishlistResolverInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

/** Saves the visit data and sets/renews the guest cookie for persisted wishlists resolved during the request. */
final class WishlistCookieListener
{
    public function __construct(
        private readonly WishlistRepositoryInterface $wishlistRepository,
        private readonly GuestLifetimeProviderInterface $guestLifetimeProvider,
        private readonly string $cookieName,
    ) {
    }

    public function __invoke(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $wishlist = $request->attributes->get(CurrentWishlistResolverInterface::REQUEST_ATTRIBUTE);
        // Not persisted = nothing saved yet: no cookie, no row (wishlist is created on first save only).
        if (!$wishlist instanceof WishlistInterface || null === $wishlist->getId()) {
            return;
        }

        if (true === $request->attributes->get(CurrentWishlistResolver::TOUCHED_ATTRIBUTE)) {
            $this->wishlistRepository->add($wishlist);
        }

        if (!$wishlist->isGuest() || null === $channel = $wishlist->getChannel()) {
            return;
        }

        $days = $this->guestLifetimeProvider->getGuestLifetimeDays($channel);
        $event->getResponse()->headers->setCookie(Cookie::create(
            $this->cookieName,
            $wishlist->getToken(),
            new \DateTimeImmutable(sprintf('+%d days', $days)),
            '/',
            null,
            $request->isSecure(),
            true,
            false,
            Cookie::SAMESITE_LAX,
        ));
    }
}
