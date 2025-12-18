# Loyalty Hub

Piattaforma loyalty minimale scritta in PHP/JS/HTML/CSS con MySQL, pensata per XAMPP/Apache e pronta per integrazioni con Shopify e Odoo.

## Setup rapido
1. Copia la cartella nel document root di XAMPP (es. `htdocs/loyalty2`).
2. Importa il database:
   ```bash
   mysql -u root -p < schema.sql
   ```
3. Aggiorna le credenziali in `config/config.php` se necessario. Se cambi il nome della cartella, aggiorna anche `base_url` (es. impostalo a `""` se metti i file direttamente in `htdocs`).
4. Apri `http://localhost/loyalty2` (o il percorso scelto): verrai reindirizzato al login/dashboard. Admin di default: `admin@example.com` / `admin123`.

## Funzioni incluse
- Registrazione con campi Nome, Cognome, Codice fiscale, Telefono, Email, CAP, Indirizzo e raccolta consensi (trattamento dati, profilazione, marketing) con timestamp.
- Login con password + stub per social login da completare con OAuth.
- Home con carta fedeltà, saldo punti, livello, coupon attivi, offerte personalizzate e ultimi ordini.
- Pagina profilo per aggiornare i dati personali e visualizzare i consensi registrati.
- Pagina offerte personalizzate: ogni cliente può scegliere max 2 prodotti (identificati da EAN), con limite di 2 cambi/anno tracciati a log. La UI mostra il catalogo con immagini, brand, categoria e descrizione.
- Backoffice per admin: gestione clienti, punti, coupon e catalogo (SKU + EAN master + ID Odoo/Shopify per mapping cross piattaforma) con preview delle immagini prodotto.

## Sicurezza e GDPR
- Password con `password_hash`, sessioni HttpOnly/SameSite e rigenerate, query prepared, token CSRF nei form.
- Consensi salvati nella tabella `user_consents` con timestamp e check obbligatorio per il trattamento dati.
- Minimizzazione dati: solo gli attributi richiesti, nota su diritto di cancellazione/esportazione.
- Per social login/OAuth salva solo gli identificativi strettamente necessari.

## Integrazioni successive
- **Shopify**: sincronizza clienti, ordini e coupon usando le API Admin; mappa email/phone e aggiorna tabella `orders` e `coupons`. Usa l'EAN come identificatore prodotto o i campi `shopify_product_id` nella tabella `products`.
- **Odoo**: sincronizza catalogo (`products`) usando `ean` come chiave primaria condivisa; verifica coupon in fase di checkout POS con un endpoint PHP protetto.

## Struttura
- `public/` pagine principali (login, register, home, profilo, offerte, backoffice).
- `includes/` helper di sicurezza, sessione e DB.
- `assets/` stile e script UI responsive.
- `schema.sql` definizione database con dati demo, EAN/sku e consensi preconfigurati.
