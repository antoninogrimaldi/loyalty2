<?php
class CustomerProfile {
    public int $user_id;
    public string $first_name;
    public string $last_name;
    public string $tax_code;
    public ?string $shopify_customer_id;
    public ?string $odoo_partner_id;
}
