<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\Controller\Shop;

use Bwlab\SyliusWishlistPlugin\Cart\WishlistCartAdder;
use Bwlab\SyliusWishlistPlugin\Checker\VariantAvailabilityCheckerInterface;
use Bwlab\SyliusWishlistPlugin\Checker\WishlistAvailability;
use Bwlab\SyliusWishlistPlugin\Entity\WishlistInterface;
use Bwlab\SyliusWishlistPlugin\Entity\WishlistItemInterface;
use Bwlab\SyliusWishlistPlugin\Http\ReturnPath;
use Bwlab\SyliusWishlistPlugin\Modifier\WishlistItemModifierInterface;
use Bwlab\SyliusWishlistPlugin\Repository\WishlistRepositoryInterface;
use Bwlab\SyliusWishlistPlugin\Resolver\CurrentWishlistResolverInterface;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Repository\ProductVariantRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/** Storefront wishlist: plain HTML forms + CSRF, no JavaScript needed. */
#[Route('/{_locale}/wishlist', requirements: ['_locale' => '^[A-Za-z]{2,4}(_([A-Za-z]{4}|[0-9]{3}))?(_([A-Za-z]{2}|[0-9]{3}))?$'])]
final class WishlistController extends AbstractController
{
    public const CSRF_TOKEN_ID = 'bwlab_wishlist';

    public function __construct(
        private readonly CurrentWishlistResolverInterface $resolver,
        private readonly WishlistItemModifierInterface $modifier,
        #[Autowire(service: 'bwlab_wishlist.repository.wishlist')]
        private readonly WishlistRepositoryInterface $wishlistRepository,
        private readonly VariantAvailabilityCheckerInterface $availabilityChecker,
        private readonly ChannelContextInterface $channelContext,
        #[Autowire(service: 'sylius.repository.product_variant')]
        private readonly ProductVariantRepositoryInterface $variantRepository,
        private readonly WishlistCartAdder $cartAdder,
    ) {
    }

    #[Route('', name: 'bwlab_wishlist_shop_index', methods: ['GET'])]
    public function index(): Response
    {
        $channel = $this->channel();
        $rows = [];
        foreach ($this->resolver->find()?->getItems() ?? [] as $item) {
            $variant = $item->getVariant();
            if (null === $variant) {
                continue;
            }
            $rows[] = ['availability' => $this->availabilityChecker->check($variant, $channel), 'item' => $item];
        }

        return $this->render('@BwlabSyliusWishlistPlugin/shop/index.html.twig', ['rows' => $rows]);
    }

    #[Route('/add', name: 'bwlab_wishlist_shop_add', methods: ['POST'])]
    public function add(Request $request): Response
    {
        if (!$this->validToken($request)) {
            return $this->invalid($request);
        }

        $variant = $this->variantRepository->find((int) $request->request->get('variant'));
        $source = (string) $request->request->get('source');
        if (null === $variant || WishlistAvailability::Unavailable === $this->availabilityChecker->check($variant, $this->channel())) {
            $this->addFlash('error', 'bwlab_wishlist.variant_not_found');

            return $this->back($request);
        }

        $wishlist = $this->resolver->getOrCreate();
        $added = $this->modifier->addVariant(
            $wishlist,
            $variant,
            1,
            \in_array($source, [WishlistItemInterface::SOURCE_PDP, WishlistItemInterface::SOURCE_PLP], true) ? $source : WishlistItemInterface::SOURCE_PDP,
        );
        if ($added) {
            $this->wishlistRepository->add($wishlist);
        }
        $this->addFlash($added ? 'success' : 'info', $added ? 'bwlab_wishlist.item_added' : 'bwlab_wishlist.item_already_present');

        return $this->back($request);
    }

    #[Route('/item/{id}/remove', name: 'bwlab_wishlist_shop_remove', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function remove(Request $request, int $id): Response
    {
        $item = $this->validToken($request) ? $this->findItem($id) : null;
        if (null === $item) {
            return $this->invalid($request);
        }

        $wishlist = $item->getWishlist();
        \assert($wishlist instanceof WishlistInterface);
        $this->modifier->removeVariant($wishlist, $item->getVariant());
        $this->wishlistRepository->add($wishlist);
        $this->addFlash('success', 'bwlab_wishlist.item_removed');

        return $this->back($request);
    }

