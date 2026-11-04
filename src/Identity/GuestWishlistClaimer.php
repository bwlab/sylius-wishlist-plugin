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
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/** Attaches all guest lists of the current browser (own cookie, current channel only) to a customer. */
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
        private readonly ?LoggerInterface $logger = null,
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

        $guestLists = $this->wishlistRepository->findAllGuestByTokenAndChannel($token, $channel);
        if ([] === $guestLists) {
            return;
        }

        // A failed merge (e.g. unique name hit by another tab) must never block the login: keep the cookie, retry next login.
        try {
            $result = $this->merger->mergeAll($guestLists, $this->wishlistRepository->findAllByCustomerAndChannel($customer, $channel), $customer);
            foreach ($result['save'] as $wishlist) {
                $this->wishlistRepository->add($wishlist);
            }
            foreach ($result['delete'] as $wishlist) {
                $this->wishlistRepository->remove($wishlist);
            }
        } catch (\Throwable $e) {
            $this->logger?->error('Wishlist merge failed at login.', ['exception' => $e]);

            return;
        }

        // Resolver cache must not hand the deleted guest wishlist to later code in this request.
        $request->attributes->remove(CurrentWishlistResolverInterface::REQUEST_ATTRIBUTE);
        $request->attributes->set(self::CLEAR_COOKIE_ATTRIBUTE, true);
    }
}
