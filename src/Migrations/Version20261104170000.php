<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/** 0.2.0 → 0.2.1: price at save time. Additive only; existing rows stay NULL (the past price cannot be rebuilt). */
final class Version20261104170000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Wishlist plugin 0.2.1: price and currency recorded when a product is saved.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE bwlab_wishlist_item ADD price_at_add INT DEFAULT NULL, ADD currency_code VARCHAR(3) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE bwlab_wishlist_item DROP price_at_add, DROP currency_code');
    }
}
