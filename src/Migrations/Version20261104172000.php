<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/** 0.2.x → 0.3.0: public link of a list (share_token, shared_at) and the "already gifted" marks. Additive only. */
final class Version20261104172000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Wishlist plugin 0.3.0: sharing link per list and gifted marks.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE bwlab_wishlist_wishlist ADD share_token VARCHAR(64) DEFAULT NULL, ADD shared_at DATETIME DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX bwlab_wishlist_share_token ON bwlab_wishlist_wishlist (share_token)');
        $this->addSql('CREATE TABLE bwlab_wishlist_gift (id INT AUTO_INCREMENT NOT NULL, gifted_at DATETIME NOT NULL, item_id INT NOT NULL, UNIQUE INDEX bwlab_wishlist_gift_item (item_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE bwlab_wishlist_gift ADD CONSTRAINT FK_D7B26DA7126F525E FOREIGN KEY (item_id) REFERENCES bwlab_wishlist_item (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE bwlab_wishlist_gift');
        $this->addSql('DROP INDEX bwlab_wishlist_share_token ON bwlab_wishlist_wishlist');
        $this->addSql('ALTER TABLE bwlab_wishlist_wishlist DROP share_token, DROP shared_at');
    }
}
