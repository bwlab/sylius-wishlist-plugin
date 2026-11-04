<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/** 0.1.0 → 0.2.0: multiple named lists. Every existing wishlist becomes the default list "My Favorites", rows untouched. */
final class Version20261104160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Wishlist plugin 0.2.0: named lists, default list, unique name per owner and channel.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE bwlab_wishlist_wishlist ADD name VARCHAR(60) DEFAULT NULL, ADD name_canonical VARCHAR(60) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_bin`, ADD is_default TINYINT DEFAULT 0 NOT NULL');
        // Phase 1 had exactly one wishlist per owner and channel: it becomes the default list.
        $this->addSql("UPDATE bwlab_wishlist_wishlist SET name = 'My Favorites', name_canonical = 'my favorites', is_default = 1");
        $this->addSql('ALTER TABLE bwlab_wishlist_wishlist CHANGE name name VARCHAR(60) NOT NULL, CHANGE name_canonical name_canonical VARCHAR(60) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_bin`');
        $this->addSql('DROP INDEX bwlab_wishlist_customer_channel ON bwlab_wishlist_wishlist');
        $this->addSql('DROP INDEX bwlab_wishlist_token ON bwlab_wishlist_wishlist');
        $this->addSql('CREATE UNIQUE INDEX bwlab_wishlist_customer_channel_name ON bwlab_wishlist_wishlist (customer_id, channel_id, name_canonical)');
        $this->addSql('CREATE UNIQUE INDEX bwlab_wishlist_token_channel_name ON bwlab_wishlist_wishlist (token, channel_id, name_canonical)');
    }

    public function down(Schema $schema): void
    {
        // Going back to one wishlist per owner would delete the extra lists: refuse instead of losing data.
        $this->throwIrreversibleMigrationException('Downgrading to 0.1.0 would delete named lists.');
    }
}
