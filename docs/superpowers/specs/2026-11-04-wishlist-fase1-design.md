# Wishlist plugin per Sylius 2 — specifica fase 1

Data: 2026-11-04 · Autore: team bwlab (Luigi Massa capitano, Jigen sviluppo, Amelia marketing)
Contesto: PS-Summit Hackathon 2026, consegna entro le 18:00 su `github.com/bwlab/sylius-wishlist-plugin`.
Mappa dei processi (fonte delle decisioni PA-xx e dei GAP-xx): artifact "BPM Wishlist Sylius".

## 1. Perché

Il cliente (organizzatori dell'hackathon) chiede una wishlist per Sylius come **plugin riutilizzabile**,
accettato con una demo dal vivo, fase per fase. Ogni fase è un aggiornamento del plugin già installato.

Il plugin si progetta da due punti di vista:

- **chi compra**: salvare prodotti, ritrovarli, passarli al carrello;
- **il merchant** (riferimento: un grande marchio multi-paese che valuta bwlab come fornitore): la wishlist
  deve tradursi in vendite, quindi deve essere misurabile e poi usabile dal marketing.

Il risultato atteso è un prodotto completo e di qualità, non il minimo per passare la fase.

## 2. Obiettivi della fase 1

Gli 11 scenari del cliente (WL-01…11) più le decisioni prese nel brainstorming:

1. Salvare dalla pagina prodotto (PDP) e dalla lista prodotti (PLP), con messaggio di conferma, senza doppioni.
2. Wishlist del visitatore (stesso browser, sopravvive al refresh) e del cliente registrato (sopravvive a logout/login).
3. **Unione** della wishlist del visitatore in quella dell'account al primo accesso, anche dopo la registrazione (PA-02, vedi §12).
4. **Una wishlist per canale** (PA-04).
5. Pagina wishlist: nome, immagine, prezzo attuale (del canale), togliere, wishlist vuota con "continua gli acquisti".
6. **Quantità desiderata** per riga, default 1, modificabile (PA-08).
7. Al carrello: singolo prodotto, selezionati, **"Aggiungi tutti i disponibili"** (PA-06). I prodotti restano in wishlist.
8. Non acquistabile: scritte **"Esaurito"** e **"Non più disponibile"** (PA-03), non selezionabile, non aggiungibile.
9. Pannello: pagina **Wishlist > Impostazioni per canale** (PA-12). Prima impostazione: durata della wishlist del visitatore.
10. Durata wishlist visitatore **90 giorni** di default dall'ultima visita, configurabile; **pulizia** delle scadute con
    conteggi anonimi conservati (PA-05).
11. Testi traducibili ovunque (negozio, pannello, futuri email); traduzioni **solo `it` e `en`**, `en` di riserva (PA-13).
12. **API**: negozio complete; amministrazione **solo lettura** (wishlist e impostazioni) (PA-14).
13. **Dati per il merchant registrati da subito** (PA-10): data di aggiunta, pagina di origine, lingua dell'ultima visita,
    quantità. Servono alle statistiche future: non si possono ricostruire dopo.
14. Plugin installabile con composer, tabelle create con **migration**, README di installazione e aggiornamento.

## 3. Non obiettivi (fase 1)

- Statistiche e marketing del merchant (MP6, MP7): classifiche, conversioni, gruppi, avvisi email, export. Arrivano
  dopo aver letto le fasi 2-4 (PA-10). La fase 1 registra solo i dati.
- Più wishlist per cliente, condivisione, wishlist pubbliche.
- Modifica della wishlist di un cliente da parte del merchant (pannello o API).
- Traduzioni oltre `it`/`en`.

## 4. Anti-obiettivi (fallimento anche se "funziona")

- Modificare il core di Sylius o il negozio di prova (template, config dell'app) per far funzionare il plugin.
  Unica eccezione ammessa: le righe di installazione documentate nel README (bundle, routing, config import).
- Qualsiasi operazione sulla wishlist che modifichi il carrello senza un'azione esplicita "al carrello".
- Aggiornare il plugin tra fasi richiedendo di cancellare dati o ricreare il database.
- Bypassare le regole del carrello Sylius (disponibilità, scorte) quando si aggiunge dalla wishlist.
- Salvare dati personali del visitatore anonimo oltre al token del browser.

## 5. Vincoli

- Sylius **2.3** (negozio di prova: 2.3.2, PHP 8.3, Docker locale su `http://localhost`).
- Il negozio di prova ha 2 canali (`FASHION_WEB`, `WEB_EUR`), lingue attive nei canali `en_US` e `it_IT`.
- Pacchetto composer `bwlab/sylius-wishlist-plugin`, namespace `Bwlab\SyliusWishlistPlugin`, licenza MIT.
- Installato nel negozio di prova come pacchetto (repository composer `path` durante lo sviluppo), mai copiando codice nell'app.
- Tabelle con prefisso `bwlab_wishlist_`; migration Doctrine nel plugin, registrate dal README; migration rilasciate mai modificate.
- Ogni testo visibile passa dal traduttore (`messages` + `flashes`), chiavi `bwlab_wishlist.*`.
- Moduli HTML tradizionali con CSRF sono sufficienti; AJAX facoltativo.
- Inserimento nel tema solo tramite **Twig Hooks** di Sylius 2 (niente override dei template del negozio).

## 6. Decisioni prese (con chi le ha prese)

| Tema | Decisione | Fonte |
|---|---|---|
| Cosa si salva | La **variante** (`ProductVariant`). PDP: la variante scelta dal cliente. PLP: prodotto con una sola variante → salvata subito; con più varianti → il pulsante porta alla PDP | Luigi, PA-01 |
| Canale | Una wishlist per (proprietario, canale) | Luigi, PA-04 |
| Visitatore → cliente | Unione all'accesso e alla registrazione, nessun doppione | Luigi, PA-02 |
| Non acquistabile | `out_of_stock` → "Esaurito"; variante/prodotto disattivati o prodotto non nel canale → "Non più disponibile"; stesse regole del carrello Sylius | Luigi, PA-03 |
| Selezione multipla | Non acquistabili non selezionabili; "Aggiungi tutti i disponibili"; se uno si esaurisce nel frattempo gli altri entrano e un messaggio dice quale no e perché | Luigi, PA-06 |
| Quantità | Per riga, default 1, usata nell'aggiunta al carrello; scorte insufficienti → messaggio del carrello | Luigi, PA-08 |
| Durata visitatore | 90 giorni dall'ultima visita, per canale, dalla pagina impostazioni | Luigi, PA-05/PA-12 |
| Impostazioni | Pagina admin per canale; valori di partenza da config del bundle | Luigi, PA-12 |
| Lingue | `it`, `en`; `en` di riserva | Luigi, PA-13 |
| API admin | Solo lettura | Luigi, PA-14 |
| Statistiche/marketing | Dopo le fasi 2-4; ora solo registrazione dati | Luigi, PA-10 |

## 7. Modello dei dati (scelta del team, il builder può affinare i nomi)

- **Wishlist**: `id`, `channel` (obbligatorio), `customer` (nullable), `token` (stringa casuale univoca, sempre valorizzata),
  `localeCode` (lingua dell'ultima visita), `createdAt`, `updatedAt`, `lastSeenAt`.
  Vincoli: univoco (`customer`, `channel`) quando `customer` non è nullo; univoco `token`.
- **WishlistItem**: `id`, `wishlist`, `variant`, `quantity` (≥ 1), `addedAt`, `source` (`pdp` | `plp` | `api`).
  Vincolo univoco (`wishlist`, `variant`). Cancellare un prodotto o una variante dal catalogo non deve rompere la pagina:
  la riga si rimuove (cascade a livello DB sulla variante) — scelta del team.
- **WishlistChannelSettings**: `channel` (univoco), `guestLifetimeDays` (default 90). Estendibile con altre impostazioni nelle fasi future.
- **WishlistPurgeStat** (conteggi anonimi): `channel`, `variant`, `purgedAt` (giorno), `itemsCount`, `quantitySum`. Nessun dato personale.

Tutte registrate come risorse Sylius dove serve (factory/repository), create con migration.

## 8. Comportamenti chiave

- **Identificazione visitatore**: cookie `bwlab_wishlist` con il token (HttpOnly, SameSite=Lax, Secure su HTTPS),
  durata = `guestLifetimeDays` del canale, rinnovata a ogni visita. Wishlist risolta da (token, canale corrente).
  La wishlist si crea solo al primo salvataggio (niente righe vuote per ogni visitatore).
- **Cliente registrato**: wishlist risolta da (customer, canale corrente).
- **Unione**: all'evento di login del firewall `shop` (anche il primo accesso dopo la registrazione, vedi §12). Le righe del visitatore
  passano all'account; variante già presente → una riga sola con la quantità maggiore (scelta del team). La wishlist del visitatore
  si elimina, il cookie si cancella.
- **Doppioni**: aggiungere una variante già presente non crea righe; mostra comunque la conferma ("già nella wishlist").
- **Prezzo**: quello attuale del canale corrente, calcolato con i servizi Sylius (stesso prezzo che mostrerebbe la PDP).
- **Disponibilità**: un solo servizio che restituisce `available` | `out_of_stock` | `unavailable`, usando i servizi Sylius
  (inventory/availability checker, `enabled` di prodotto e variante, prodotto nel canale). Usato da pagina, API e aggiunta al carrello.
- **Al carrello**: si usano carrello corrente e servizi di Sylius (cart context, order modifier/quantity modifier, validazioni
  dell'aggiunta al carrello). Nessun carrello parallelo. Le righe restano nella wishlist.
- **Pulizia**: comando console (es. `bwlab:wishlist:purge-expired`) che, per ogni canale, elimina le wishlist di visitatori con
  `lastSeenAt` oltre la durata, dopo aver sommato i conteggi in `WishlistPurgeStat`. Il README indica il cron.
- **Dati merchant**: `addedAt`, `source`, `localeCode`, `quantity` si registrano a ogni operazione, anche via API.

## 9. Interfaccia

- **PDP**: pulsante "Aggiungi alla wishlist" vicino all'aggiunta al carrello; salva la variante selezionata.
- **PLP**: pulsante su ogni scheda prodotto (vedi regola varianti).
- **Testata**: link "Wishlist" con contatore delle righe.
- **Pagina wishlist** (`/{_locale}/wishlist`): elenco con immagine, nome (+ variante), prezzo, quantità modificabile,
  stato, "Togli", "Aggiungi al carrello", casella di selezione, "Aggiungi i selezionati", "Aggiungi tutti i disponibili";
  stato vuoto con link al catalogo.
- **Pannello**: voce di menu "Wishlist > Impostazioni"; elenco canali con modulo per canale; testi tradotti.

## 10. API (API Platform di Sylius)

Principio (Luigi, PA-11): gli strumenti di marketing del merchant sono esterni e si integrano via API. Le API devono
essere **complete e coerenti con le funzioni**: ogni cosa che si fa o si vede dall'interfaccia (negozio) o si legge nel
pannello ha la sua API, con gli stessi dati (inclusi `addedAt`, `source`, `quantity`, `localeCode`, stato di disponibilità).
Le fasi future aggiungono API insieme alle funzioni, mai dopo.

- **Negozio** (`/api/v2/shop/...`): leggere la wishlist del canale corrente, aggiungere variante (con quantità), cambiare quantità,
  togliere riga, aggiungere una o più righe al carrello indicato dal suo token. Cliente: autenticato con JWT; visitatore:
  identificato dal token della wishlist (scelta del team: il builder definisce il trasporto, es. path o header).
  Un utente non accede mai alla wishlist di un altro.
- **Amministrazione** (`/api/v2/admin/...`): solo GET. Elenco wishlist filtrabile per cliente e canale, dettaglio con righe,
  impostazioni per canale.
- Nota ambiente: nel negozio di prova mancano le chiavi JWT di sviluppo; si creano solo con l'ok di Luigi.

## 11. Collaudo

Scenari da provare sul browser (e via API dove indicato), con esito riportato nella mappa:

- Cliente: WL-01…11 (testo del cliente).
- Team: WL-12 installazione su negozio pulito; WL-13 aggiornamento tra fasi con dati presenti; WL-14/15 unione all'accesso;
  WL-16 non più disponibile; WL-17 durata e pulizia; WL-18/19 lingue negozio/pannello; WL-20/21 aggiungi tutti e caso limite;
  WL-22 quantità; WL-23/24 API negozio/amministrazione.
- Test automatici minimi sulla logica non banale (unione, disponibilità, pulizia).

## 12. Scelte del team dopo la revisione

- **Testi del cliente**: copiati in `docs/requisiti/` (fase 1 con scenari e criteri di accettazione; regole dell'hackathon).
  Sono la fonte per il collaudo: in caso di dubbio vale il testo del cliente.
- **Unione alla registrazione** (corretta dopo la revisione di sicurezza, 4/11): alla registrazione NON si lega nulla.
  Sylius riusa un cliente già esistente da ospite con la stessa email, quindi legare la wishlist in quel momento la
  sposterebbe sull'account di un'altra persona prima della verifica dell'email. La wishlist del visitatore resta nel suo
  cookie e si unisce al **primo accesso** (`security.interactive_login`, che scatta anche nell'accesso automatico quando la
  verifica è disattivata). Se l'unione fallisce, l'accesso non si blocca e il cookie resta.
- **WL-12 installazione**: si prova seguendo il README da zero sul negozio di prova, che non ha il plugin installato.
  Nessun secondo negozio, nessun reset del database.

## 13. Ordine di consegna (Luigi, 4/11 ore 15:40)

Ogni blocco parte solo quando il precedente funziona sul browser. Ciò che non entra oggi resta in questa specifica.

1. **Base, consegna garantita**: WL-01…11, unione all'accesso e alla registrazione, wishlist per canale, registrazione dei
   dati merchant, migration, README, traduzioni `it`/`en`. Poi richiesta di accettazione della fase 1.
2. Quantità desiderata, "Aggiungi tutti i disponibili", pagina impostazioni per canale con durata e pulizia.
3. API del negozio.
4. API di amministrazione in sola lettura.

## 14. Lasciato al builder

- Struttura interna del bundle, nomi di servizi, rotte e classi.
- Nomi esatti dei Twig Hooks (da verificare sul negozio reale con gli strumenti `vendor/bin/mate`).
- Trasporto del token visitatore nelle API.
- Aspetto grafico: semplice, coerente con il tema standard.
