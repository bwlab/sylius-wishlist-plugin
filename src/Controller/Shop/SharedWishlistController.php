<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\Controller\Shop;

use Bwlab\SyliusWishlistPlugin\Cart\WishlistCartAdder;
use Bwlab\SyliusWishlistPlugin\Checker\VariantAvailabilityCheckerInterface;
use Bwlab\SyliusWishlistPlugin\Entity\WishlistInterface;
use Bwlab\SyliusWishlistPlugin\Entity\WishlistItemInterface;
use Bwlab\SyliusWishlistPlugin\Gift\GiftRegistry;
use Bwlab\SyliusWishlistPlugin\Gift\GiftSession;
use Bwlab\SyliusWishlistPlugin\Resolver\CurrentWishlistResolverInterface;
use Bwlab\SyliusWishlistPlugin\Share\SharedWishlistFinder;
use Bwlab\SyliusWishlistPlugin\Share\SharedWishlistTitle;
use Bwlab\SyliusWishlistPlugin\Twig\WishlistExtension;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Public, read-only page of a shared list and the "gift" button. No login. The only write is adding to the VISITOR's own cart.
 * Unknown token, link off, other channel: the same bare 404. The token is never logged by this code.
 */
#[Route('/{_locale}/wishlist/shared/{token}', requirements: ['_locale' => '^[A-Za-z]{2,4}(_([A-Za-z]{4}|[0-9]{3}))?(_([A-Za-z]{2}|[0-9]{3}))?$', 'token' => '[^/]+'])]
final class SharedWishlistController extends AbstractController
{
    public const CSRF_TOKEN_ID = 'bwlab_wishlist_gift';

    public function __construct(
        private readonly SharedWishlistFinder $finder,
        private readonly CurrentWishlistResolverInterface $resolver,
        private readonly VariantAvailabilityCheckerInterface $availabilityChecker,
        private readonly ChannelContextInterface $channelContext,
        private readonly WishlistCartAdder $cartAdder,
        private readonly GiftRegistry $gifts,
        private readonly WishlistExtension $twigExtension,
        private readonly TranslatorInterface $translator,
    ) {
    }

    #[Route('', name: 'bwlab_wishlist_shop_shared', methods: ['GET'])]
    public function show(string $token): Response
    {
        $list = $this->listOr404($token);
        $channel = $this->channel();
        $items = $list->getItems()->filter(static fn (WishlistItemInterface $i): bool => null !== $i->getVariant())->getValues();
        $gifted = $this->gifts->giftedIds($items);

        $rows = [];
        foreach ($items as $item) {
            $rows[] = [
                'item' => $item,
                'availability' => $this->availabilityChecker->check($item->getVariant(), $channel),
                'gifted' => isset($gifted[$item->getId()]),
            ];
        }

        $firstName = SharedWishlistTitle::firstName($list->getCustomer());
        $response = $this->render('@BwlabSyliusWishlistPlugin/shop/shared.html.twig', [
            'rows' => $rows,
            'list_name' => $this->twigExtension->listName($list),
            'title' => SharedWishlistTitle::compose($this->twigExtension->listName($list), $firstName),
            'first_name' => $firstName,
            'is_owner' => $this->isOwner($list),
            'token' => $token,
        ]);
        $this->privateHeaders($response);

        return $response;
    }

    #[Route('/gift/{id}', name: 'bwlab_wishlist_shop_gift', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function gift(Request $request, string $token, int $id): Response
    {
        $list = $this->listOr404($token);
        $item = null;
        foreach ($list->getItems() as $candidate) {
            if ($candidate->getId() === $id) {
                $item = $candidate;
            }
        }
        if (null === $item) {
            throw new NotFoundHttpException();
        }

        $back = $this->redirectToRoute('bwlab_wishlist_shop_shared', ['token' => $token]);
        $this->privateHeaders($back);
        if (!$this->isCsrfTokenValid(self::CSRF_TOKEN_ID, (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'bwlab_wishlist.invalid_request');

            return $back;
        }
        if ($this->isOwner($list)) {
            $this->addFlash('info', $this->translator->trans('flash.own_list', [], ShareController::DOMAIN));

            return $back;
        }
        if ([] !== $this->gifts->giftedIds([$item])) {
            $this->addFlash('info', $this->translator->trans('flash.already_gifted', [], ShareController::DOMAIN));

            return $back;
        }

        // Same check and same cart service as "Add to cart" on the wishlist page; the cart is the VISITOR's.
        $reason = $this->cartAdder->add($item);
        if (null !== $reason) {
            $this->addFlash('error', $this->translator->trans('flash.not_added', ['%reason%' => $reason], ShareController::DOMAIN));

            return $back;
        }

        GiftSession::remember($request->getSession(), (int) $item->getId());
        $firstName = SharedWishlistTitle::firstName($list->getCustomer());
        $this->addFlash('success', null === $firstName
            ? $this->translator->trans('flash.gift_added_anonymous', [], ShareController::DOMAIN)
            : $this->translator->trans('flash.gift_added', ['%name%' => $firstName], ShareController::DOMAIN));

        $cart = $this->redirectToRoute('sylius_shop_cart_summary');
        $this->privateHeaders($cart);

        return $cart;
    }

    private function listOr404(string $token): WishlistInterface
    {
        return $this->finder->find($token, $this->channel()) ?? throw new NotFoundHttpException();
    }

    private function isOwner(WishlistInterface $list): bool
    {
        return null !== $this->resolver->findOwned((int) $list->getId());
    }

    private function channel(): ChannelInterface
    {
        /** @var ChannelInterface $channel */
        $channel = $this->channelContext->getChannel();

        return $channel;
    }

    /** The URL is a secret: no caching, no indexing, no Referer leaking it to other sites. */
    private function privateHeaders(Response $response): void
    {
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        $response->headers->set('Referrer-Policy', 'no-referrer');
        $response->headers->set('Cache-Control', 'no-store, private');
    }
}
