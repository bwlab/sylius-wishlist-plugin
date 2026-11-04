<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\Cart;

use Bwlab\SyliusWishlistPlugin\Checker\VariantAvailabilityCheckerInterface;
use Bwlab\SyliusWishlistPlugin\Entity\WishlistItemInterface;
use Sylius\Bundle\OrderBundle\Adder\CartItemAdderInterface;
use Sylius\Bundle\OrderBundle\Factory\AddToCartCommandFactoryInterface;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Core\Factory\CartItemFactoryInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Order\Context\CartContextInterface;
use Sylius\Component\Order\Modifier\OrderItemQuantityModifierInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/** Moves a wishlist row into the current Sylius cart using the same validation as the "add to cart" form. */
final class WishlistCartAdder
{
    public function __construct(
        private readonly CartContextInterface $cartContext,
        #[Autowire(service: 'sylius.custom_factory.order_item')]
        private readonly CartItemFactoryInterface $cartItemFactory,
        private readonly OrderItemQuantityModifierInterface $quantityModifier,
        private readonly AddToCartCommandFactoryInterface $commandFactory,
        private readonly CartItemAdderInterface $cartItemAdder,
        private readonly ValidatorInterface $validator,
        private readonly VariantAvailabilityCheckerInterface $availabilityChecker,
        private readonly ChannelContextInterface $channelContext,
        private readonly TranslatorInterface $translator,
    ) {
    }

    /** @return string|null null when added, otherwise the (translated) reason why the row was not added */
    public function add(WishlistItemInterface $item): ?string
    {
        $variant = $item->getVariant();
        /** @var ChannelInterface $channel */
        $channel = $this->channelContext->getChannel();
        if (null === $variant) {
            return $this->translator->trans('bwlab_wishlist.ui.availability.unavailable', [], 'messages');
        }

        $availability = $this->availabilityChecker->check($variant, $channel);
        if (!$availability->isPurchasable()) {
            return $this->translator->trans($availability->translationKey(), [], 'messages');
        }

        $cartItem = $this->cartItemFactory->createNew();
        $cartItem->setVariant($variant);
        $this->quantityModifier->modify($cartItem, $item->getQuantity());

        $command = $this->commandFactory->createWithCartAndCartItem($this->cartContext->getCart(), $cartItem);
        $violations = $this->validator->validate($command, null, ['sylius']);
        if (\count($violations) > 0) {
            $messages = [];
            foreach ($violations as $violation) {
                $messages[] = (string) $violation->getMessage();
            }

            return implode(' ', array_unique($messages));
        }

        $this->cartItemAdder->add($command);

        return null;
    }
}
