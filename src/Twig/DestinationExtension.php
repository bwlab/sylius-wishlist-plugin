<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\Twig;

use Bwlab\SyliusWishlistPlugin\Destination\DestinationMenu;
use Bwlab\SyliusWishlistPlugin\Resolver\CurrentWishlistResolverInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Contracts\Service\ResetInterface;
use Twig\Attribute\AsTwigFunction;

final class DestinationExtension implements ResetInterface
{
    /** @var list<array{id: int, name: string, selected: bool}>|null */
    private ?array $menu = null;

    public function __construct(
        private readonly CurrentWishlistResolverInterface $resolver,
        private readonly TranslatorInterface $translator,
    ) {
    }

    /**
     * Options of the destination menu: [] with fewer than two lists (no menu). Loaded once per request: a product listing calls it for every card.
     *
     * @return list<array{id: int, name: string, selected: bool}>
     */
    #[AsTwigFunction('bwlab_wishlist_destinations')]
    public function destinations(): array
    {
        return $this->menu ??= $this->load();
    }

    public function reset(): void
    {
        $this->menu = null;
    }

    /** @return list<array{id: int, name: string, selected: bool}> */
    private function load(): array
    {
        $lists = $this->resolver->findAll();
        if (\count($lists) < 2) {
            return [];
        }

        return DestinationMenu::build($lists, $this->resolver->getLastUsed()->getId(), $this->translator->trans('bwlab_wishlist.ui.shop.destination.default_name'));
    }
}
