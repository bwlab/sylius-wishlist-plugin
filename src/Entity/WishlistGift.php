<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * "Already gifted" mark of a wishlist row. Only the row and the date: nobody (friend or owner) is recorded.
 * Kept apart from WishlistItem on purpose; the row going away takes the mark with it (FK cascade).
 */
#[ORM\Entity]
#[ORM\Table(name: 'bwlab_wishlist_gift')]
#[ORM\UniqueConstraint(name: 'bwlab_wishlist_gift_item', columns: ['item_id'])]
class WishlistGift
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id', type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: WishlistItemInterface::class)]
    #[ORM\JoinColumn(name: 'item_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private WishlistItemInterface $item;

    #[ORM\Column(name: 'gifted_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $giftedAt;

    public function __construct(WishlistItemInterface $item)
    {
        $this->item = $item;
        $this->giftedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getItem(): WishlistItemInterface
    {
        return $this->item;
    }

    public function getGiftedAt(): \DateTimeImmutable
    {
        return $this->giftedAt;
    }
}
