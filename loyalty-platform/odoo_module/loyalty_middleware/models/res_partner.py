from odoo import fields, models

class ResPartner(models.Model):
    _inherit = 'res.partner'

    x_offer_prod1_ean = fields.Char(string='Offerta prodotto 1 EAN')
    x_offer_prod2_ean = fields.Char(string='Offerta prodotto 2 EAN')
    x_offer_discount_pct = fields.Float(string='Sconto offerta (%)')
    x_offer_updated_at = fields.Datetime(string='Offerta aggiornata il')
