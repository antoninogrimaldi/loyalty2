# Loyalty Hub

Piattaforma loyalty minimale scritta in PHP/JS/HTML/CSS con MySQL, pensata per XAMPP/Apache e pronta per integrazioni con Shopify e Odoo.

## Setup rapido
1. Copia la cartella nel document root di XAMPP (`htdocs/`). Se usi una sottocartella, aggiorna `base_url` in `config/config.php`.
2. Importa il database:
   ```bash
   mysql -u root -p < schema.sql
   ```
3. Aggiorna le credenziali in `config/config.php` se necessario. Di default `base_url` è vuoto per installazione diretta in `htdocs`.
4. Apri `http://localhost/` (o il percorso scelto): verrai reindirizzato al login/dashboard. Admin di default: `admin@example.com` / `admin123`.

## Funzioni incluse
- Registrazione con campi Nome, Cognome, Codice fiscale, Telefono, Email, CAP, Indirizzo e raccolta consensi (trattamento dati, profilazione, marketing) con timestamp e password policy forte.
- Login con password (throttling anti-brute-force + rehash automatico) e stub per social login da completare con OAuth.
- Home con carta fedeltà, saldo punti, livello, coupon attivi, offerte personalizzate e ultimi ordini.
- Pagina profilo per aggiornare i dati personali e visualizzare i consensi registrati.
- Pagina offerte personalizzate: ogni cliente può scegliere max 2 prodotti (identificati da EAN), con limite di 2 cambi/anno tracciati a log. La UI mostra il catalogo con immagini, brand, categoria e descrizione.
- Backoffice per admin: gestione clienti, punti, coupon e catalogo (SKU + EAN master + ID Odoo/Shopify per mapping cross piattaforma) con preview delle immagini prodotto.

## Sicurezza e GDPR
- Password con `password_hash` + rehash automatico, sessioni HttpOnly/SameSite (Strict) e rigenerate, query prepared, token CSRF nei form, throttling login e validazione CF/CAP/telefono.
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

## Shopify (fase 2)
1. Imposta in `config/config.php` il blocco `shopify` con `domain`, `access_token`, `api_version` e un `sync_secret` robusto.
2. Creazione/aggiornamento clienti: la registrazione e l’aggiornamento del profilo eseguono un upsert su Shopify, aggiungono il tag `fidelity` e sincronizzano `accepts_marketing` con il consenso marketing della piattaforma.
3. Import prodotti: chiama l’endpoint protetto `public/api/shopify_products.php?token=SYNC_SECRET` (da cron o webhook) per importare/aggiornare tutte le varianti del catalogo Shopify nella tabella `products` (mappando barcode→EAN, SKU, vendor, product_type, descrizione, immagine e prezzo). 
4. I dati vengono normalizzati e gli update sono idempotenti grazie alle chiavi univoche SKU/EAN.
