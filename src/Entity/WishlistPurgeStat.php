<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\Entity;

use Doctrine\ORM\Mapping as ORM;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;

#[ORM\Entity]
#[ORM\Table(name: 'bwlab_wishlist_purge_stat')]
#[ORM\UniqueConstraint(name: 'bwlab_wishlist_purge_stat_day', columns: ['channel_id', 'variant_id', 'purged_at'])]
class WishlistPurgeStat implements WishlistPurgeStatInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id', type: 'integer')]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: ChannelInterface::class)]
    #[ORM\JoinColumn(name: 'channel_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected ?ChannelInterface $channel = null;

    #[ORM\ManyToOne(targetEntity: ProductVariantInterface::class)]
    #[ORM\JoinColumn(name: 'variant_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected ?ProductVariantInterface $variant = null;

    #[ORM\Column(name: 'purged_at', type: 'date_immutable')]
    protected ?\DateTimeImmutable $purgedAt = null;

    #[ORM\Column(name: 'items_count', type: 'integer')]
    protected int $itemsCount = 0;

    #[ORM\Column(name: 'quantity_sum', type: 'integer')]
    protected int $quantitySum = 0;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getChannel(): ?ChannelInterface
    {
        return $this->channel;
    }

    public function setChannel(?ChannelInterface $channel): void
    {
        $this->channel = $channel;
    }

    public function getVariant(): ?ProductVariantInterface
    {
        return $this->variant;
    }

    public function setVariant(?ProductVariantInterface $variant): void
    {
        $this->variant = $variant;
    }

    public function getPurgedAt(): ?\DateTimeImmutable
    {
        return $this->purgedAt;
    }

    public function setPurgedAt(?\DateTimeImmutable $purgedAt): void
    {
        $this->purgedAt = $purgedAt;
    }

    public function getItemsCount(): int
    {
        return $this->itemsCount;
    }

    public function setItemsCount(int $itemsCount): void
    {
        $this->itemsCount = $itemsCount;
    }

    public function getQuantitySum(): int
    {
        return $this->quantitySum;
    }

    public function setQuantitySum(int $quantitySum): void
    {
        $this->quantitySum = $quantitySum;
    }
}
