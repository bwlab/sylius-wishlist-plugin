<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\Admin\Customer;

use Bwlab\SyliusWishlistPlugin\Checker\VariantAvailabilityCheckerInterface;
use Bwlab\SyliusWishlistPlugin\Entity\WishlistInterface;
use Bwlab\SyliusWishlistPlugin\Entity\WishlistItemInterface;
use Sylius\Component\Core\Calculator\ProductVariantPricesCalculatorInterface;
use Sylius\Component\Core\Exception\MissingChannelConfigurationException;

/**
 * Pure logic (no I/O): groups lists by channel and sorts them.
 * Channels by name; in a channel the default list first, then by creation; rows by addedAt descending.
 * Rows without a variant are skipped (cannot be shown).
 */
final class CustomerWishlistGrouper
{
    public function __construct(
        private readonly VariantAvailabilityCheckerInterface $availabilityChecker,
        private readonly ProductVariantPricesCalculatorInterface $priceCalculator,
    ) {
    }

    /**
     * @param iterable<WishlistInterface> $wishlists
     *
     * @return list<ChannelWishlists>
     */
    public function group(iterable $wishlists): array
    {
        /** @var array<string, array{channel: \Sylius\Component\Core\Model\ChannelInterface, lists: list<WishlistInterface>}> $byChannel */
        $byChannel = [];
        foreach ($wishlists as $wishlist) {
            $channel = $wishlist->getChannel();
            if (null === $channel) {
                continue;
            }
            $byChannel[(string) $channel->getCode()]['channel'] = $channel;
            $byChannel[(string) $channel->getCode()]['lists'][] = $wishlist;
        }

        uasort($byChannel, static fn (array $a, array $b): int => strcasecmp((string) $a['channel']->getName(), (string) $b['channel']->getName()));

        $result = [];
        foreach ($byChannel as $group) {
            $lists = $group['lists'];
            usort($lists, static fn (WishlistInterface $a, WishlistInterface $b): int => [$b->isDefault(), $a->getCreatedAt()?->getTimestamp(), $a->getId()]
                <=> [$a->isDefault(), $b->getCreatedAt()?->getTimestamp(), $b->getId()]);

            $views = [];
            foreach ($lists as $list) {
                $views[] = new WishlistView($list, $this->items($list, $group['channel']));
            }
            $result[] = new ChannelWishlists($group['channel'], $views);
        }

        return $result;
    }

    /** @return list<ItemView> */
    private function items(WishlistInterface $wishlist, \Sylius\Component\Core\Model\ChannelInterface $channel): array
    {
        $items = array_values(array_filter($wishlist->getItems()->toArray(), static fn (WishlistItemInterface $i): bool => null !== $i->getVariant()));
        usort($items, static fn (WishlistItemInterface $a, WishlistItemInterface $b): int => [$b->getAddedAt()->getTimestamp(), $b->getId()] <=> [$a->getAddedAt()->getTimestamp(), $a->getId()]);

        return array_map(
            fn (WishlistItemInterface $item): ItemView => new ItemView($item, $this->availabilityChecker->check($item->getVariant(), $channel), $this->currentPrice($item, $channel)),
            $items,
        );
    }

    private function currentPrice(WishlistItemInterface $item, \Sylius\Component\Core\Model\ChannelInterface $channel): ?int
    {
        try {
            return $this->priceCalculator->calculate($item->getVariant(), ['channel' => $channel]);
        } catch (MissingChannelConfigurationException) {
            return null;
        }
    }
}
