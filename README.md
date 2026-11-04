# Bwlab Sylius Wishlist Plugin

A wishlist for Sylius 2.3 that helps shoppers come back and buy. Visitors and logged-in customers can save products, and the wishlist is merged into the customer account at login, so nothing is lost.

- **One wishlist per channel**, with its own guest retention time, set in the admin panel.
- **Cart that follows Sylius rules**: stock, availability and cart validation apply; products that cannot be bought are skipped and explained.
- **Complete shop API** (admin API is read-only), to connect apps and marketing tools.
- **Data recorded from day one** (added at, source page, locale, quantity), so you can measure conversions later.

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

### 0.1.0 → 0.2.0 (multiple named lists)

1. Back up the database.
2. Run the commands above. The migration `Version20261104160000` adds the list name and the default flag, replaces the unique keys (one wishlist per customer → unique list name per owner and channel) and turns every existing wishlist into the default list «My Favorites». Items, quantities and dates are kept.
3. Nothing to change in the shop: phase 1 routes and API calls without a list keep working on the default list. The guest cookie keeps its name and lifetime; it now identifies the owner of all their lists.

The migration cannot be reverted (`migrate prev` refuses): going back would delete the extra lists.

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

## API

REST API on API Platform (Sylius 2.3), resources declared with PHP attributes in `src/Api/Resource`. The bundle registers the
mapping path itself; the shop only needs the usual `api_platform` routes import (already present in a Sylius 2 app).
Same data as the shop pages: `addedAt`, `source`, `quantity`, `availability` (`available` | `out_of_stock` | `unavailable`),
current channel `price` / `originalPrice` (minor units) with `currencyCode`, variant and product (code, name, IRI).
The channel is the one of the request (host), as for every Sylius API call.

### Who is the owner

- **Customer**: JWT of Sylius (`POST /api/v2/shop/customers/token`), header `Authorization: Bearer <jwt>`.
- **Visitor**: header `X-Wishlist-Token: <token>`. The token is returned as `wishlistToken` by the first
  `POST /shop/wishlist/items` (and as `token` by `GET /shop/wishlist`). A customer JWT always wins over the header.
- Nobody reaches another owner's wishlist: rows of other wishlists answer `404`, like missing rows.

### Shop (`/api/v2/shop`)

| Method and path | Body | Result |
|---|---|---|
| `GET /wishlist` | - | wishlist of the channel with `items` (empty wishlist, `token: null`, when nothing is saved) |
| `POST /wishlist/items` | `{"variantCode": "...", "quantity": 1}` | the row (`source = api`); a variant already present returns the existing row, no duplicate. `422` for unknown or unavailable (disabled / not in channel) variants; out of stock may be saved |
| `GET /wishlist/items/{id}` | - | one row |
| `PATCH /wishlist/items/{id}` | `{"quantity": 3}` (`Content-Type: application/merge-patch+json`) | the row |
| `DELETE /wishlist/items/{id}` | - | `204` |
| `POST /wishlist/add-to-cart` | `{"orderTokenValue": "<cart token>", "itemIds": [1, 2]}` or `{"orderTokenValue": "...", "all": true}` | `{orderTokenValue, added[], skipped[]}` |

`add-to-cart` goes through the same validation and command as `POST /shop/orders/{token}/items` (stock, enabled product,
cart rules): a row that cannot enter is listed in `skipped` with `reason` (`out_of_stock`, `unavailable`, `rejected` + `message`,
`not_in_wishlist`), the others enter. Rows always stay in the wishlist. Unknown cart, cart of another channel or of another
customer: `404`.

### Admin (`/api/v2/admin`, read only, admin JWT)

| Path | Result |
|---|---|
| `GET /wishlists?customer=<id or email>&channel=<code>&page=1` | paginated list (no rows; `itemsCount`) |
| `GET /wishlists/{id}` | wishlist with rows |
| `GET /wishlist-channel-settings` | effective settings of every channel (`guestLifetimeDays`, `usesDefault`) |
| `GET /wishlist-channel-settings/{channelCode}` | settings of one channel |

The visitor token is never exposed by the admin API. There is no write operation.

### Multiple named lists (0.2.0)

Phase 1 calls keep working unchanged: without a list, every operation uses the **default list**
(`GET /shop/wishlist` still returns it, now also with `listId`, `name`, `isDefault`; rows carry `listId`).
The new resources sit next to it. A list is chosen by its id (`5`) or its IRI (`/api/v2/shop/wishlists/5`).

| Method and path | Body | Result |
|---|---|---|
| `GET /wishlists` | - | the owner's lists, default first: `id`, `name`, `isDefault`, `itemsCount`, `wishlistToken` (no rows; `[]` when nothing is saved) |
| `POST /wishlists` | `{"name": "Christmas"}` | `201`, the new list (the default list is created first when missing) |
| `GET /wishlists/{id}` | - | one list with its `items` |
| `PATCH /wishlists/{id}` | `{"name": "Gifts"}` (`merge-patch+json`) | the list; the default list can be renamed too |
| `DELETE /wishlists/{id}` | - | `204`, rows included. The default list: `409` |
| `POST /wishlist/items/{id}/move` | `{"targetList": 5}` | the row in the target list (same variant already there: rows merge, larger quantity and older `addedAt` win) |

List choice on the row operations:

- `POST /wishlist/items` and `POST /wishlist/add-to-cart`: optional field `"list": 5` in the body.
- `GET` / `PATCH` / `DELETE /wishlist/items/{id}` and `POST .../move` (source list): optional query `?list=5`.
- Omitted: the default list, exactly as in phase 1. A row of another list is a `404` unless its list is given.

`name` of the default list is translated by the request locale ("My Favorites" / "I miei preferiti") until it is renamed.

Errors (message translated, `validators` domain): blank name, longer than 60 characters, name already used by the owner
(case-insensitive), more than 20 lists, move to the same list: `422`. Deleting the default list: `409`.
A list (or row) of another owner and a missing one answer the same `404`; the visitor token is only returned to its owner.

Admin (still read only, other methods answer `405`): `GET /admin/wishlists` and `/admin/wishlists/{id}` now show `name` (as stored)
and `isDefault`; one row is one list, so `?customer=<id or email>` returns all the lists of a customer, and `&channel=<code>` narrows it.

## Development

Unit tests (from the shop where the plugin is installed):

```bash
vendor/bin/phpunit -c plugins/sylius-wishlist-plugin/phpunit.xml.dist
```

Plugin conventions (where to add services, routes, translations, Twig hooks): [docs/architettura.md](docs/architettura.md).

## License

MIT, see [LICENSE](LICENSE).
