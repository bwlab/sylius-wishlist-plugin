<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\Identity;

use Bwlab\SyliusWishlistPlugin\Repository\WishlistRepositoryInterface;
use Bwlab\SyliusWishlistPlugin\Resolver\CurrentWishlistResolverInterface;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Channel\Context\ChannelNotFoundException;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RequestStack;

/** Attaches the guest wishlist of the current browser (own cookie, current channel only) to a customer. */
final class GuestWishlistClaimer
{
    public const CLEAR_COOKIE_ATTRIBUTE = '_bwlab_wishlist_clear_cookie';

    public function __construct(
        #[Autowire(service: 'bwlab_wishlist.repository.wishlist')]
        private readonly WishlistRepositoryInterface $wishlistRepository,
        private readonly WishlistMerger $merger,
        private readonly ChannelContextInterface $channelContext,
        private readonly RequestStack $requestStack,
        #[Autowire('%bwlab_wishlist.cookie_name%')]
        private readonly string $cookieName,
    ) {
    }

    public function claim(CustomerInterface $customer): void
    {
        $request = $this->requestStack->getMainRequest();
        $token = $request?->cookies->get($this->cookieName);
        if (null === $request || !\is_string($token) || '' === $token) {
            return;
        }

        try {
            /** @var ChannelInterface $channel */
            $channel = $this->channelContext->getChannel();
        } catch (ChannelNotFoundException) {
            return;
        }

        $guest = $this->wishlistRepository->findOneGuestByTokenAndChannel($token, $channel);
        if (null === $guest) {
            return;
        }

        $survivor = $this->merger->merge($guest, $this->wishlistRepository->findOneByCustomerAndChannel($customer, $channel), $customer);
        $this->wishlistRepository->add($survivor);
        if ($survivor !== $guest) {
            $this->wishlistRepository->remove($guest);
        }

        // Resolver cache must not hand the deleted guest wishlist to later code in this request.
        $request->attributes->remove(CurrentWishlistResolverInterface::REQUEST_ATTRIBUTE);
        $request->attributes->set(self::CLEAR_COOKIE_ATTRIBUTE, true);
    }
}
