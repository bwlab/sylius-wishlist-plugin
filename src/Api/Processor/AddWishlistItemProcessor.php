<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\Api\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Bwlab\SyliusWishlistPlugin\Api\Factory\WishlistViewFactory;
use Bwlab\SyliusWishlistPlugin\Api\Input\AddWishlistItem;
use Bwlab\SyliusWishlistPlugin\Api\Resource\ShopWishlistItem;
use Bwlab\SyliusWishlistPlugin\Checker\VariantAvailabilityCheckerInterface;
use Bwlab\SyliusWishlistPlugin\Checker\WishlistAvailability;
use Bwlab\SyliusWishlistPlugin\Entity\WishlistItemInterface;
use Bwlab\SyliusWishlistPlugin\Modifier\WishlistItemModifierInterface;
use Bwlab\SyliusWishlistPlugin\Repository\WishlistRepositoryInterface;
use Bwlab\SyliusWishlistPlugin\Resolver\CurrentWishlistResolverInterface;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Core\Repository\ProductVariantRepositoryInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/** @implements ProcessorInterface<AddWishlistItem, ShopWishlistItem> */
final class AddWishlistItemProcessor implements ProcessorInterface
{
    /** @param ProductVariantRepositoryInterface<ProductVariantInterface> $variantRepository */
    public function __construct(
        private readonly CurrentWishlistResolverInterface $resolver,
        private readonly WishlistItemModifierInterface $modifier,
        private readonly WishlistRepositoryInterface $wishlistRepository,
        #[Autowire(service: 'sylius.repository.product_variant')]
        private readonly ProductVariantRepositoryInterface $variantRepository,
        private readonly VariantAvailabilityCheckerInterface $availabilityChecker,
        private readonly ChannelContextInterface $channelContext,
        private readonly WishlistViewFactory $viewFactory,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ShopWishlistItem
    {
        /** @var AddWishlistItem $data */
        $variant = $this->variantRepository->findOneBy(['code' => $data->variantCode]);
        if (!$variant instanceof ProductVariantInterface) {
            throw new UnprocessableEntityHttpException(sprintf('Variant "%s" does not exist.', $data->variantCode));
        }
        // Out of stock rows may be saved (the shop shows "Esaurito"); disabled variants/products or products outside the channel may not.
        if (WishlistAvailability::Unavailable === $this->availabilityChecker->check($variant, $this->channelContext->getChannel())) {
            throw new UnprocessableEntityHttpException(sprintf('Variant "%s" is not available in this channel.', $data->variantCode));
        }

        $wishlist = $this->resolver->getOrCreate();
        $this->modifier->addVariant($wishlist, $variant, $data->quantity, WishlistItemInterface::SOURCE_API);
        try {
            $this->wishlistRepository->add($wishlist);
        } catch (UniqueConstraintViolationException) {
            // Two parallel first saves of the same owner: the loser's EntityManager is closed by Doctrine, so no retry here.
            throw new ConflictHttpException('The wishlist was created by a concurrent request: retry.');
        }

        $item = $wishlist->getItemForVariant($variant);
        \assert($item instanceof WishlistItemInterface);

        return $this->viewFactory->shopItem($item);
    }
}
