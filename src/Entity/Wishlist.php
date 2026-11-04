<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;

#[ORM\Entity]
#[ORM\Table(name: 'bwlab_wishlist_wishlist')]
// Phase 2: one owner (customer, or guest token shared by all its lists) has many lists; names unique per owner and channel, case-insensitive.
#[ORM\UniqueConstraint(name: 'bwlab_wishlist_customer_channel_name', columns: ['customer_id', 'channel_id', 'name_canonical'])]
#[ORM\UniqueConstraint(name: 'bwlab_wishlist_token_channel_name', columns: ['token', 'channel_id', 'name_canonical'])]
#[ORM\Index(name: 'bwlab_wishlist_last_seen_idx', columns: ['last_seen_at'])]
class Wishlist implements WishlistInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id', type: 'integer')]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: ChannelInterface::class)]
    #[ORM\JoinColumn(name: 'channel_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected ?ChannelInterface $channel = null;

    #[ORM\ManyToOne(targetEntity: CustomerInterface::class)]
    #[ORM\JoinColumn(name: 'customer_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    protected ?CustomerInterface $customer = null;

    #[ORM\Column(name: 'token', type: 'string', length: 64)]
    protected string $token;

    #[ORM\Column(name: 'name', type: 'string', length: 60)]
    protected string $name = self::DEFAULT_NAME;

    /** Lower-cased trimmed name, binary collation: the case-insensitive uniqueness key on MySQL. */
    #[ORM\Column(name: 'name_canonical', type: 'string', length: 60, options: ['collation' => 'utf8mb4_bin'])]
    protected string $nameCanonical = 'my favorites';

    #[ORM\Column(name: 'is_default', type: 'boolean', options: ['default' => false])]
    protected bool $default = false;

    #[ORM\Column(name: 'locale_code', type: 'string', length: 16, nullable: true)]
    protected ?string $localeCode = null;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    protected ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(name: 'updated_at', type: 'datetime_immutable', nullable: true)]
    protected ?\DateTimeInterface $updatedAt = null;

    #[ORM\Column(name: 'last_seen_at', type: 'datetime_immutable', nullable: true)]
    protected ?\DateTimeImmutable $lastSeenAt = null;

    /** @var Collection<int, WishlistItemInterface> */
    #[ORM\OneToMany(targetEntity: WishlistItemInterface::class, mappedBy: 'wishlist', cascade: ['all'], orphanRemoval: true)]
    #[ORM\OrderBy(['addedAt' => 'DESC'])]
    protected Collection $items;

    public function __construct()
    {
        $this->token = bin2hex(random_bytes(32));
        $this->items = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->lastSeenAt = new \DateTimeImmutable();
    }

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

    public function getCustomer(): ?CustomerInterface
    {
        return $this->customer;
    }

    public function setCustomer(?CustomerInterface $customer): void
    {
        $this->customer = $customer;
    }

    public function isGuest(): bool
    {
        return null === $this->customer;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function setToken(string $token): void
    {
        $this->token = $token;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = trim($name);
        $this->nameCanonical = self::canonicalize($name);
    }

    public function getNameCanonical(): string
    {
        return $this->nameCanonical;
    }

    public static function canonicalize(string $name): string
    {
        return mb_strtolower(trim($name));
    }

    public function isDefault(): bool
    {
        return $this->default;
    }

    public function setDefault(bool $default): void
    {
        $this->default = $default;
    }

    public function getLocaleCode(): ?string
    {
        return $this->localeCode;
    }

    public function setLocaleCode(?string $localeCode): void
    {
        $this->localeCode = $localeCode;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeInterface $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

    public function getLastSeenAt(): ?\DateTimeImmutable
    {
        return $this->lastSeenAt;
    }

    public function setLastSeenAt(?\DateTimeImmutable $lastSeenAt): void
    {
        $this->lastSeenAt = $lastSeenAt;
    }

    public function getItems(): Collection
    {
        return $this->items;
    }

    public function getItemForVariant(ProductVariantInterface $variant): ?WishlistItemInterface
    {
        foreach ($this->items as $item) {
            if ($item->getVariant() === $variant) {
                return $item;
            }
        }

        return null;
    }

    public function hasItem(WishlistItemInterface $item): bool
    {
        return $this->items->contains($item);
    }

    public function addItem(WishlistItemInterface $item): void
    {
        if (!$this->hasItem($item)) {
            $this->items->add($item);
            $item->setWishlist($this);
        }
    }

    public function removeItem(WishlistItemInterface $item): void
    {
        if ($this->hasItem($item)) {
            $this->items->removeElement($item);
            $item->setWishlist(null);
        }
    }

    public function countItems(): int
    {
        return $this->items->count();
    }
}
