<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\EventListener;

use Bwlab\SyliusWishlistPlugin\Entity\WishlistItemInterface;
use Bwlab\SyliusWishlistPlugin\Gift\GiftRegistry;
use Bwlab\SyliusWishlistPlugin\Gift\GiftSelector;
use Bwlab\SyliusWishlistPlugin\Gift\GiftSession;
use Doctrine\ORM\EntityManagerInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Order completed in the checkout (same event Sylius uses for the confirmation email): the gift rows the friend
 * put in THIS session's cart and that are in the order become "already gifted". Nothing about the friend is stored.
 */
#[AsEventListener(event: 'sylius.order.post_complete')]
final class GiftOrderListener
{
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly EntityManagerInterface $entityManager,
        private readonly GiftRegistry $registry,
    ) {
    }

    public function __invoke(GenericEvent $event): void
    {
        $order = $event->getSubject();
        $session = $this->requestStack->getMainRequest()?->hasSession() ? $this->requestStack->getSession() : null;
        $ids = null === $session ? [] : GiftSession::ids($session);
        if (!$order instanceof OrderInterface || [] === $ids) {
            return;
        }

        $variantIds = [];
        foreach ($order->getItems() as $orderItem) {
            if (null !== $orderItem->getVariant()?->getId()) {
                $variantIds[] = $orderItem->getVariant()->getId();
            }
        }

        $candidates = $this->entityManager->getRepository(WishlistItemInterface::class)->findBy(['id' => $ids]);
        $items = GiftSelector::select($candidates, $variantIds);
        if ([] !== $items) {
            $this->registry->mark(...$items);
        }
        GiftSession::forget($session, array_map(static fn (WishlistItemInterface $i): int => (int) $i->getId(), $items));
    }
}
