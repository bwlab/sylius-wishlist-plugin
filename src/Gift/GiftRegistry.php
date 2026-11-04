<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\Gift;

use Bwlab\SyliusWishlistPlugin\Entity\WishlistGift;
use Bwlab\SyliusWishlistPlugin\Entity\WishlistItemInterface;
use Doctrine\ORM\EntityManagerInterface;

/** Reads and writes the "already gifted" marks. */
final class GiftRegistry
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    /**
     * @param iterable<WishlistItemInterface> $items
     *
     * @return array<int, true> ids of the rows already gifted
     */
    public function giftedIds(iterable $items): array
    {
        $ids = [];
        foreach ($items as $item) {
            if (null !== $item->getId()) {
                $ids[] = $item->getId();
            }
        }
        if ([] === $ids) {
            return [];
        }

        $rows = $this->entityManager->createQuery('SELECT IDENTITY(g.item) AS item FROM ' . WishlistGift::class . ' g WHERE g.item IN (:ids)')
            ->setParameter('ids', $ids)
            ->getScalarResult();

        return array_fill_keys(array_map(static fn (array $row): int => (int) $row['item'], $rows), true);
    }

    /** Marks the rows (once each) and saves. */
    public function mark(WishlistItemInterface ...$items): void
    {
        $already = $this->giftedIds($items);
        foreach ($items as $item) {
            if (!isset($already[$item->getId()])) {
                $this->entityManager->persist(new WishlistGift($item));
            }
        }
        $this->entityManager->flush();
    }
}
