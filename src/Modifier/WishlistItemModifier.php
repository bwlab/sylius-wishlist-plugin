<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\Modifier;

use Bwlab\SyliusWishlistPlugin\Entity\WishlistInterface;
use Bwlab\SyliusWishlistPlugin\Entity\WishlistItemInterface;
use Bwlab\SyliusWishlistPlugin\Exception\WishlistOperationException;
use Sylius\Component\Core\Calculator\ProductVariantPricesCalculatorInterface;
use Sylius\Component\Core\Exception\MissingChannelConfigurationException;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Resource\Factory\FactoryInterface;

final class WishlistItemModifier implements WishlistItemModifierInterface
{
    /** @param FactoryInterface<WishlistItemInterface> $wishlistItemFactory */
    public function __construct(
        private readonly FactoryInterface $wishlistItemFactory,
        private readonly ProductVariantPricesCalculatorInterface $priceCalculator,
        private readonly ?\Closure $clock = null,
    ) {
    }

    public function addVariant(
        WishlistInterface $wishlist,
        ProductVariantInterface $variant,
        int $quantity = 1,
        string $source = WishlistItemInterface::SOURCE_PDP,
    ): bool {
        if (null !== $wishlist->getItemForVariant($variant)) {
            return false;
        }

        $item = $this->wishlistItemFactory->createNew();
        $item->setVariant($variant);
        $item->setQuantity($quantity);
        $item->setSource($source);
        $item->setAddedAt($this->now());
        $this->recordPrice($item, $wishlist, $variant);
        $wishlist->addItem($item);
        $wishlist->setUpdatedAt($this->now());

        return true;
    }

    public function removeVariant(WishlistInterface $wishlist, ProductVariantInterface $variant): void
    {
        $item = $wishlist->getItemForVariant($variant);
        if (null === $item) {
            return;
        }

        $wishlist->removeItem($item);
        $wishlist->setUpdatedAt($this->now());
    }

    public function changeQuantity(WishlistItemInterface $item, int $quantity): void
    {
        $item->setQuantity($quantity);
        $item->getWishlist()?->setUpdatedAt($this->now());
    }

    public function moveItem(WishlistItemInterface $item, WishlistInterface $target): WishlistItemInterface
    {
        $source = $item->getWishlist();
        if ($source === $target) {
            throw new WishlistOperationException(WishlistOperationException::SAME_LIST);
        }

        $source?->removeItem($item);
        $source?->setUpdatedAt($this->now());
        $target->setUpdatedAt($this->now());

        $existing = null !== $item->getVariant() ? $target->getItemForVariant($item->getVariant()) : null;
        if (null === $existing) {
            $target->addItem($item);

            return $item;
        }

        self::absorb($existing, $item);

        return $existing;
    }

    /** Duplicate variant rule (move and login merge): greater quantity, oldest addedAt. */
    public static function absorb(WishlistItemInterface $kept, WishlistItemInterface $dropped): void
    {
        $kept->setQuantity(max($kept->getQuantity(), $dropped->getQuantity()));
        // The price follows the oldest row (even when unknown): it is the price at the first save.
        if ($dropped->getAddedAt() < $kept->getAddedAt()) {
            $kept->setPriceAtAdd($dropped->getPriceAtAdd(), $dropped->getCurrencyCode());
        }
        $kept->setAddedAt(min($kept->getAddedAt(), $dropped->getAddedAt()));
    }

    /** Price the shop shows now in the wishlist channel; stays null when the channel has no price. */
    private function recordPrice(WishlistItemInterface $item, WishlistInterface $wishlist, ProductVariantInterface $variant): void
    {
        $channel = $wishlist->getChannel();
        if (null === $channel) {
            return;
        }

        try {
            $price = $this->priceCalculator->calculate($variant, ['channel' => $channel]);
        } catch (MissingChannelConfigurationException) {
            return;
        }
        $item->setPriceAtAdd($price, $channel->getBaseCurrency()?->getCode());
    }

    private function now(): \DateTimeImmutable
    {
        return null !== $this->clock ? ($this->clock)() : new \DateTimeImmutable();
    }
}
