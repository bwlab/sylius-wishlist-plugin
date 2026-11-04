<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\Controller\Admin;

use Bwlab\SyliusWishlistPlugin\Entity\WishlistChannelSettingsInterface;
use Bwlab\SyliusWishlistPlugin\Form\Type\ChannelSettingsType;
use Sylius\Component\Channel\Repository\ChannelRepositoryInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Resource\Doctrine\Persistence\RepositoryInterface;
use Sylius\Resource\Factory\FactoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Admin page "Wishlist > Settings": one form per channel.
 * The admin prefix is in the class route: the app's `routing.controllers` import loads #[Route] controllers by itself.
 */
#[Route('/%sylius_admin.path_name%/wishlist/settings')]
final class ChannelSettingsController extends AbstractController
{
    /**
     * @param RepositoryInterface<WishlistChannelSettingsInterface> $settingsRepository
     * @param FactoryInterface<WishlistChannelSettingsInterface> $settingsFactory
     */
    public function __construct(
        #[Autowire(service: 'sylius.repository.channel')]
        private readonly ChannelRepositoryInterface $channelRepository,
        private readonly FormFactoryInterface $formFactory,
        #[Autowire(service: 'bwlab_wishlist.repository.channel_settings')]
        private readonly RepositoryInterface $settingsRepository,
        #[Autowire(service: 'bwlab_wishlist.factory.channel_settings')]
        private readonly FactoryInterface $settingsFactory,
        #[Autowire('%bwlab_wishlist.guest_lifetime_days%')]
        private readonly int $defaultGuestLifetimeDays,
    ) {
    }

    #[Route('', name: 'bwlab_wishlist_admin_settings', methods: ['GET'])]
    public function index(): Response
    {
        return $this->renderPage([]);
    }

    #[Route('/{code}', name: 'bwlab_wishlist_admin_settings_save', methods: ['POST'])]
    public function save(Request $request, string $code): Response
    {
        $channel = $this->channelRepository->findOneBy(['code' => $code]);
        if (!$channel instanceof ChannelInterface) {
            throw $this->createNotFoundException();
        }

        $settings = $this->findSettings($channel);
        $form = $this->createChannelForm($channel, $settings ?? $this->newSettings($channel));
        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            return $this->renderPage([$code => $form]);
        }

        /** @var WishlistChannelSettingsInterface $data */
        $data = $form->getData();
        // A channel with no row and an empty value already means "bundle default": nothing to store.
        if (null !== $settings || null !== $data->getGuestLifetimeDays()) {
            $this->settingsRepository->add($data);
        }

        $this->addFlash('success', 'bwlab_wishlist.admin.settings_saved');

        return new RedirectResponse($this->generateUrl('bwlab_wishlist_admin_settings'));
    }

    /** @param array<string, FormInterface> $submitted forms (with errors) that replace the fresh ones, by channel code */
    private function renderPage(array $submitted): Response
    {
        $forms = [];
        /** @var ChannelInterface $channel */
        foreach ($this->channelRepository->findAll() as $channel) {
            $form = $submitted[$channel->getCode()] ?? $this->createChannelForm($channel, $this->findSettings($channel) ?? $this->newSettings($channel));
            $forms[] = ['channel' => $channel, 'form' => $form->createView()];
        }

        return $this->render('@BwlabSyliusWishlistPlugin/admin/settings/index.html.twig', ['channel_forms' => $forms]);
    }

    private function createChannelForm(ChannelInterface $channel, WishlistChannelSettingsInterface $settings): FormInterface
    {
        return $this->formFactory->createNamed('wishlist_settings_' . $channel->getCode(), ChannelSettingsType::class, $settings, [
            'default_days' => $this->defaultGuestLifetimeDays,
            'action' => $this->generateUrl('bwlab_wishlist_admin_settings_save', ['code' => $channel->getCode()]),
            'method' => 'POST',
        ]);
    }

    private function findSettings(ChannelInterface $channel): ?WishlistChannelSettingsInterface
    {
        return $this->settingsRepository->findOneBy(['channel' => $channel]);
    }

    private function newSettings(ChannelInterface $channel): WishlistChannelSettingsInterface
    {
        /** @var WishlistChannelSettingsInterface $settings */
        $settings = $this->settingsFactory->createNew();
        $settings->setChannel($channel);

        return $settings;
    }
}
