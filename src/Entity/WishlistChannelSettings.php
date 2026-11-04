<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\Entity;

use Doctrine\ORM\Mapping as ORM;
use Sylius\Component\Core\Model\ChannelInterface;

#[ORM\Entity]
#[ORM\Table(name: 'bwlab_wishlist_channel_settings')]
class WishlistChannelSettings implements WishlistChannelSettingsInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id', type: 'integer')]
    protected ?int $id = null;

    #[ORM\OneToOne(targetEntity: ChannelInterface::class)]
    #[ORM\JoinColumn(name: 'channel_id', referencedColumnName: 'id', unique: true, nullable: false, onDelete: 'CASCADE')]
    protected ?ChannelInterface $channel = null;

    #[ORM\Column(name: 'guest_lifetime_days', type: 'integer', nullable: true)]
    protected ?int $guestLifetimeDays = null;

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

    public function getGuestLifetimeDays(): ?int
    {
        return $this->guestLifetimeDays;
    }

    public function setGuestLifetimeDays(?int $guestLifetimeDays): void
    {
        $this->guestLifetimeDays = $guestLifetimeDays;
    }
}
