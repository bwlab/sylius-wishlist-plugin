<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\Identity;

use Sylius\Component\Core\Model\ShopUserInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

/** Thin listeners: merge at login only (never at registration: the typed email may belong to someone else). */
final class IdentityListener
{
    public function __construct(
        private readonly GuestWishlistClaimer $claimer,
        #[Autowire('%bwlab_wishlist.cookie_name%')]
        private readonly string $cookieName,
    ) {
    }

    #[AsEventListener(event: 'security.interactive_login')]
    public function onInteractiveLogin(InteractiveLoginEvent $event): void
    {
        $user = $event->getAuthenticationToken()->getUser();
        if ($user instanceof ShopUserInterface && null !== $customer = $user->getCustomer()) {
            $this->claimer->claim($customer);
        }
    }

    // Priority below the base cookie listener: our clearing wins.
    #[AsEventListener(event: 'kernel.response', priority: -10)]
    public function onResponse(ResponseEvent $event): void
    {
        if ($event->isMainRequest() && true === $event->getRequest()->attributes->get(GuestWishlistClaimer::CLEAR_COOKIE_ATTRIBUTE)) {
            $event->getResponse()->headers->clearCookie($this->cookieName, '/');
        }
    }
}
