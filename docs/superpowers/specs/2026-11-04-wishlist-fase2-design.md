# Wishlist plugin per Sylius 2 — specifica fase 2 (liste multiple con nome)

Data: 2026-11-04 · Testo del cliente: `docs/requisiti/fase-2-liste-multiple.txt` (14 scenari, criteri di accettazione).
Parte dalla fase 1 (`docs/superpowers/specs/2026-11-04-wishlist-fase1-design.md`), che resta valida in tutto ciò che qui non cambia.
**La fase 2 è un aggiornamento del plugin già installato**: la versione 0.1.0 (fase 1) si aggiorna alla 0.2.0 con una migration nuova.
I dati salvati non si perdono, le funzioni della fase 1 continuano a funzionare.

## 1. Cosa cambia

Ogni cliente **e ogni visitatore** può avere più liste, ognuna con un nome e le sue righe. Una variante può stare in più liste, una sola volta per lista.

## 2. Decisioni (scelte del team, non ancora viste dal cliente: Luigi può cambiarle)

| Tema | Decisione |
|---|---|
| Lista predefinita | Ogni proprietario (cliente o visitatore, per canale) ha sempre una lista predefinita, creata al primo salvataggio, chiamata «My Favorites» (traducibile: it «I miei preferiti»). Si può rinominare, **non si può eliminare** |
| Migrazione dalla fase 1 | La wishlist esistente di ogni cliente e visitatore diventa la sua lista predefinita; nessuna riga persa |
| Nomi | Obbligatorio, tolti gli spazi iniziali e finali, non vuoto e non di soli spazi, massimo 60 caratteri; unico per proprietario e canale **senza distinguere maiuscole e minuscole**. Messaggio di errore tradotto |
| Numero di liste | Massimo 20 per proprietario e canale (valore fisso nel codice per ora) |
| Visitatore | Il cookie `bwlab_wishlist` identifica il **proprietario**, non più una lista: stesse regole di durata e pulizia della fase 1, valide per tutte le sue liste |
| Aggiunta da PDP e PLP | Un menu per scegliere la lista di destinazione, preselezionata l'ultima usata (predefinita la prima volta), con la voce «Nuova lista…» che chiede il nome. Con una sola lista il menu non compare e il comportamento è quello della fase 1 |
| Stessa variante | In una lista già presente: nessun doppione, messaggio «già nella lista X». In un'altra lista: si aggiunge |
| Spostare | Operazione dedicata: sposta la riga nella lista di destinazione. Se la variante c'è già lì: la riga di origine si toglie e si tiene la quantità maggiore |
| Eliminare | Pagina o finestra di conferma. Si eliminano la lista e le sue righe, mai prodotti del catalogo né altre liste |
| Accesso | Una lista o una riga di un altro proprietario risponde 404 identico a «inesistente». Mai accesso per identificatore indovinato |
| Unione all'accesso | Il visitatore accede: la sua lista predefinita si unisce a quella predefinita dell'account (regola della fase 1); le sue altre liste passano all'account; se un nome esiste già nell'account le due liste si uniscono (quantità maggiore, nessun doppione); oltre il massimo di 20, le liste in più si uniscono alla predefinita |
| Carrello | Dentro ogni lista funzionano le azioni della fase 1 (singolo, selezionati, tutti i disponibili), con le righe che restano nella lista |
| Pagina wishlist | `/{_locale}/wishlist` mostra l'elenco delle liste (nome, numero di prodotti), con crea, rinomina, elimina; `/{_locale}/wishlist/{id}` (o equivalente scelto dal builder) mostra una lista; la lista predefinita si apre con un clic dal link in testata. Il contatore in testata somma le righe di tutte le liste |
| Pannello | Nessun cambiamento in questa fase |
| API | Negozio: elenco liste, creare, rinominare, eliminare, spostare riga; le operazioni sulle righe accettano la lista (se omessa vale la predefinita, così i client della fase 1 non si rompono). Amministrazione: sola lettura, ora con le liste del cliente |
| Dati per il merchant | Ogni riga conserva `addedAt`, `source`, `quantity`; ogni lista `createdAt`, `name`. Spostare conserva `addedAt` della riga |

## 3. Compatibilità con la fase 1 (vincolo di consegna)

- Una nuova migration, mai modificando quelle esistenti, che aggiunge nome e lista predefinita, cambia i vincoli di unicità e valorizza «My Favorites» per tutte le wishlist esistenti. Deve funzionare su un database con wishlist già salvate.
- Prova obbligatoria (scenario WL-13): database con dati della fase 1 → aggiornamento → le liste esistenti ci sono, predefinite, con le stesse righe.
- Le rotte e le API della fase 1 restano; quelle senza indicazione di lista agiscono sulla predefinita.
- Il comando di pulizia resta valido: elimina tutte le liste di un visitatore scaduto, con i conteggi anonimi.

## 4. Anti-obiettivi

- Perdere righe o liste nell'aggiornamento o nell'unione.
- Una lista visibile o modificabile da chi non è il proprietario.
- Rompere un client che usa le API o le rotte della fase 1.
- Toccare il core di Sylius o il negozio di prova.

## 5. Collaudo (scenari del cliente WL2-01…14 = scenari 1…14 del testo; del team: WL2-15 aggiornamento da 0.1.0 con dati, WL2-16 unione di liste all'accesso, WL2-17 nome duplicato e oltre il massimo, WL2-18 API)

## 6. Lasciato al builder

Nomi di tabelle e classi, forma delle rotte, il modo preciso di riconoscere il proprietario visitatore nel cookie (stesso nome e durata della fase 1), aspetto dell'interfaccia.
