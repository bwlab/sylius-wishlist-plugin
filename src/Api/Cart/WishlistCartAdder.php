<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\Api\Cart;

use Bwlab\SyliusWishlistPlugin\Api\Output\AddWishlistToCartResult;
use Bwlab\SyliusWishlistPlugin\Checker\VariantAvailabilityCheckerInterface;
use Bwlab\SyliusWishlistPlugin\Entity\WishlistInterface;
use Bwlab\SyliusWishlistPlugin\Entity\WishlistItemInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Sylius\Bundle\ApiBundle\Command\Cart\AddItemToCart;
use Sylius\Bundle\ApiBundle\Context\UserContextInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\ShopUserInterface;
use Sylius\Component\Core\Repository\OrderRepositoryInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Wishlist rows -> a Sylius cart, one row at a time, through the SAME command, validation and handler as
 * POST /shop/orders/{token}/items: stock, enabled product/variant and cart rules are never bypassed.
 * A row that cannot enter is reported, the others still enter. Rows always stay in the wishlist.
 */
final class WishlistCartAdder
{
    /** @param OrderRepositoryInterface<OrderInterface> $orderRepository */
    public function __construct(
        #[Autowire(service: 'sylius.repository.order')]
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly VariantAvailabilityCheckerInterface $availabilityChecker,
        private readonly ValidatorInterface $validator,
        #[Autowire(service: 'sylius.command_bus')]
        private readonly MessageBusInterface $commandBus,
        private readonly UserContextInterface $userContext,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger = new NullLogger(),
    ) {
    }

    /** @param list<int>|null $itemIds null = every row of the wishlist */
    public function add(WishlistInterface $wishlist, ChannelInterface $channel, string $cartToken, ?array $itemIds): AddWishlistToCartResult
    {
        // Same 404 for a missing cart and for a cart of another channel.
        $cart = $this->orderRepository->findCartByTokenValueAndChannel($cartToken, $channel);
        if (null === $cart) {
            throw new NotFoundHttpException('Cart not found.');
        }
        // Same ownership rule as AddItemToCartHandler, checked up front: it must hold even when no row is dispatched.
        $this->assertCartAccessible($cart);

        $added = [];
        $skipped = [];
        foreach ($this->select($wishlist, $itemIds, $skipped) as $item) {
            $variant = $item->getVariant();
            \assert(null !== $variant);
            $itemId = (int) $item->getId();
            $code = (string) $variant->getCode();

            $availability = $this->availabilityChecker->check($variant, $channel);
            if (!$availability->isPurchasable()) {
                $skipped[] = ['itemId' => $itemId, 'variantCode' => $code, 'reason' => $availability->value, 'message' => null];

                continue;
            }

            $command = new AddItemToCart($cartToken, $code, $item->getQuantity());
            $violations = $this->validator->validate($command, null, ['sylius']);
            if (\count($violations) > 0) {
                $skipped[] = ['itemId' => $itemId, 'variantCode' => $code, 'reason' => 'rejected', 'message' => (string) $violations->get(0)->getMessage()];

                continue;
            }

            try {
                $this->commandBus->dispatch($command);
            } catch (HandlerFailedException $exception) {
                $cause = $exception->getPrevious() ?? $exception;
                if ($cause instanceof NotFoundHttpException) {
                    throw $cause;
                }
                if ($cause instanceof HttpException) { // known ApiBundle rule (e.g. unprocessable variant): safe to show
                    $skipped[] = ['itemId' => $itemId, 'variantCode' => $code, 'reason' => 'rejected', 'message' => $cause->getMessage()];

                    continue;
                }

                // Unknown failure: log it, never show its message. The cart may be dirty in memory: reload it,
                // and if the EntityManager is closed stop (the caller gets a 500 instead of a half-saved cart).
                $this->logger->error('Wishlist add-to-cart failed.', ['exception' => $cause, 'variant' => $code]);
                if (!$this->entityManager->isOpen()) {
                    throw $cause;
                }
                $this->entityManager->refresh($cart);
                $skipped[] = ['itemId' => $itemId, 'variantCode' => $code, 'reason' => 'rejected', 'message' => null];

                continue;
            }

            $added[] = ['itemId' => $itemId, 'variantCode' => $code, 'quantity' => $item->getQuantity()];
        }

        return new AddWishlistToCartResult($cartToken, $added, $skipped);
    }

    private function assertCartAccessible(OrderInterface $cart): void
    {
        if ($cart->isCreatedByGuest()) {
            return;
        }

        $cartCustomer = $cart->getCustomer();
        if (null === $cartCustomer || null === $cartCustomer->getUser()) {
            return;
        }

        $currentUser = $this->userContext->getUser();
        if ($currentUser instanceof ShopUserInterface && $currentUser->getCustomer()?->getId() === $cartCustomer->getId()) {
            return;
        }

        throw new NotFoundHttpException('Cart not found.');
    }

    /**
     * @param list<int>|null $itemIds
     * @param list<array{itemId: int, variantCode: ?string, reason: string, message: ?string}> $skipped
     *
     * @return list<WishlistItemInterface>
     */
    private function select(WishlistInterface $wishlist, ?array $itemIds, array &$skipped): array
    {
        if (null === $itemIds) {
            return array_values($wishlist->getItems()->toArray());
        }

        $byId = [];
        foreach ($wishlist->getItems() as $item) {
            $byId[$item->getId()] = $item;
        }

        $selected = [];
        foreach (array_unique($itemIds) as $id) {
            if (isset($byId[$id])) {
                $selected[] = $byId[$id];
            } else {
                $skipped[] = ['itemId' => $id, 'variantCode' => null, 'reason' => 'not_in_wishlist', 'message' => null];
            }
        }

        return $selected;
    }
}
