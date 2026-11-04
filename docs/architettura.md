# Architettura del plugin (convenzioni)

Namespace `Bwlab\SyliusWishlistPlugin\` → `src/`. Bundle `BwlabSyliusWishlistPlugin`, chiave di config `bwlab_sylius_wishlist`.
Prefisso di servizi, rotte e traduzioni: `bwlab_wishlist`.

## Dove va cosa (un file per area = niente conflitti)

| Area | Servizi | Rotte | Twig hooks | Template |
|---|---|---|---|---|
| dominio (fase A) | `config/services/domain.yaml` | — | — | — |
| negozio | `config/services/shop.yaml` | `config/routes/shop/*.yaml` (prefisso `/{_locale}`) | `config/twig_hooks/shop/*.yaml` | `templates/shop/` |
| riconoscimento/unione | `config/services/identity.yaml` | — | — | — |
| pannello/pulizia | `config/services/admin.yaml` | `config/routes/admin/*.yaml` (prefisso `/%sylius_admin.path_name%`) | `config/twig_hooks/admin/*.yaml` | `templates/admin/` |
| API | `config/services/api.yaml` | `config/routes/api/*.yaml` (nessun prefisso) | — | — |

- **Servizio nuovo**: nel file della propria area. Id `bwlab_wishlist.<area>.<nome>`, più alias FQCN dell'interfaccia.
  I file sono caricati per glob (`config/services/*.yaml`): un file nuovo nella cartella entra da solo.
- **Rotta nuova**: file `.yaml` nella cartella dell'area (caricata per glob). Nome `bwlab_wishlist_<area>_<azione>`.
- **Traduzione**: `translations/messages.{en,it}.yaml` (testi interfaccia, chiavi `bwlab_wishlist.ui.*`) e
  `translations/flashes.{en,it}.yaml` (messaggi flash, chiavi `bwlab_wishlist.*`). Sempre entrambe le lingue; `en` di riserva.
  Ogni area aggiunge le proprie chiavi sotto un sotto-nodo proprio (`bwlab_wishlist.ui.shop.*`, `bwlab_wishlist.ui.admin.*`) per ridurre i conflitti.
- **Twig hook**: file `.yaml` in `config/twig_hooks/<area>/` con `sylius_twig_hooks: hooks: ...`; template in
  `@BwlabSyliusWishlistPlugin/<area>/...`. Nome dell'hook da verificare con
  `vendor/bin/mate tools:call sylius_hooks_find_for_template`. Mai override dei template Sylius.
- **Migration**: `src/Migrations/`, namespace `Bwlab\SyliusWishlistPlugin\Migrations`, generata con
  `bin/console doctrine:migrations:diff --namespace='Bwlab\SyliusWishlistPlugin\Migrations' --filter-expression='/^bwlab_wishlist_/'`.
  Le migration già committate non si modificano.
- **Entità**: classe + interfaccia in `src/Entity/`, mappatura con attributi PHP Doctrine (`#[ORM\...]`, colonne snake_case esplicite) sulla classe,
  risorsa in `config/config.yaml` (`sylius_resource.resources.bwlab_wishlist.<alias>`).

## Risorse Sylius (servizi generati)

| Alias | Entità / interfaccia | Tabella |
|---|---|---|
| `bwlab_wishlist.wishlist` | `Entity\Wishlist` / `WishlistInterface` (repository `Repository\WishlistRepository`) | `bwlab_wishlist_wishlist` |
| `bwlab_wishlist.wishlist_item` | `Entity\WishlistItem` / `WishlistItemInterface` | `bwlab_wishlist_item` |
| `bwlab_wishlist.channel_settings` | `Entity\WishlistChannelSettings` / `WishlistChannelSettingsInterface` | `bwlab_wishlist_channel_settings` |
| `bwlab_wishlist.purge_stat` | `Entity\WishlistPurgeStat` / `WishlistPurgeStatInterface` | `bwlab_wishlist_purge_stat` |

Per ogni alias: `bwlab_wishlist.repository.<x>`, `bwlab_wishlist.factory.<x>`, `bwlab_wishlist.manager.<x>`, `bwlab_wishlist.controller.<x>`.
Salvataggio: `$repository->add($entity)` (persist + flush), rimozione `$repository->remove($entity)`.

Vincoli DB: univoco (`customer_id`, `channel_id`) e univoco `token` sulla wishlist; univoco (`wishlist_id`, `variant_id`) sulle righe;
cancellare variante, canale o cliente cancella le righe collegate (`ON DELETE CASCADE`).

## Servizi di dominio (fase A)

| Id | Interfaccia (autowiring) | Uso |
|---|---|---|
| `bwlab_wishlist.resolver.current_wishlist` | `Resolver\CurrentWishlistResolverInterface` | `find()`: wishlist corrente o `null`, non crea mai. `getOrCreate()`: crea in memoria se manca; chi aggiunge la prima riga fa `$wishlistRepository->add($wishlist)`. Cliente loggato → (customer, canale); visitatore → token del cookie + canale. |
| `bwlab_wishlist.modifier.wishlist_item` | `Modifier\WishlistItemModifierInterface` | `addVariant($wishlist, $variant, $qty = 1, $source = 'pdp')` → `true` aggiunta, `false` già presente (flash "già nella wishlist"); `removeVariant()`; `changeQuantity()`. Solo memoria: poi `add()` sul repository. |
| `bwlab_wishlist.checker.variant_availability` | `Checker\VariantAvailabilityCheckerInterface` | `check($variant, $channel)` → enum `Checker\WishlistAvailability` (`available` / `out_of_stock` / `unavailable`), `isPurchasable()`, `translationKey()`. |
| `bwlab_wishlist.provider.guest_lifetime` | `Provider\GuestLifetimeProviderInterface` | giorni di durata della wishlist visitatore del canale (impostazioni canale, altrimenti `bwlab_sylius_wishlist.guest_lifetime_days`). |
| `bwlab_wishlist.listener.wishlist_cookie` | — | `kernel.response`: salva `lastSeenAt`/`localeCode` (al massimo una scrittura al giorno) e imposta/rinnova il cookie `bwlab_wishlist` (HttpOnly, SameSite=Lax, Secure su HTTPS) per le wishlist visitatore già salvate. |

Costanti utili: `WishlistItemInterface::SOURCE_PDP|SOURCE_PLP|SOURCE_API`, `CurrentWishlistResolverInterface::REQUEST_ATTRIBUTE`.
Parametri: `%bwlab_wishlist.guest_lifetime_days%`, `%bwlab_wishlist.cookie_name%`.

Esempio (negozio, aggiunta):

```php
$wishlist = $resolver->getOrCreate();
$added = $modifier->addVariant($wishlist, $variant, 1, WishlistItemInterface::SOURCE_PDP);
$wishlistRepository->add($wishlist);
```

## Fase 2 — liste multiple (contratti della base S2A)

Modello (`bwlab_wishlist_wishlist`, una riga = una lista):
- `name` (max 60, già senza spazi ai lati), `nameCanonical` (minuscolo, collation `utf8mb4_bin`: chiave dell'unicità senza maiuscole/minuscole), `isDefault`.
- Proprietario: cliente → `customer`; visitatore → `token` = valore del cookie `bwlab_wishlist`, **uguale per tutte le sue liste**.
- Vincoli unici: `(customer_id, channel_id, name_canonical)` e `(token, channel_id, name_canonical)`. Tolti quelli della fase 1 (un cliente per canale, token unico).
- Costanti `WishlistInterface::DEFAULT_NAME = 'My Favorites'`, `NAME_MAX_LENGTH = 60`, `MAX_LISTS = 20`. La lista predefinita ha nome salvato «My Favorites»: l'interfaccia può mostrarla tradotta (`isDefault()` e nome invariato).
- Le righe (`WishlistItem`) non cambiano: unicità `(wishlist_id, variant_id)` = stessa variante una volta per lista, in più liste sì.

`Resolver\CurrentWishlistResolverInterface` (proprietario corrente, canale corrente):

| Metodo | Contratto |
|---|---|
| `find(): ?WishlistInterface` | lista **predefinita**, non crea (fase 1 invariata) |
| `getOrCreate(): WishlistInterface` | predefinita o nuova in memoria (nome «My Favorites», `isDefault`); chi aggiunge la prima riga fa `add()` (fase 1 invariata) |
| `findAll(): list<WishlistInterface>` | tutte le liste, predefinita prima, poi per data di creazione; `[]` se il visitatore non ha cookie |
| `findOwned(int $id): ?WishlistInterface` | la lista se è del proprietario corrente, altrimenti `null` → rispondere **404** sia per "non tua" sia per "inesistente" |
| `getTarget(?int $listId = null): ?WishlistInterface` | destinazione di un'aggiunta: id dato → `findOwned` (null = 404); id omesso → predefinita (`getOrCreate`, compatibile fase 1/API) |
| `getLastUsed(): WishlistInterface` | lista da preselezionare nel menu PDP/PLP: quella con `updatedAt` più recente, altrimenti la predefinita |
| `create(string $name): WishlistInterface` | valida, crea e **salva** (e salva prima la predefinita se manca). Lancia `Exception\WishlistNameException` |
| `rename(WishlistInterface $w, string $name): void` | valida e **salva**; anche la predefinita. Lancia `WishlistNameException` |
| `delete(WishlistInterface $w): void` | elimina lista e righe. Lancia `Exception\WishlistOperationException::DELETE_DEFAULT` sulla predefinita |

`rename()` e `delete()` non ricontrollano il proprietario: passare solo liste ottenute da `findOwned()`.

`Modifier\WishlistItemModifierInterface`:
- `addVariant($wishlist, ...)` invariato: la lista la sceglie il chiamante (`getTarget($listId)`). `false` = già in **quella** lista (messaggio «già nella lista X»).
- `moveItem(WishlistItemInterface $item, WishlistInterface $target): WishlistItemInterface` — sposta la riga (conserva `addedAt`, quantità, origine); se la variante c'è già in destinazione toglie l'origine e tiene quantità maggiore e `addedAt` più vecchio; ritorna la riga rimasta. Solo memoria: poi `add()` su repository per entrambe le liste. Stessa lista → `WishlistOperationException::SAME_LIST`. Il proprietario di entrambe lo controlla il chiamante (`findOwned`).

Validazione nomi: `Policy\WishlistNamePolicy::check(string $name, iterable $ownerLists, ?WishlistInterface $renamed = null): string` (pura, ritorna il nome senza spazi ai lati). Errori = `WishlistNameException`, `getMessage()` è la chiave di traduzione del dominio **`validators`**, parametri in `->parameters`:
`bwlab_wishlist.list.name.not_blank`, `.name.max_length` (`%limit%`), `.name.unique` (`%name%`), `bwlab_wishlist.list.max_lists` (`%limit%`); `WishlistOperationException`: `bwlab_wishlist.list.delete_default`, `bwlab_wishlist.list.move_same_list`. Uso: `$translator->trans($e->getMessage(), $e instanceof WishlistNameException ? $e->parameters : [], 'validators')`.

Repository (`WishlistRepositoryInterface`): `findOneByCustomerAndChannel()` / `findOneGuestByTokenAndChannel()` ora ritornano la **predefinita**; nuovi `findAllByCustomerAndChannel()`, `findAllGuestByTokenAndChannel()`.

Unione all'accesso (`Identity\WishlistMerger::mergeAll($guestLists, $customerLists, $customer)` → `['save' => [...], 'delete' => [...]]`, pura; `GuestWishlistClaimer` salva/elimina): predefinita visitatore → predefinita cliente (regola fase 1); altre liste → passano all'account; stesso nome (senza maiuscole) → le due liste si uniscono; oltre 20 → unite alla predefinita.

Pulizia visitatori: la scadenza è per **proprietario** (token): si eliminano tutte le sue liste quando nessuna è stata vista dopo la soglia. Il visitatore resta "visto" tramite la predefinita (il resolver aggiorna `lastSeenAt` su `find()`/`getOrCreate()`).

Contatore in testata: somma `countItems()` di `findAll()`.

Esempio (negozio, aggiunta in una lista scelta):

```php
$wishlist = $resolver->getTarget($listId) ?? throw new NotFoundHttpException();
$added = $modifier->addVariant($wishlist, $variant, 1, WishlistItemInterface::SOURCE_PDP);
$wishlistRepository->add($wishlist);
```

## Test

`tests/Unit/` (PHPUnit). Dallo shop: `vendor/bin/phpunit --bootstrap vendor/autoload.php plugins/sylius-wishlist-plugin/tests/Unit`.
