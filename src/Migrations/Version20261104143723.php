<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261104143723 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Wishlist plugin: wishlist, item, channel settings and anonymous purge stats tables.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE bwlab_wishlist_channel_settings (guest_lifetime_days INT DEFAULT NULL, id INT AUTO_INCREMENT NOT NULL, channel_id INT NOT NULL, UNIQUE INDEX UNIQ_96765AF972F5A1AA (channel_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE bwlab_wishlist_item (quantity INT DEFAULT 1 NOT NULL, added_at DATETIME NOT NULL, source VARCHAR(16) NOT NULL, id INT AUTO_INCREMENT NOT NULL, wishlist_id INT NOT NULL, variant_id INT NOT NULL, INDEX IDX_6CD5D1B4FB8E54CD (wishlist_id), INDEX IDX_6CD5D1B43B69A9AF (variant_id), UNIQUE INDEX bwlab_wishlist_item_wishlist_variant (wishlist_id, variant_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE bwlab_wishlist_purge_stat (purged_at DATE NOT NULL, items_count INT NOT NULL, quantity_sum INT NOT NULL, id INT AUTO_INCREMENT NOT NULL, channel_id INT NOT NULL, variant_id INT NOT NULL, INDEX IDX_2903E21572F5A1AA (channel_id), INDEX IDX_2903E2153B69A9AF (variant_id), UNIQUE INDEX bwlab_wishlist_purge_stat_day (channel_id, variant_id, purged_at), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE bwlab_wishlist_wishlist (token VARCHAR(64) NOT NULL, locale_code VARCHAR(16) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, last_seen_at DATETIME DEFAULT NULL, id INT AUTO_INCREMENT NOT NULL, channel_id INT NOT NULL, customer_id INT DEFAULT NULL, INDEX IDX_908FD21A72F5A1AA (channel_id), INDEX IDX_908FD21A9395C3F3 (customer_id), INDEX bwlab_wishlist_last_seen_idx (last_seen_at), UNIQUE INDEX bwlab_wishlist_customer_channel (customer_id, channel_id), UNIQUE INDEX bwlab_wishlist_token (token), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE bwlab_wishlist_channel_settings ADD CONSTRAINT FK_96765AF972F5A1AA FOREIGN KEY (channel_id) REFERENCES sylius_channel (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE bwlab_wishlist_item ADD CONSTRAINT FK_6CD5D1B4FB8E54CD FOREIGN KEY (wishlist_id) REFERENCES bwlab_wishlist_wishlist (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE bwlab_wishlist_item ADD CONSTRAINT FK_6CD5D1B43B69A9AF FOREIGN KEY (variant_id) REFERENCES sylius_product_variant (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE bwlab_wishlist_purge_stat ADD CONSTRAINT FK_2903E21572F5A1AA FOREIGN KEY (channel_id) REFERENCES sylius_channel (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE bwlab_wishlist_purge_stat ADD CONSTRAINT FK_2903E2153B69A9AF FOREIGN KEY (variant_id) REFERENCES sylius_product_variant (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE bwlab_wishlist_wishlist ADD CONSTRAINT FK_908FD21A72F5A1AA FOREIGN KEY (channel_id) REFERENCES sylius_channel (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE bwlab_wishlist_wishlist ADD CONSTRAINT FK_908FD21A9395C3F3 FOREIGN KEY (customer_id) REFERENCES sylius_customer (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE bwlab_wishlist_channel_settings DROP FOREIGN KEY FK_96765AF972F5A1AA');
        $this->addSql('ALTER TABLE bwlab_wishlist_item DROP FOREIGN KEY FK_6CD5D1B4FB8E54CD');
        $this->addSql('ALTER TABLE bwlab_wishlist_item DROP FOREIGN KEY FK_6CD5D1B43B69A9AF');
        $this->addSql('ALTER TABLE bwlab_wishlist_purge_stat DROP FOREIGN KEY FK_2903E21572F5A1AA');
        $this->addSql('ALTER TABLE bwlab_wishlist_purge_stat DROP FOREIGN KEY FK_2903E2153B69A9AF');
        $this->addSql('ALTER TABLE bwlab_wishlist_wishlist DROP FOREIGN KEY FK_908FD21A72F5A1AA');
        $this->addSql('ALTER TABLE bwlab_wishlist_wishlist DROP FOREIGN KEY FK_908FD21A9395C3F3');
        $this->addSql('DROP TABLE bwlab_wishlist_channel_settings');
        $this->addSql('DROP TABLE bwlab_wishlist_item');
        $this->addSql('DROP TABLE bwlab_wishlist_purge_stat');
        $this->addSql('DROP TABLE bwlab_wishlist_wishlist');
    }
}
