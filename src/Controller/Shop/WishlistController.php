<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\Controller\Shop;

use Bwlab\SyliusWishlistPlugin\Cart\WishlistCartAdder;
use Bwlab\SyliusWishlistPlugin\Checker\VariantAvailabilityCheckerInterface;
use Bwlab\SyliusWishlistPlugin\Checker\WishlistAvailability;
use Bwlab\SyliusWishlistPlugin\Destination\DestinationMenu;
use Bwlab\SyliusWishlistPlugin\Entity\WishlistInterface;
use Bwlab\SyliusWishlistPlugin\Entity\WishlistItemInterface;
use Bwlab\SyliusWishlistPlugin\Exception\WishlistNameException;
use Bwlab\SyliusWishlistPlugin\Exception\WishlistOperationException;
use Bwlab\SyliusWishlistPlugin\Http\ReturnPath;
use Bwlab\SyliusWishlistPlugin\Modifier\WishlistItemModifierInterface;
use Bwlab\SyliusWishlistPlugin\Repository\WishlistRepositoryInterface;
use Bwlab\SyliusWishlistPlugin\Resolver\CurrentWishlistResolverInterface;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Repository\ProductVariantRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Bwlab\SyliusWishlistPlugin\Twig\WishlistExtension;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

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
        private readonly TranslatorInterface $translator,
        private readonly WishlistExtension $twigExtension,
    ) {
    }

    /** One list (just the default): shown directly, as in phase 1. Several lists: the overview. */
    #[Route('', name: 'bwlab_wishlist_shop_index', methods: ['GET'])]
    public function index(): Response
    {
        $lists = $this->resolver->findAll();
        if (\count($lists) > 1) {
            return $this->renderLists($lists);
        }

        return $this->renderList($this->resolver->find(), $lists, true);
    }

    #[Route('/lists', name: 'bwlab_wishlist_shop_lists', methods: ['GET'])]
    public function lists(): Response
    {
        return $this->renderLists($this->resolver->findAll());
    }

    #[Route('/lists', name: 'bwlab_wishlist_shop_list_create', methods: ['POST'])]
    public function createList(Request $request): Response
    {
        if (!$this->validToken($request)) {
            return $this->invalid($request, 'bwlab_wishlist_shop_lists');
        }

        $name = (string) $request->request->get('name');
        try {
            $list = $this->resolver->create($name);
        } catch (WishlistNameException $e) {
            return $this->renderLists($this->resolver->findAll(), $this->message($e), $name, Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        $this->addFlash('success', ['message' => 'bwlab_wishlist.list_created', 'parameters' => ['%name%' => $list->getName()]]);

        return $this->redirectToRoute('bwlab_wishlist_shop_lists');
    }

    #[Route('/{id}', name: 'bwlab_wishlist_shop_list', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(int $id): Response
    {
        return $this->renderList($this->ownedOr404($id), $this->resolver->findAll(), false);
    }

    #[Route('/{id}/rename', name: 'bwlab_wishlist_shop_list_rename', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function rename(Request $request, int $id): Response
    {
        $list = $this->ownedOr404($id);
        if (!$request->isMethod('POST')) {
            return $this->render('@BwlabSyliusWishlistPlugin/shop/rename.html.twig', ['list' => $list, 'name' => $list->getName(), 'error' => null]);
        }
        if (!$this->validToken($request)) {
            return $this->invalid($request, 'bwlab_wishlist_shop_lists');
        }

        $name = (string) $request->request->get('name');
        try {
            $this->resolver->rename($list, $name);
        } catch (WishlistNameException $e) {
            return $this->render('@BwlabSyliusWishlistPlugin/shop/rename.html.twig', ['list' => $list, 'name' => $name, 'error' => $this->message($e)], new Response(status: Response::HTTP_UNPROCESSABLE_ENTITY));
        }
        $this->addFlash('success', ['message' => 'bwlab_wishlist.list_renamed', 'parameters' => ['%name%' => $list->getName()]]);

        return $this->redirectToRoute('bwlab_wishlist_shop_list', ['id' => $id]);
    }

    /** GET = confirmation page, POST = the deletion itself. The default list cannot be deleted. */
    #[Route('/{id}/delete', name: 'bwlab_wishlist_shop_list_delete', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function deleteList(Request $request, int $id): Response
    {
        $list = $this->ownedOr404($id);
        if ($list->isDefault()) {
            $this->addFlash('error', $this->translator->trans(WishlistOperationException::DELETE_DEFAULT, [], 'validators'));

            return $this->redirectToRoute('bwlab_wishlist_shop_lists');
        }
        if (!$request->isMethod('POST')) {
            return $this->render('@BwlabSyliusWishlistPlugin/shop/delete.html.twig', ['list' => $list]);
        }
        if (!$this->validToken($request)) {
            return $this->invalid($request, 'bwlab_wishlist_shop_lists');
        }

        $name = $list->getName();
        try {
            $this->resolver->delete($list);
        } catch (WishlistOperationException $e) {
            $this->addFlash('error', $this->message($e));

            return $this->redirectToRoute('bwlab_wishlist_shop_lists');
        }
        $this->addFlash('success', ['message' => 'bwlab_wishlist.list_deleted', 'parameters' => ['%name%' => $name]]);

        return $this->redirectToRoute('bwlab_wishlist_shop_lists');
    }

    #[Route('/item/{id}/move', name: 'bwlab_wishlist_shop_move', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function move(Request $request, int $id): Response
    {
        $item = $this->validToken($request) ? $this->findItem($id) : null;
        $target = $this->resolver->findOwned((int) $request->request->get('target'));
        if (null === $item || null === $target) {
            return $this->invalid($request);
        }

        $source = $item->getWishlist();
        try {
            $this->modifier->moveItem($item, $target);
        } catch (WishlistOperationException $e) {
            $this->addFlash('error', $this->message($e));

            return $this->back($request);
        }
        if (null !== $source) {
            $this->wishlistRepository->add($source);
        }
        $this->wishlistRepository->add($target);
        $this->addFlash('success', ['message' => 'bwlab_wishlist.item_moved', 'parameters' => ['%list%' => $this->twigExtension->listName($target)]]);

        return $this->back($request);
    }

    #[Route('/add', name: 'bwlab_wishlist_shop_add', methods: ['POST'])]
    public function add(Request $request): Response
    {
        if (!$this->validToken($request)) {
            return $this->feedback($request, 'error', 'bwlab_wishlist.invalid_request');
        }

        $variant = $this->variantRepository->find((int) $request->request->get('variant'));
        $source = (string) $request->request->get('source');
        if (null === $variant || WishlistAvailability::Unavailable === $this->availabilityChecker->check($variant, $this->channel())) {
            return $this->feedback($request, 'error', 'bwlab_wishlist.variant_not_found');
        }

        // Optional destination: absent = default list (phase 1 clients), "new" = create it first, otherwise the id of one of the owner's lists.
        $listParam = (string) $request->request->get('list', '');
        try {
            $wishlist = 'new' === $listParam
                ? $this->resolver->create((string) $request->request->get('list_name'))
                : $this->resolver->getTarget('' === $listParam ? null : (ctype_digit($listParam) ? (int) $listParam : 0));
        } catch (WishlistNameException $e) {
            return $this->feedback($request, 'error', $e->getMessage(), null, $e->parameters, 'validators');
        }
        if (null === $wishlist) {
            throw $this->createNotFoundException(); // someone else's list and a missing one look the same
        }

        $added = $this->modifier->addVariant(
            $wishlist,
            $variant,
            max(1, min(9999, (int) $request->request->get('quantity', 1))),
            \in_array($source, [WishlistItemInterface::SOURCE_PDP, WishlistItemInterface::SOURCE_PLP], true) ? $source : WishlistItemInterface::SOURCE_PDP,
        );
        if ($added) {
            $this->wishlistRepository->add($wishlist);
        }

        if ('' === $listParam) {
            return $this->feedback($request, $added ? 'success' : 'info', $added ? 'bwlab_wishlist.item_added' : 'bwlab_wishlist.item_already_present', $wishlist);
        }

        return $this->feedback(
            $request,
            $added ? 'success' : 'info',
            $added ? 'bwlab_wishlist.item_added_to_list' : 'bwlab_wishlist.item_already_in_list',
            $wishlist,
            ['%list%' => DestinationMenu::label($wishlist, $this->translator->trans('bwlab_wishlist.ui.shop.destination.default_name'))],
        );
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

        $raw = $request->request->get('quantity');
        if (!\is_string($raw) || 1 !== preg_match('/^\d{1,4}$/', trim($raw)) || 1 > (int) $raw) {
            $this->addFlash('error', 'bwlab_wishlist.quantity_invalid');

            return $this->back($request);
        }

        $this->modifier->changeQuantity($item, (int) $raw);
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
            $this->listFromRequest($request)?->getItems()->toArray() ?? [],
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
        $items = [];
        foreach ($this->listFromRequest($request)?->getItems()->toArray() ?? [] as $item) {
            $variant = $item->getVariant();
            if (null === $variant) {
                continue;
            }
            $availability = $this->availabilityChecker->check($variant, $channel);
            if ($availability->isPurchasable()) {
                $items[] = $item;

                continue;
            }
            // Skipped because the status changed since the page was opened: say which and why, never silently.
            $this->addFlash('error', [
                'message' => 'bwlab_wishlist.cart_item_failed',
                'parameters' => ['%name%' => $variant->getInventoryName() ?? '', '%reason%' => $this->translator->trans($availability->translationKey())],
            ]);
        }

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
        // Ownership by construction: only rows of the current owner's lists are searched.
        foreach ($this->resolver->findAll() as $list) {
            foreach ($list->getItems() as $item) {
                if ($item->getId() === $id) {
                    return $item;
                }
            }
        }

        return null;
    }

    /** List named by the "list" field of a form (none: the default list). Not mine / missing: 404. */
    private function listFromRequest(Request $request): ?WishlistInterface
    {
        $id = $request->request->get('list');
        if (null === $id || '' === $id) {
            return $this->resolver->find();
        }

        return $this->ownedOr404((int) $id);
    }

    private function ownedOr404(int $id): WishlistInterface
    {
        return $this->resolver->findOwned($id) ?? throw new NotFoundHttpException();
    }

    /** @param list<WishlistInterface> $lists */
    private function renderLists(array $lists, ?string $error = null, string $name = '', int $status = Response::HTTP_OK): Response
    {
        return $this->render('@BwlabSyliusWishlistPlugin/shop/lists.html.twig', [
            'lists' => $lists,
            'error' => $error,
            'name' => $name,
            'max_lists' => WishlistInterface::MAX_LISTS,
            'name_max_length' => WishlistInterface::NAME_MAX_LENGTH,
        ], new Response(status: $status));
    }

    /** @param list<WishlistInterface> $lists all lists of the owner (for the "move to" choice and the link to the overview) */
    private function renderList(?WishlistInterface $list, array $lists, bool $single): Response
    {
        $channel = $this->channel();
        $rows = [];
        foreach ($list?->getItems() ?? [] as $item) {
            $variant = $item->getVariant();
            if (null === $variant) {
                continue;
            }
            $rows[] = ['availability' => $this->availabilityChecker->check($variant, $channel), 'item' => $item];
        }

        return $this->render('@BwlabSyliusWishlistPlugin/shop/index.html.twig', [
            'rows' => $rows,
            'list' => $list,
            'lists' => $lists,
            'single' => $single,
        ]);
    }

    /** Translated text of a domain exception (domain "validators", parameters only for names). */
    private function message(\DomainException $e): string
    {
        return $this->translator->trans($e->getMessage(), $e instanceof WishlistNameException ? $e->parameters : [], 'validators');
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

    private function invalid(Request $request, ?string $route = null): Response
    {
        if (null !== $route) {
            $this->addFlash('error', 'bwlab_wishlist.invalid_request');

            return $this->redirectToRoute($route);
        }

        return $this->feedback($request, 'error', 'bwlab_wishlist.invalid_request');
    }

    /**
     * Plain form post: flash + redirect. Script call (fetch): JSON with the message, the row count of all lists and the list used, no flash.
     *
     * @param array<string, string|int> $parameters
     */
    private function feedback(Request $request, string $type, string $messageKey, ?WishlistInterface $wishlist = null, array $parameters = [], string $domain = 'flashes'): Response
    {
        if (!$request->isXmlHttpRequest()) {
            // Errors from other domains (list names) are translated here: the flash renderer only knows "flashes".
            $this->addFlash($type, 'flashes' !== $domain ? $this->translator->trans($messageKey, $parameters, $domain) : ([] === $parameters ? $messageKey : ['message' => $messageKey, 'parameters' => $parameters]));

            return $this->back($request);
        }

        // A visitor's very first save has no cookie yet: findAll() is empty, the list just saved still counts.
        $lists = $this->resolver->findAll();
        if (null !== $wishlist && !\in_array($wishlist, $lists, true)) {
            $lists[] = $wishlist;
        }
        $payload = [
            'count' => DestinationMenu::totalItems($lists),
            'message' => $this->translator->trans($messageKey, $parameters, $domain),
            'type' => $type,
        ];
        if (null !== $wishlist && [] !== $parameters) {
            $payload['list'] = ['id' => $wishlist->getId(), 'name' => $parameters['%list%']];
        }

        return new JsonResponse($payload, 'error' === $type ? Response::HTTP_BAD_REQUEST : Response::HTTP_OK);
    }

    private function back(Request $request): RedirectResponse
    {
        return new RedirectResponse(ReturnPath::sanitize(
            $request->request->get('returnTo'),
            $this->generateUrl('bwlab_wishlist_shop_index'),
        ));
    }
}
