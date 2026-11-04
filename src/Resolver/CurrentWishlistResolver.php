<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\Resolver;

use Bwlab\SyliusWishlistPlugin\Entity\WishlistInterface;
use Bwlab\SyliusWishlistPlugin\Exception\WishlistOperationException;
use Bwlab\SyliusWishlistPlugin\Policy\WishlistNamePolicy;
use Bwlab\SyliusWishlistPlugin\Repository\WishlistRepositoryInterface;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Customer\Context\CustomerContextInterface;
use Sylius\Component\Locale\Context\LocaleContextInterface;
use Sylius\Resource\Factory\FactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

final class CurrentWishlistResolver implements CurrentWishlistResolverInterface
{
    /** Request attribute set to true when lastSeenAt/localeCode changed and must be saved. */
    public const TOUCHED_ATTRIBUTE = '_bwlab_wishlist_touched';

    /** @param FactoryInterface<WishlistInterface> $wishlistFactory */
    public function __construct(
        private readonly WishlistRepositoryInterface $wishlistRepository,
        private readonly FactoryInterface $wishlistFactory,
        private readonly ChannelContextInterface $channelContext,
        private readonly CustomerContextInterface $customerContext,
        private readonly LocaleContextInterface $localeContext,
        private readonly RequestStack $requestStack,
        private readonly string $cookieName,
    ) {
    }

    public function find(): ?WishlistInterface
    {
        $request = $this->requestStack->getMainRequest();
        $cached = $request?->attributes->get(self::REQUEST_ATTRIBUTE);
        if ($cached instanceof WishlistInterface) {
            return $cached;
        }

        /** @var ChannelInterface $channel */
        $channel = $this->channelContext->getChannel();
        $customer = $this->customerContext->getCustomer();

        $wishlist = null;
        if ($customer instanceof CustomerInterface) {
            $wishlist = $this->wishlistRepository->findOneByCustomerAndChannel($customer, $channel);
        } elseif (null !== $token = $this->getGuestToken($request)) {
            $wishlist = $this->wishlistRepository->findOneGuestByTokenAndChannel($token, $channel);
        }

        if (null !== $wishlist) {
            $this->remember($request, $wishlist);
        }

        return $wishlist;
    }

    public function getOrCreate(): WishlistInterface
    {
        $wishlist = $this->find();
        if (null !== $wishlist) {
            return $wishlist;
        }

        /** @var ChannelInterface $channel */
        $channel = $this->channelContext->getChannel();
        $customer = $this->customerContext->getCustomer();
        $request = $this->requestStack->getMainRequest();

        $wishlist = $this->wishlistFactory->createNew();
        $wishlist->setChannel($channel);
        $wishlist->setName(WishlistInterface::DEFAULT_NAME);
        $wishlist->setDefault(true);
        // Guest: always a fresh token (unique column). shortcut: channels sharing one hostname share one cookie, so the last channel used wins; upgrade to a per-channel cookie name if needed.
        if ($customer instanceof CustomerInterface) {
            $wishlist->setCustomer($customer);
        }

        $this->remember($request, $wishlist);

        return $wishlist;
    }

    public function findAll(): array
    {
        /** @var ChannelInterface $channel */
        $channel = $this->channelContext->getChannel();
        $customer = $this->customerContext->getCustomer();
        if ($customer instanceof CustomerInterface) {
            return $this->wishlistRepository->findAllByCustomerAndChannel($customer, $channel);
        }
        $token = $this->getGuestToken($this->requestStack->getMainRequest());

        return null === $token ? [] : $this->wishlistRepository->findAllGuestByTokenAndChannel($token, $channel);
    }

    public function findOwned(int $id): ?WishlistInterface
    {
        // Ownership by construction: only the current owner's lists are searched (max 20).
        foreach ($this->findAll() as $wishlist) {
            if ($wishlist->getId() === $id) {
                return $wishlist;
            }
        }

        return null;
    }

    public function getTarget(?int $listId = null): ?WishlistInterface
    {
        return null === $listId ? $this->getOrCreate() : $this->findOwned($listId);
    }

    public function getLastUsed(): WishlistInterface
    {
        $last = null;
        foreach ($this->findAll() as $wishlist) {
            if (null !== $wishlist->getUpdatedAt() && (null === $last || $wishlist->getUpdatedAt() > $last->getUpdatedAt())) {
                $last = $wishlist;
            }
        }

        return $last ?? $this->getOrCreate();
    }

    public function create(string $name): WishlistInterface
    {
        $lists = $this->findAll();
        $name = WishlistNamePolicy::check($name, $lists);

        $default = $this->getOrCreate();
        if (null === $default->getId()) {
            $this->wishlistRepository->add($default);
        }

        $wishlist = $this->wishlistFactory->createNew();
        $wishlist->setChannel($default->getChannel());
        $wishlist->setCustomer($default->getCustomer());
        $wishlist->setToken($default->getToken()); // guest owner token shared by all lists
        $wishlist->setLocaleCode($default->getLocaleCode());
        $wishlist->setName($name);
        $this->wishlistRepository->add($wishlist);

        return $wishlist;
    }

    public function rename(WishlistInterface $wishlist, string $name): void
    {
        $wishlist->setName(WishlistNamePolicy::check($name, $this->findAll(), $wishlist));
        $this->wishlistRepository->add($wishlist);
    }

    public function delete(WishlistInterface $wishlist): void
    {
        if ($wishlist->isDefault()) {
            throw new WishlistOperationException(WishlistOperationException::DELETE_DEFAULT);
        }
        $this->wishlistRepository->remove($wishlist);
    }

    private function remember(?Request $request, WishlistInterface $wishlist): void
    {
        $touched = WishlistVisitRecorder::record($wishlist, new \DateTimeImmutable(), $this->localeContext->getLocaleCode());

        if (null === $request) {
            return;
        }

        $request->attributes->set(self::REQUEST_ATTRIBUTE, $wishlist);
        if ($touched) {
            $request->attributes->set(self::TOUCHED_ATTRIBUTE, true);
        }
    }

    private function getGuestToken(?Request $request): ?string
    {
        $token = $request?->cookies->get($this->cookieName);

        return \is_string($token) && '' !== $token ? $token : null;
    }
}
