<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\Share;

use Bwlab\SyliusWishlistPlugin\Entity\WishlistInterface;
use Bwlab\SyliusWishlistPlugin\Repository\WishlistRepositoryInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/** The list behind a public link, or null for every reason it must not be shown (callers answer one identical 404). */
final class SharedWishlistFinder
{
    public function __construct(
        #[Autowire(service: 'bwlab_wishlist.repository.wishlist')]
        private readonly WishlistRepositoryInterface $repository,
    ) {
    }

    public function find(string $token, ChannelInterface $channel): ?WishlistInterface
    {
        if (1 !== preg_match('/^[0-9a-f]{64}$/', $token)) {
            return null; // not even shaped like a token: no lookup
        }

        $list = $this->repository->findOneByShareToken($token);
        if (null === $list || !$list->isShared() || null === $list->getCustomer() || $list->getChannel() !== $channel) {
            return null;
        }

        return $list;
    }
}
