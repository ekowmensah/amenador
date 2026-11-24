<?php
/**
 * Plugin Name: Perry Customer POS
 * Description: A modern customer ordering page powered by your WooCommerce catalog, cart and checkout.
 * Version: 1.0.0
 * Requires at least: 6.6
 * Requires PHP: 8.0
 * Requires Plugins: woocommerce
 * Author: Perry POS
 * License: GPLv2 or later
 * Text Domain: perry-customer-pos
 */

namespace PerryCustomerPOS;

defined('ABSPATH') || exit;

const VERSION = '1.0.0';
const TEMPLATE = 'perry-customer-pos';

require_once __DIR__.'/includes/cart.php';

function settings(): array
{
    return wp_parse_args(get_option('perry_customer_pos_settings', []), [
        'name' => get_bloginfo('name'),
        'tagline' => 'Drinks, good times & everything in between',
        'headline' => 'Your next round, your way.',
    ]);
}

function activate(): void
{
    if (! class_exists('WooCommerce')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(esc_html__('Please activate WooCommerce before Perry Customer POS.', 'perry-customer-pos'));
    }
    $page = get_post((int) get_option('perry_customer_pos_page_id'));
    if (! $page || $page->post_status === 'trash') {
        $id = wp_insert_post([
            'post_type' => 'page', 'post_status' => 'publish',
            'post_title' => 'Order online', 'post_name' => 'order',
            'post_content' => '[perry_customer_pos]',
            'meta_input' => ['_wp_page_template' => TEMPLATE],
        ], true);
        if (! is_wp_error($id)) {
            update_option('perry_customer_pos_page_id', $id);
        }
    }
}
register_activation_hook(__FILE__, __NAMESPACE__.'\\activate');

add_action('init', function () {
    add_shortcode('perry_customer_pos', __NAMESPACE__.'\\render');
});

function is_order_page(): bool
{
    $post = get_post();
    return is_singular() && $post && has_shortcode($post->post_content, 'perry_customer_pos');
}

add_action('template_redirect', function () {
    if (is_order_page()) {
        if (! defined('DONOTCACHEPAGE')) {
            define('DONOTCACHEPAGE', true);
        }
        nocache_headers();
    }
});

add_filter('theme_page_templates', function ($templates) {
    $templates[TEMPLATE] = __('Customer POS — full screen', 'perry-customer-pos');
    return $templates;
});

add_filter('template_include', function ($template) {
    return is_page() && get_page_template_slug() === TEMPLATE
        ? __DIR__.'/templates/page.php' : $template;
}, 99);

add_action('wp_enqueue_scripts', function () {
    if (is_order_page()) {
        enqueue();
    }
});

function enqueue(): void
{
    static $enqueued = false;
    if ($enqueued) {
        return;
    }
    $enqueued = true;
    wp_enqueue_style('perry-customer-pos', plugins_url('assets/store.css', __FILE__), [], VERSION);
    wp_enqueue_script('perry-customer-pos', plugins_url('assets/store.js', __FILE__), [], VERSION, true);
    wp_localize_script('perry-customer-pos', 'perryPOS', [
        'endpoint' => class_exists('WC_AJAX') ? \WC_AJAX::get_endpoint('perry_customer_cart') : '',
        'nonce' => wp_create_nonce('perry_customer_cart'),
        'checkout' => function_exists('wc_get_checkout_url') ? wc_get_checkout_url() : '',
    ]);
}

function icon(string $name, string $class = ''): string
{
    $paths = [
        'bag' => '<path d="M5 7h14l1 14H4L5 7Z"/><path d="M8 8V6a4 4 0 0 1 8 0v2"/>',
        'search' => '<circle cx="10.5" cy="10.5" r="6.5"/><path d="m16 16 5 5"/>',
        'plus' => '<path d="M12 5v14M5 12h14"/>',
        'minus' => '<path d="M5 12h14"/>',
        'close' => '<path d="m6 6 12 12M18 6 6 18"/>',
        'grid' => '<rect x="3" y="3" width="7" height="7" rx="2"/><rect x="14" y="3" width="7" height="7" rx="2"/><rect x="3" y="14" width="7" height="7" rx="2"/><rect x="14" y="14" width="7" height="7" rx="2"/>',
        'wine' => '<path d="M8 3h8l2 7a6 6 0 0 1-12 0l2-7ZM12 16v5M8 21h8M7 9h10"/>',
        'check' => '<path d="m5 12 4 4L19 6"/>',
        'user' => '<circle cx="12" cy="8" r="4"/><path d="M4 21v-2a8 8 0 0 1 16 0v2"/>',
        'shield' => '<path d="m12 3 8 3v6c0 5-8 9-8 9s-8-4-8-9V6l8-3Z"/><path d="m8 12 3 3 5-6"/>',
        'chevron' => '<path d="m9 5 7 7-7 7"/>',
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
    ];
    return '<svg class="pcp-icon '.esc_attr($class).'" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.($paths[$name] ?? $paths['bag']).'</svg>';
}

