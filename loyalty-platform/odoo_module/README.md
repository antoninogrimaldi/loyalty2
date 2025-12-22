# Modulo Odoo Loyalty Middleware (base)

Questo modulo aggiunge campi custom su `res.partner` per memorizzare le offerte personalizzate provenienti dal middleware e un hook semplice per applicare sconti su `sale.order` in base ai codici a barre (EAN).

## Installazione
1. Copia la cartella `loyalty_middleware` dentro la directory `addons` di Odoo.
2. Aggiorna la lista moduli e installa **Loyalty Middleware** dal backend Odoo.
3. Verifica che il partner abbia i campi:
   - `x_offer_prod1_ean`
   - `x_offer_prod2_ean`
   - `x_offer_discount_pct`
   - `x_offer_updated_at`

## Funzionalità
- Salvataggio dei campi custom su res.partner.
- Metodo `apply_loyalty_discount` su `sale.order` che applica uno sconto percentuale alle righe prodotto con barcode corrispondente.

## Estensione futura (fase 2)
- Hook POS per applicare sconti in tempo reale.
- Webhook di ritorno verso middleware per aggiornare ledger punti.
