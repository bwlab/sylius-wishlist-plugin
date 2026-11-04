# Bwlab Sylius Wishlist Plugin

Wishlist for Sylius 2.3: guest (browser cookie) and customer wishlists, one per channel,
availability status, merchant data (added at, source page, last visit locale, quantity).

Requirements: PHP ^8.2, Sylius ^2.3.

## Installation

1. Require the package:

   ```bash
   composer require bwlab/sylius-wishlist-plugin
   ```

   During development from a local folder (e.g. `plugins/sylius-wishlist-plugin`), add a path repository first:

   ```bash
   composer config repositories.bwlab-wishlist '{"type":"path","url":"plugins/sylius-wishlist-plugin","options":{"symlink":true}}'
   composer require 'bwlab/sylius-wishlist-plugin:*@dev'
   ```

2. Register the bundle in `config/bundles.php` (Symfony Flex does it automatically):

   ```php
   Bwlab\SyliusWishlistPlugin\BwlabSyliusWishlistPlugin::class => ['all' => true],
   ```

3. Import the configuration in `config/packages/bwlab_sylius_wishlist.yaml`:

   ```yaml
   imports:
       - { resource: "@BwlabSyliusWishlistPlugin/config/config.yaml" }

   # Optional, defaults shown:
   # bwlab_sylius_wishlist:
   #     guest_lifetime_days: 90     # default guest wishlist lifetime, overridable per channel in the admin
   #     cookie_name: bwlab_wishlist
   ```

4. Import the routes in `config/routes/bwlab_sylius_wishlist.yaml`:

   ```yaml
   bwlab_sylius_wishlist:
       resource: "@BwlabSyliusWishlistPlugin/config/routes.yaml"
   ```

5. Run the migrations (the plugin registers its migrations path `Bwlab\SyliusWishlistPlugin\Migrations` automatically,
   executed after the Sylius core ones):

   ```bash
   bin/console doctrine:migrations:migrate -n
   bin/console cache:clear
   ```

Tables created: `bwlab_wishlist_wishlist`, `bwlab_wishlist_item`, `bwlab_wishlist_channel_settings`, `bwlab_wishlist_purge_stat`.

## Upgrade

```bash
composer update bwlab/sylius-wishlist-plugin
bin/console doctrine:migrations:migrate -n
bin/console cache:clear
```

Released migrations are never modified: upgrades keep existing wishlists, no data reset is required.

## Admin settings and cleanup of expired guest wishlists

Admin menu **Wishlist > Settings**: one form per channel with the guest wishlist lifetime in days
(empty = bundle default `bwlab_sylius_wishlist.guest_lifetime_days`, minimum 1).

Guest wishlists (no customer) not seen for longer than the channel lifetime are deleted by a console command;
customer wishlists are never touched. Before deleting, anonymous counters (channel, variant, day, items, quantity)
are added to `bwlab_wishlist_purge_stat`.

```bash
bin/console bwlab:wishlist:purge-expired --dry-run   # only counts, deletes nothing
bin/console bwlab:wishlist:purge-expired
```

Cron (every night at 03:30):

```cron
30 3 * * * cd /path/to/shop && php bin/console bwlab:wishlist:purge-expired --env=prod --no-debug >> var/log/wishlist-purge.log 2>&1
```

## Development

Unit tests (from the shop where the plugin is installed):

```bash
vendor/bin/phpunit -c plugins/sylius-wishlist-plugin/phpunit.xml.dist
```

Plugin conventions (where to add services, routes, translations, Twig hooks): [docs/architettura.md](docs/architettura.md).

## License

MIT, see [LICENSE](LICENSE).
