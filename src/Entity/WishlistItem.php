<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\Entity;

use Doctrine\ORM\Mapping as ORM;
use Sylius\Component\Core\Model\ProductVariantInterface;

#[ORM\Entity]
#[ORM\Table(name: 'bwlab_wishlist_item')]
#[ORM\UniqueConstraint(name: 'bwlab_wishlist_item_wishlist_variant', columns: ['wishlist_id', 'variant_id'])]
class WishlistItem implements WishlistItemInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id', type: 'integer')]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: WishlistInterface::class, inversedBy: 'items')]
    #[ORM\JoinColumn(name: 'wishlist_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected ?WishlistInterface $wishlist = null;

    #[ORM\ManyToOne(targetEntity: ProductVariantInterface::class)]
    #[ORM\JoinColumn(name: 'variant_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected ?ProductVariantInterface $variant = null;

    #[ORM\Column(name: 'quantity', type: 'integer', options: ['default' => 1])]
    protected int $quantity = 1;

    #[ORM\Column(name: 'added_at', type: 'datetime_immutable')]
    protected \DateTimeImmutable $addedAt;

    #[ORM\Column(name: 'source', type: 'string', length: 16)]
    protected string $source = self::SOURCE_PDP;

    public function __construct()
    {
        $this->addedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getWishlist(): ?WishlistInterface
    {
        return $this->wishlist;
    }

    public function setWishlist(?WishlistInterface $wishlist): void
    {
        $this->wishlist = $wishlist;
    }

    public function getVariant(): ?ProductVariantInterface
    {
        return $this->variant;
    }

    public function setVariant(?ProductVariantInterface $variant): void
    {
        $this->variant = $variant;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): void
    {
        if ($quantity < 1) {
            throw new \InvalidArgumentException('Wishlist item quantity must be at least 1.');
        }
        $this->quantity = $quantity;
    }

    public function getAddedAt(): \DateTimeImmutable
    {
        return $this->addedAt;
    }

    public function setAddedAt(\DateTimeImmutable $addedAt): void
    {
        $this->addedAt = $addedAt;
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function setSource(string $source): void
    {
        if (!\in_array($source, self::SOURCES, true)) {
            throw new \InvalidArgumentException(sprintf('Unknown wishlist item source "%s".', $source));
        }
        $this->source = $source;
    }
}
