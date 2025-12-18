# Loyalty Hub

Piattaforma loyalty minimale scritta in PHP/JS/HTML/CSS con MySQL, pensata per XAMPP/Apache e pronta per integrazioni con Shopify e Odoo.

## Setup rapido
1. Copia la cartella nel document root di XAMPP (es. `htdocs/loyalty2`).
2. Importa il database:
   ```bash
   mysql -u root -p < schema.sql
   ```
3. Aggiorna le credenziali in `config/config.php` se necessario.
4. Accedi all'app su `http://localhost/loyalty2/public/login.php`. Admin di default: `admin@example.com` / `admin123`.

## Funzioni incluse
- Registrazione con campi Nome, Cognome, Codice fiscale, Telefono, Email, CAP, Indirizzo.
- Login con password + stub per social login da completare con OAuth.
- Home con carta fedeltà, saldo punti, livello, coupon attivi, offerte personalizzate e ultimi ordini.
- Pagina profilo per aggiornare i dati personali.
- Pagina offerte personalizzate: ogni cliente può scegliere max 2 prodotti, con limite di 2 cambi/anno.
- Backoffice per admin: gestione clienti, punti, coupon e catalogo (in vista di sync con Odoo/Shopify).

## Sicurezza e GDPR
- Password con `password_hash`, sessioni rigenerate, query prepare, token CSRF nei form.
- Minimizzazione dati: solo gli attributi richiesti, nota su diritto di cancellazione/esportazione.
- Per social login/OAuth salva solo gli identificativi strettamente necessari.

## Integrazioni successive
- **Shopify**: sincronizza clienti, ordini e coupon usando le API Admin; mappa email/phone e aggiorna tabella `orders` e `coupons`.
- **Odoo**: sincronizza catalogo (`products`) e verifica coupon in fase di checkout POS con un endpoint PHP protetto.

## Struttura
- `public/` pagine principali (login, register, home, profilo, offerte, backoffice).
- `includes/` helper di sicurezza, sessione e DB.
- `assets/` stile e script UI responsive.
- `schema.sql` definizione database con dati demo.
