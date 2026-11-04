<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\Controller\Shop;

use Bwlab\SyliusWishlistPlugin\Entity\WishlistInterface;
use Bwlab\SyliusWishlistPlugin\Http\ReturnPath;
use Bwlab\SyliusWishlistPlugin\Repository\WishlistRepositoryInterface;
use Bwlab\SyliusWishlistPlugin\Resolver\CurrentWishlistResolverInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/** Owner side of sharing: turn the public link of one OWN list on, off, or replace it. POST + CSRF only. */
#[Route('/{_locale}/wishlist/{id}/share', requirements: ['_locale' => '^[A-Za-z]{2,4}(_([A-Za-z]{4}|[0-9]{3}))?(_([A-Za-z]{2}|[0-9]{3}))?$', 'id' => '\d+'], methods: ['POST'])]
final class ShareController extends AbstractController
{
    public const DOMAIN = 'bwlab_wishlist_share';

    public function __construct(
        private readonly CurrentWishlistResolverInterface $resolver,
        #[Autowire(service: 'bwlab_wishlist.repository.wishlist')]
        private readonly WishlistRepositoryInterface $wishlistRepository,
        private readonly TranslatorInterface $translator,
    ) {
    }

    #[Route('', name: 'bwlab_wishlist_shop_share_on')]
    public function on(Request $request, int $id): RedirectResponse
    {
        return $this->change($request, $id, static fn (WishlistInterface $l) => $l->enableSharing(), 'flash.on');
    }

    #[Route('/off', name: 'bwlab_wishlist_shop_share_off')]
    public function off(Request $request, int $id): RedirectResponse
    {
        return $this->change($request, $id, static fn (WishlistInterface $l) => $l->disableSharing(), 'flash.off');
    }

    #[Route('/renew', name: 'bwlab_wishlist_shop_share_renew')]
    public function renew(Request $request, int $id): RedirectResponse
    {
        return $this->change($request, $id, static fn (WishlistInterface $l) => $l->renewShareToken(), 'flash.renewed');
    }

    /** Only customers' own lists can be shared (the page needs a name); anything else answers the same 404 as a missing list. */
    private function change(Request $request, int $id, callable $change, string $flash): RedirectResponse
    {
        $list = $this->resolver->findOwned($id);
        if (null === $list || $list->isGuest()) {
            throw new NotFoundHttpException();
        }

        if (!$this->isCsrfTokenValid(WishlistController::CSRF_TOKEN_ID, (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'bwlab_wishlist.invalid_request');
        } else {
            $change($list);
            $this->wishlistRepository->add($list);
            $this->addFlash('success', $this->translator->trans($flash, [], self::DOMAIN));
        }

        return new RedirectResponse(ReturnPath::sanitize(
            $request->request->get('returnTo'),
            $this->generateUrl('bwlab_wishlist_shop_list', ['id' => $id]),
        ));
    }
}