function render(): string
{
    if (! function_exists('wc_get_products')) {
        return '<p>'.esc_html__('The ordering page needs WooCommerce to be active.', 'perry-customer-pos').'</p>';
    }
    enqueue();
    $settings = settings();
    $products = wc_get_products([
        'status' => 'publish', 'visibility' => 'catalog', 'limit' => -1,
        'orderby' => 'title', 'order' => 'ASC',
    ]);
    $categories = get_terms(['taxonomy' => 'product_cat', 'hide_empty' => true]);
    $categories = is_wp_error($categories) ? [] : $categories;
    $hero_image = '';
    foreach ($products as $product) {
        if ($product->get_image_id()) {
            $file = get_attached_file($product->get_image_id());
            if ($file && preg_match('/^default(?:-\d+)?\./', basename($file))) {
                $hero_image = wp_get_attachment_image_url($product->get_image_id(), 'large');
                break;
            }
        }
    }
    ob_start();
    include __DIR__.'/templates/store.php';
    return ob_get_clean();
}

add_action('wc_ajax_perry_customer_cart', __NAMESPACE__.'\\cart_request');

add_action('admin_menu', function () {
    add_submenu_page('woocommerce', 'Customer POS', 'Customer POS', 'manage_woocommerce', 'perry-customer-pos', __NAMESPACE__.'\\admin_page');
});
add_filter('option_page_capability_perry_customer_pos', function () { return 'manage_woocommerce'; });
add_action('admin_init', function () {
    register_setting('perry_customer_pos', 'perry_customer_pos_settings', [
        'type' => 'array',
        'sanitize_callback' => function ($input) {
            $clean = [];
            foreach (['name', 'tagline', 'headline'] as $key) {
                $clean[$key] = sanitize_text_field($input[$key] ?? '');
            }
            return $clean;
        },
    ]);
});

function admin_page(): void
{
    if (! current_user_can('manage_woocommerce')) {
        return;
    }
    $settings = settings();
    $url = get_permalink((int) get_option('perry_customer_pos_page_id'));
    ?>
    <div class="wrap">
        <h1>Customer POS</h1>
        <p>A customer ordering experience using your live WooCommerce products and checkout.</p>
        <?php if ($url) : ?><p><a class="button button-primary" href="<?php echo esc_url($url); ?>" target="_blank" rel="noopener">Open ordering page</a></p><?php endif; ?>
        <form method="post" action="options.php">
            <?php settings_fields('perry_customer_pos'); ?>
            <table class="form-table" role="presentation">
            <?php foreach (['name' => 'Store name', 'tagline' => 'Store tagline', 'headline' => 'Welcome headline'] as $key => $label) : ?>
                <tr><th><label for="pcp-<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></label></th>
                    <td><input class="regular-text" id="pcp-<?php echo esc_attr($key); ?>" name="perry_customer_pos_settings[<?php echo esc_attr($key); ?>]" value="<?php echo esc_attr($settings[$key]); ?>"></td></tr>
            <?php endforeach; ?>
            </table>
            <?php submit_button(); ?>
        </form>
        <h2>Use on another page</h2>
        <p>Add the shortcode <code>[perry_customer_pos]</code> to a page. For the dedicated layout, select the <strong>Customer POS — full screen</strong> page template.</p>
        <p>To make it your homepage, choose the ordering page under <a href="<?php echo esc_url(admin_url('options-reading.php')); ?>">Settings → Reading</a>.</p>
        <p>Products, images, categories, stock and prices are managed in WooCommerce. Payment methods are managed in WooCommerce → Settings → Payments. Orders use the normal WooCommerce checkout and your existing POS order sync.</p>
    </div>
    <?php
}

add_filter('plugin_action_links_'.plugin_basename(__FILE__), function ($links) {
    array_unshift($links, '<a href="'.esc_url(admin_url('admin.php?page=perry-customer-pos')).'">Settings</a>');
    return $links;
});
