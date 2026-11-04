<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\Api\Factory;

use Bwlab\SyliusWishlistPlugin\Api\Resource\AdminWishlist;
use Bwlab\SyliusWishlistPlugin\Entity\WishlistInterface;

/** Entity -> admin view. The guest token is never exposed: it is the access secret of a visitor wishlist. */
final class AdminWishlistFactory
{
    public function __construct(private readonly WishlistViewFactory $itemFactory)
    {
    }

    public function create(WishlistInterface $wishlist, bool $withItems): AdminWishlist
    {
        $view = new AdminWishlist();
        $view->id = $wishlist->getId();
        $view->channelCode = (string) $wishlist->getChannel()?->getCode();
        $customer = $wishlist->getCustomer();
        $view->customerId = $customer?->getId();
        $view->customerEmail = $customer?->getEmail();
        $view->guest = $wishlist->isGuest();
        $view->localeCode = $wishlist->getLocaleCode();
        $view->createdAt = $wishlist->getCreatedAt() ? \DateTimeImmutable::createFromInterface($wishlist->getCreatedAt()) : null;
        $view->updatedAt = $wishlist->getUpdatedAt() ? \DateTimeImmutable::createFromInterface($wishlist->getUpdatedAt()) : null;
        $view->lastSeenAt = $wishlist->getLastSeenAt();
        $view->itemsCount = $wishlist->countItems();
        if ($withItems) {
            foreach ($wishlist->getItems() as $item) {
                $view->items[] = $this->itemFactory->adminItem($item);
            }
        }

        return $view;
    }
}
