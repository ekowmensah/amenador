<?php

namespace PerryCustomerPOS;

defined('ABSPATH') || exit;

function cart_request(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        wp_send_json_error(['message' => 'Please use a POST request.'], 405);
    }
    if (! check_ajax_referer('perry_customer_cart', 'nonce', false)) {
        wp_send_json_error(['message' => 'This page has expired. Refresh it and try again.'], 403);
    }
    if (! WC()->cart) {
        wc_load_cart();
    }
    $operation = sanitize_key(wp_unslash($_POST['operation'] ?? 'get'));
    try {
        if ($operation === 'add') {
            $id = absint($_POST['product_id'] ?? 0);
            $product = wc_get_product($id);
            if (! $product || $product->get_status() !== 'publish' || ! $product->is_visible()
                || ! $product->is_type('simple') || ! $product->is_purchasable() || ! $product->is_in_stock()) {
                throw new \RuntimeException('This item is not available. Please refresh the menu.');
            }
            $valid = apply_filters('woocommerce_add_to_cart_validation', true, $id, 1);
            if (! $valid || ! WC()->cart->add_to_cart($id, 1)) {
                throw new \RuntimeException('This item could not be added. Please check its stock or options.');
            }
            do_action('woocommerce_ajax_added_to_cart', $id);
        } elseif (in_array($operation, ['quantity', 'remove'], true)) {
            $key = sanitize_text_field(wp_unslash($_POST['key'] ?? ''));
            $item = WC()->cart->get_cart_item($key);
            if (! $item) {
                throw new \RuntimeException('This item is no longer in your order. Refresh the page.');
            }
            if ($operation === 'remove') {
                WC()->cart->remove_cart_item($key);
            } else {
                $raw = wp_unslash($_POST['quantity'] ?? '');
                if (! is_scalar($raw) || ! preg_match('/^\d+$/', (string) $raw)) {
                    throw new \RuntimeException('Please choose a valid quantity.');
                }
                $quantity = (int) $raw;
                $product = $item['data'];
                $max = $product->get_max_purchase_quantity();
                if ($quantity > 0 && (! $product->is_purchasable() || ! $product->is_in_stock()
                    || $quantity < $product->get_min_purchase_quantity()
                    || ($max > 0 && $quantity > $max)
                    || ($product->is_sold_individually() && $quantity > 1)
                    || ! $product->has_enough_stock($quantity))) {
                    throw new \RuntimeException('The requested quantity is not available.');
                }
                if (! apply_filters('woocommerce_update_cart_validation', true, $key, $item, $quantity)) {
                    throw new \RuntimeException('This quantity could not be updated.');
                }
                WC()->cart->set_quantity($key, $quantity);
            }
        } elseif ($operation !== 'get') {
            throw new \RuntimeException('Unknown cart action.');
        }
        WC()->cart->calculate_totals();
        wc_clear_notices();
        wp_send_json_success(cart_state());
    } catch (\Throwable $error) {
        $notices = wc_get_notices('error');
        $message = ! empty($notices[0]['notice']) ? $notices[0]['notice'] : $error->getMessage();
        wc_clear_notices();
        wp_send_json_error(['message' => html_entity_decode(wp_strip_all_tags($message), ENT_QUOTES, get_bloginfo('charset'))], 400);
    }
}

function cart_state(): array
{
    $items = [];
    foreach (WC()->cart->get_cart() as $key => $item) {
        $product = $item['data'];
        if (! $product || ! $product->exists()) {
            continue;
        }
        $items[] = [
            'key' => $key,
            'id' => $product->get_id(),
            'name' => html_entity_decode(wp_strip_all_tags($product->get_name()), ENT_QUOTES, get_bloginfo('charset')),
            'quantity' => $item['quantity'],
            'max' => $product->is_sold_individually() ? 1 : $product->get_max_purchase_quantity(),
            'image' => wp_get_attachment_image_url($product->get_image_id(), 'woocommerce_thumbnail') ?: wc_placeholder_img_src(),
            'price' => wp_kses_post(WC()->cart->get_product_subtotal($product, $item['quantity'])),
        ];
    }
    return [
        'items' => $items,
        'count' => WC()->cart->get_cart_contents_count(),
        'subtotal' => wp_kses_post(WC()->cart->get_cart_subtotal()),
        'discount' => (float) WC()->cart->get_discount_total() > 0 ? wp_kses_post(wc_price(WC()->cart->get_discount_total())) : '',
        'total' => wp_kses_post(WC()->cart->get_total()),
        'checkout' => wc_get_checkout_url(),
    ];
}
