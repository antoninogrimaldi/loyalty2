from odoo import models

class SaleOrder(models.Model):
    _inherit = 'sale.order'

    def apply_loyalty_discount(self):
        for order in self:
            partner = order.partner_id
            discount = partner.x_offer_discount_pct or 0.0
            eans = [partner.x_offer_prod1_ean, partner.x_offer_prod2_ean]
            if discount and any(eans):
                for line in order.order_line:
                    if line.product_id.barcode and line.product_id.barcode in eans:
                        line.discount = discount
        return True
