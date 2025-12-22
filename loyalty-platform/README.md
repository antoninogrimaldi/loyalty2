# Loyalty Middleware Platform (Shopify + Odoo)

Una piattaforma loyalty middleware semplice, pensata per ambienti XAMPP (Apache + PHP 8 + MySQL/phpMyAdmin). Offre area cliente e backoffice admin, sincronizzazioni base con Shopify e Odoo, e gestione punti/offerte/coupon.

## Requisiti minimi
- PHP 8.x (estensioni: PDO MySQL, curl, json, openssl, session)
- MySQL 5.7+ / MariaDB
- Apache con mod_php (XAMPP va benissimo)
- Composer (solo per PHPMailer e libreria QR) opzionale

## Setup rapido per principianti
1. **Installa XAMPP** (Apache + MySQL + phpMyAdmin).
2. Clona o copia questa cartella `loyalty-platform/` nella tua directory web (es: `htdocs/`).
3. Crea un database vuoto via phpMyAdmin (es: `loyalty_platform`).
4. Importa lo schema eseguendo `app/db/migrations.sql` da phpMyAdmin.
5. Copia `app/config/env.php.example` in `app/config/env.php` e compila i valori (DB, SMTP, Shopify, Odoo, APP_URL, SESSION_SECRET).
6. Avvia Apache e MySQL da XAMPP.
7. Naviga su `http://localhost/loyalty-platform/public/`.
8. Crea il primo admin eseguendo una query SQL (vedi sezione seed) o usando phpMyAdmin:
   ```sql
   INSERT INTO users (email, username, phone, password_hash, is_admin, status, email_verified_at, created_at)
   VALUES ('admin@example.com','admin','0000000000', PASSWORD_HASH('Admin123!', 'bcrypt'), 1, 'active', NOW(), NOW());
   ```
   In MySQL usa `SELECT password_hash('Admin123!');` in PHP o genera via PHP CLI: `php -r "echo password_hash('Admin123!', PASSWORD_DEFAULT);";` e incolla il valore.
9. Testa registrazione e verifica email: registrati come nuovo cliente, controlla l'email (SMTP configurato) e clicca il link di verifica.
10. Lancia manualmente gli script di sync da riga di comando: `php scripts/run_sync.php`.

## Struttura cartelle
- `public/` front controller e asset statici.
- `app/config/` configurazioni e variabili ambiente.
- `app/db/` connessione e migrazioni.
- `app/helpers/` funzioni di supporto (auth, CSRF, Shopify, Odoo, punti, QR, mailer, GDPR, logger).
- `app/models/` classi dati base per tipizzazione semplice.
- `app/controllers/` logica per autenticazione, area cliente, admin e sync.
- `app/views/` template Bootstrap 5.
- `scripts/` job di sincronizzazione e ricalcolo punti.
- `odoo_module/` modulo Odoo minimo.

## Comandi utili
- `php -S localhost:8000 -t public` per servire localmente senza Apache.
- `php scripts/run_sync.php` esegue tutte le sincronizzazioni (prodotti, ordini, resi, punti).

## Sicurezza di base
- Hash password con `password_hash`/`password_verify` (bcrypt).
- CSRF token su form mutanti.
- Session cookie con SameSite e HttpOnly.
- Rate limit su login/registrazione basato su IP.
- Prepared statement PDO ovunque.
- Audit log azioni admin e cambi offerta.

## Limiti fase 1
- Social login non implementato (pulsanti disabilitati); previsto in roadmap.
- Codice sconto Shopify applicato via link `/discount/CODE` (non auto-applicato invisibilmente).
- Fulfillment premi manuale (no generazione automatica coupon/ordini Shopify).

## Roadmap fase 2
- Social login (Google/Apple) con OAuth.
- Auto-applicazione sconto su Shopify cart/checkout.
- Webhook bidirezionali Shopify/Odoo.
- Fulfillment premi automatico (coupon o ordine draft su Shopify / Odoo).
- UI avanzata, tracciamento eventi marketing, dashboard KPI evoluta.

