<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\Identity;

use Sylius\Bundle\ResourceBundle\Event\ResourceControllerEvent;
use Sylius\Bundle\UserBundle\Event\UserEvent;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\ShopUserInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

/** Thin listeners: login, implicit login (after email verification) and shop registration. */
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
        $this->claimForUser($event->getAuthenticationToken()->getUser());
    }

    #[AsEventListener(event: 'sylius.user.security.implicit_login')]
    public function onImplicitLogin(UserEvent $event): void
    {
        $this->claimForUser($event->getUser());
    }

    #[AsEventListener(event: 'sylius.customer.post_register')]
    public function onRegistration(ResourceControllerEvent $event): void
    {
        $customer = $event->getSubject();
        if ($customer instanceof CustomerInterface) {
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

    private function claimForUser(mixed $user): void
    {
        if ($user instanceof ShopUserInterface && null !== $customer = $user->getCustomer()) {
            $this->claimer->claim($customer);
        }
    }
}