    #[Route('/item/{id}/quantity', name: 'bwlab_wishlist_shop_quantity', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function quantity(Request $request, int $id): Response
    {
        $item = $this->validToken($request) ? $this->findItem($id) : null;
        if (null === $item) {
            return $this->invalid($request);
        }

        $this->modifier->changeQuantity($item, max(1, min(9999, (int) $request->request->get('quantity'))));
        $this->wishlistRepository->add($item->getWishlist());
        $this->addFlash('success', 'bwlab_wishlist.quantity_changed');

        return $this->back($request);
    }

    #[Route('/cart/item/{id}', name: 'bwlab_wishlist_shop_cart_item', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function cartItem(Request $request, int $id): Response
    {
        $item = $this->validToken($request) ? $this->findItem($id) : null;
        if (null === $item) {
            return $this->invalid($request);
        }

        return $this->toCart($request, [$item]);
    }

    #[Route('/cart/selected', name: 'bwlab_wishlist_shop_cart_selected', methods: ['POST'])]
    public function cartSelected(Request $request): Response
    {
        if (!$this->validToken($request)) {
            return $this->invalid($request);
        }

        $ids = array_map('intval', (array) $request->request->all('items'));
        $items = array_values(array_filter(
            $this->resolver->find()?->getItems()->toArray() ?? [],
            static fn (WishlistItemInterface $item): bool => \in_array($item->getId(), $ids, true),
        ));

        return $this->toCart($request, $items);
    }

    #[Route('/cart/all', name: 'bwlab_wishlist_shop_cart_all', methods: ['POST'])]
    public function cartAll(Request $request): Response
    {
        if (!$this->validToken($request)) {
            return $this->invalid($request);
        }

        $channel = $this->channel();
        $items = array_values(array_filter(
            $this->resolver->find()?->getItems()->toArray() ?? [],
            fn (WishlistItemInterface $item): bool => null !== $item->getVariant()
                && $this->availabilityChecker->check($item->getVariant(), $channel)->isPurchasable(),
        ));

        return $this->toCart($request, $items);
    }

    /** @param list<WishlistItemInterface> $items rows stay in the wishlist; failures are reported one by one */
    private function toCart(Request $request, array $items): Response
    {
        if ([] === $items) {
            $this->addFlash('info', 'bwlab_wishlist.nothing_to_add');

            return $this->back($request);
        }

        $added = 0;
        foreach ($items as $item) {
            $reason = $this->cartAdder->add($item);
            if (null === $reason) {
                ++$added;

                continue;
            }
            $this->addFlash('error', [
                'message' => 'bwlab_wishlist.cart_item_failed',
                'parameters' => ['%name%' => $item->getVariant()?->getInventoryName() ?? '', '%reason%' => $reason],
            ]);
        }
        if ($added > 0) {
            $this->addFlash('success', ['message' => 'bwlab_wishlist.cart_added', 'parameters' => ['%count%' => $added]]);
        }

        return $this->back($request);
    }

    private function findItem(int $id): ?WishlistItemInterface
    {
        foreach ($this->resolver->find()?->getItems() ?? [] as $item) {
            if ($item->getId() === $id) {
                return $item;
            }
        }

        return null;
    }

    private function channel(): ChannelInterface
    {
        /** @var ChannelInterface $channel */
        $channel = $this->channelContext->getChannel();

        return $channel;
    }

    private function validToken(Request $request): bool
    {
        return $this->isCsrfTokenValid(self::CSRF_TOKEN_ID, (string) $request->request->get('_token'));
    }

    private function invalid(Request $request): RedirectResponse
    {
        $this->addFlash('error', 'bwlab_wishlist.invalid_request');

        return $this->back($request);
    }

    private function back(Request $request): RedirectResponse
    {
        return new RedirectResponse(ReturnPath::sanitize(
            $request->request->get('returnTo'),
            $this->generateUrl('bwlab_wishlist_shop_index'),
        ));
    }
}
