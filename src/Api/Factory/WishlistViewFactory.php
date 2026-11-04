<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\Api\Factory;

use Bwlab\SyliusWishlistPlugin\Api\Resource\AdminWishlistItem;
use Bwlab\SyliusWishlistPlugin\Api\Resource\ShopWishlist;
use Bwlab\SyliusWishlistPlugin\Api\Resource\ShopWishlistList;
use Bwlab\SyliusWishlistPlugin\Api\Resource\ShopWishlistItem;
use Bwlab\SyliusWishlistPlugin\Api\Resource\WishlistItemFields;
use Bwlab\SyliusWishlistPlugin\Checker\VariantAvailabilityCheckerInterface;
use Bwlab\SyliusWishlistPlugin\Entity\WishlistInterface;
use Bwlab\SyliusWishlistPlugin\Entity\WishlistItemInterface;
use Sylius\Component\Core\Calculator\ProductVariantPricesCalculatorInterface;
use Sylius\Component\Core\Exception\MissingChannelConfigurationException;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Locale\Context\LocaleContextInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Translation\TranslatorInterface;

/** Entity -> API view. Price and availability come from the same services as the shop pages and the cart. */
final class WishlistViewFactory
{
    public function __construct(
        private readonly VariantAvailabilityCheckerInterface $availabilityChecker,
        private readonly ProductVariantPricesCalculatorInterface $priceCalculator,
        #[Autowire('%sylius.security.api_shop_route%')]
        private readonly string $apiShopRoute,
        private readonly TranslatorInterface $translator,
        private readonly LocaleContextInterface $localeContext,
    ) {
    }

    public function shopWishlist(WishlistInterface $wishlist): ShopWishlist
    {
        $view = new ShopWishlist();
        $view->token = $wishlist->getToken();
        $view->listId = $wishlist->getId();
        $view->name = $this->listName($wishlist);
        $view->isDefault = $wishlist->isDefault();
        $view->channelCode = (string) $wishlist->getChannel()?->getCode();
        $view->localeCode = $wishlist->getLocaleCode();
        $view->createdAt = $this->immutable($wishlist->getCreatedAt());
        $view->updatedAt = $this->immutable($wishlist->getUpdatedAt());
        $view->lastSeenAt = $wishlist->getLastSeenAt();
        $view->itemsCount = $wishlist->countItems();
        foreach ($wishlist->getItems() as $item) {
            $view->items[] = $this->shopItem($item);
        }

        return $view;
    }

    /** The default list still called "My Favorites" is shown translated; a renamed list (default or not) keeps the owner's name. */
    public function listName(?WishlistInterface $wishlist): string
    {
        if (null === $wishlist || ($wishlist->isDefault() && WishlistInterface::DEFAULT_NAME === $wishlist->getName())) {
            return $this->translator->trans('default_list_name', [], 'bwlab_wishlist_api', $this->localeContext->getLocaleCode());
        }

        return $wishlist->getName();
    }

    public function shopList(WishlistInterface $wishlist, bool $withItems): ShopWishlistList
    {
        $view = new ShopWishlistList();
        $view->id = $wishlist->getId();
        $view->name = $this->listName($wishlist);
        $view->isDefault = $wishlist->isDefault();
        $view->itemsCount = $wishlist->countItems();
        $view->wishlistToken = $wishlist->getToken();
        $view->createdAt = $this->immutable($wishlist->getCreatedAt());
        $view->updatedAt = $this->immutable($wishlist->getUpdatedAt());
        if ($withItems) {
            foreach ($wishlist->getItems() as $item) {
                $view->items[] = $this->shopItem($item);
            }
        }

        return $view;
    }

    public function shopItem(WishlistItemInterface $item): ShopWishlistItem
    {
        $view = new ShopWishlistItem();
        $this->fill($view, $item);
        $view->wishlistToken = $item->getWishlist()?->getToken() ?? '';
        $view->listId = $item->getWishlist()?->getId();

        return $view;
    }

    public function adminItem(WishlistItemInterface $item): AdminWishlistItem
    {
        $view = new AdminWishlistItem();
        $this->fill($view, $item);

        return $view;
    }

    private function fill(WishlistItemFields $view, WishlistItemInterface $item): void
    {
        $variant = $item->getVariant();
        $channel = $item->getWishlist()?->getChannel();
        if (null === $variant || null === $channel) {
            throw new \LogicException('A wishlist row always has a variant and a channel.');
        }
        $product = $variant->getProduct();

        $view->id = $item->getId();
        $view->variantCode = (string) $variant->getCode();
        $view->productCode = (string) $product?->getCode();
        $view->variantName = $variant->getName();
        $view->productName = $product?->getName();
        $view->variant = sprintf('%s/product-variants/%s', $this->apiShopRoute, $variant->getCode());
        $view->product = sprintf('%s/products/%s', $this->apiShopRoute, $product?->getCode());
        $view->quantity = $item->getQuantity();
        $view->addedAt = $item->getAddedAt();
        $view->source = $item->getSource();
        $view->availability = $this->availabilityChecker->check($variant, $channel)->value;
        $view->currencyCode = $channel->getBaseCurrency()?->getCode();
        [$view->price, $view->originalPrice] = $this->prices($variant, $channel);
        $view->priceAtAdd = $item->getPriceAtAdd();
        $view->priceAtAddCurrencyCode = $item->getCurrencyCode();
    }

    /** @return array{0: ?int, 1: ?int} */
    private function prices(ProductVariantInterface $variant, ChannelInterface $channel): array
    {
        try {
            $context = ['channel' => $channel];

            return [
                $this->priceCalculator->calculate($variant, $context),
                $this->priceCalculator->calculateOriginal($variant, $context),
            ];
        } catch (MissingChannelConfigurationException) {
            return [null, null];
        }
    }

    private function immutable(?\DateTimeInterface $date): ?\DateTimeImmutable
    {
        return null === $date ? null : \DateTimeImmutable::createFromInterface($date);
    }
}
