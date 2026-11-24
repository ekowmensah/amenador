<?php
namespace PerryCustomerPOS;
defined('ABSPATH') || exit;
?>
<div class="pcp" id="pcp-app">
    <a class="pcp-skip" href="#pcp-menu">Skip to the menu</a>
    <header class="pcp-header">
        <a class="pcp-brand" href="<?php echo esc_url(get_permalink((int) get_option('perry_customer_pos_page_id')) ?: home_url('/')); ?>" aria-label="<?php echo esc_attr($settings['name']); ?> home">
            <span class="pcp-brand-mark"><?php echo icon('wine'); ?></span>
            <span><strong><?php echo esc_html($settings['name']); ?></strong><small>THE GOOD TIMES START HERE</small></span>
        </a>
        <nav class="pcp-nav" aria-label="Store navigation"><a class="pcp-nav-active" href="#pcp-menu">Our menu</a><a href="<?php echo esc_url(wc_get_page_permalink('myaccount')); ?>">My account</a></nav>
        <button class="pcp-header-cart" data-open-cart aria-controls="pcp-order" aria-expanded="false"><?php echo icon('bag'); ?><span>My order</span><b data-cart-count>0</b></button>
    </header>
    <main class="pcp-layout">
        <section class="pcp-catalog" aria-label="Product menu">
            <div class="pcp-intro"><div><span class="pcp-eyebrow">A LITTLE SOMETHING FOR EVERY MOOD</span><h1>Welcome to the good side.</h1><p><?php echo esc_html($settings['tagline']); ?></p></div><span class="pcp-intro-note"><?php echo icon('wine'); ?> Made for good company</span></div>
            <section class="pcp-hero" aria-labelledby="pcp-hero-title">
                <div class="pcp-hero-copy"><span class="pcp-hero-label"><span></span> THE MENU IS YOURS</span><h2 id="pcp-hero-title"><?php echo esc_html($settings['headline']); ?></h2><p>Find your favourites, build your order,<br>and let the good times begin.</p><a class="pcp-hero-button" href="#pcp-menu">Explore the menu <?php echo icon('grid'); ?></a></div>
                <div class="pcp-hero-art" aria-hidden="true"><div class="pcp-orbit pcp-orbit-one"></div><div class="pcp-orbit pcp-orbit-two"></div><span class="pcp-hero-spark">✦</span><?php if ($hero_image) : ?><img src="<?php echo esc_url($hero_image); ?>" alt="" fetchpriority="high"><?php else : ?><div class="pcp-hero-glass"><?php echo icon('wine'); ?></div><?php endif; ?><span class="pcp-hero-stamp">A toast to<br><strong>good taste.</strong></span></div>
            </section>
            <div class="pcp-benefits"><span><?php echo icon('check'); ?> Your favourites, in one place</span><span><?php echo icon('bag'); ?> Order at your own pace</span><span><?php echo icon('shield'); ?> Easy checkout</span></div>
            <section class="pcp-menu" id="pcp-menu" aria-labelledby="pcp-menu-title">
                <div class="pcp-section-heading"><div><span class="pcp-eyebrow">FIND YOUR NEXT FAVOURITE</span><h2 id="pcp-menu-title">What are you in the mood for?</h2></div><span class="pcp-product-count" data-result-count><?php echo count($products); ?> items</span></div>
                <div class="pcp-categories" role="group" aria-label="Filter by category"><button class="is-active" data-category="all" aria-pressed="true"><?php echo icon('grid'); ?> All items</button><?php foreach ($categories as $category) : ?><button data-category="<?php echo esc_attr($category->term_id); ?>" aria-pressed="false"><?php echo icon('wine'); ?> <?php echo esc_html(ucfirst($category->name)); ?></button><?php endforeach; ?></div>
                <div class="pcp-toolbar"><label class="pcp-search"><?php echo icon('search'); ?><input type="search" id="pcp-search" placeholder="Search for your favourite drink…" aria-label="Search products" autocomplete="off"><kbd>/</kbd></label><label class="pcp-sort"><span class="pcp-sr-only">Sort products</span><select id="pcp-sort"><option value="name">Name: A–Z</option><option value="price-low">Price: low to high</option><option value="price-high">Price: high to low</option></select></label></div>
                <div class="pcp-products" id="pcp-products">
                <?php foreach ($products as $index => $product) :
                    $terms = wp_get_post_terms($product->get_id(), 'product_cat');
                    $terms = is_wp_error($terms) ? [] : $terms;
                    $ids = array_map(function ($term) { return $term->term_id; }, $terms);
                    foreach ($terms as $term) { $ids = array_merge($ids, get_ancestors($term->term_id, 'product_cat')); }
                    $available = $product->is_purchasable() && $product->is_in_stock();
                    $simple = $product->is_type('simple');
                    $stock = $product->get_stock_quantity();
                    $name = $product->get_name();
                    $label = function_exists('mb_convert_case') && $name === mb_strtoupper($name) ? mb_convert_case($name, MB_CASE_TITLE, 'UTF-8') : $name;
                ?>
                    <article class="pcp-product" data-id="<?php echo esc_attr($product->get_id()); ?>" data-name="<?php echo esc_attr($name); ?>" data-price="<?php echo esc_attr(wc_get_price_to_display($product)); ?>" data-categories="<?php echo esc_attr(implode(' ', array_unique($ids))); ?>">
                        <div class="pcp-product-image pcp-tone-<?php echo $index % 4; ?>"><a href="<?php echo esc_url($product->get_permalink()); ?>" tabindex="-1" aria-hidden="true"><?php echo $product->get_image('woocommerce_thumbnail', ['loading' => 'lazy', 'alt' => $label]); ?></a><?php if (! $product->is_in_stock()) : ?><span class="pcp-product-badge pcp-sold-out">Sold out</span><?php elseif ($product->is_on_sale()) : ?><span class="pcp-product-badge">On sale</span><?php elseif ($stock !== null && $stock > 0 && $stock <= 5) : ?><span class="pcp-product-badge pcp-low-stock">Only <?php echo esc_html($stock); ?> left</span><?php endif; ?><span class="pcp-in-cart" data-in-cart="<?php echo esc_attr($product->get_id()); ?>" hidden></span></div>
                        <div class="pcp-product-info"><span class="pcp-product-category"><?php echo esc_html($terms ? ucfirst($terms[0]->name) : 'The collection'); ?></span><h3><a href="<?php echo esc_url($product->get_permalink()); ?>"><?php echo esc_html($label); ?></a></h3><div class="pcp-product-bottom"><span class="pcp-price"><?php echo wp_kses_post($product->get_price_html()); ?></span><?php if ($available && $simple) : ?><button class="pcp-add" data-add="<?php echo esc_attr($product->get_id()); ?>" aria-label="Add <?php echo esc_attr($label); ?> to order"><?php echo icon('plus'); ?></button><?php elseif ($available) : ?><a class="pcp-options" href="<?php echo esc_url($product->get_permalink()); ?>">Choose options</a><?php else : ?><button class="pcp-add" disabled aria-label="<?php echo esc_attr($label); ?> is unavailable"><?php echo icon('plus'); ?></button><?php endif; ?></div></div>
                    </article>
                <?php endforeach; ?>
                </div>
                <div class="pcp-no-results" data-no-results <?php echo $products ? 'hidden' : ''; ?>><?php echo icon('search'); ?><h3>No items found</h3><p>Try another search or browse a different category.</p><button class="pcp-text-button" data-reset-filters>Show all items</button></div>
            </section>
            <footer class="pcp-footer"><span><?php echo esc_html($settings['name']); ?> <span>·</span> Good taste. Great company.</span><a href="<?php echo esc_url(wc_get_page_permalink('myaccount')); ?>">Your account <?php echo icon('user'); ?></a></footer>
        </section>
        <div class="pcp-cart-overlay" data-close-cart hidden></div>
        <aside class="pcp-order" id="pcp-order" aria-labelledby="pcp-order-title" tabindex="-1">
            <div class="pcp-order-top"><span class="pcp-order-icon"><?php echo icon('bag'); ?></span><div><h2 id="pcp-order-title">Your order <span data-cart-count>0</span></h2><p>A good time starts with a great pick.</p></div><button class="pcp-close-cart" data-close-cart aria-label="Close order"><?php echo icon('close'); ?></button></div>
            <div class="pcp-order-contents" data-cart-items><div class="pcp-cart-empty"><div class="pcp-empty-art"><?php echo icon('bag'); ?><span>+</span></div><h3>Your next favourite<br>is waiting.</h3><p>Add something from the menu<br>to get your order started.</p><button class="pcp-empty-button" data-browse-menu>Let’s find you a drink <?php echo icon('wine'); ?></button></div></div>
            <div class="pcp-order-bottom"><div class="pcp-totals-row"><span>Subtotal</span><strong data-subtotal><?php echo wp_kses_post(wc_price(0)); ?></strong></div><div class="pcp-totals-row pcp-discount" data-discount-row hidden><span>Discount</span><strong data-discount></strong></div><p class="pcp-checkout-note">Final taxes, delivery and payment options are shown at checkout.</p><div class="pcp-totals-row pcp-total"><span>Estimated total</span><strong data-total><?php echo wp_kses_post(wc_price(0)); ?></strong></div><a class="pcp-checkout is-disabled" data-checkout href="<?php echo esc_url(wc_get_checkout_url()); ?>" aria-disabled="true"><span>Continue to checkout</span><?php echo icon('bag'); ?></a><span class="pcp-safe-note"><?php echo icon('shield'); ?> Checkout with confidence</span></div>
            <div class="pcp-order-footnote"><?php echo icon('wine'); ?><span>Something for you.<br><strong>Something for the good times.</strong></span></div>
        </aside>
    </main>
    <button class="pcp-mobile-cart" data-open-cart aria-controls="pcp-order" aria-expanded="false"><?php echo icon('bag'); ?><span>View your order <b data-cart-count>0</b></span><strong data-mobile-total><?php echo wp_kses_post(wc_price(0)); ?></strong></button>
    <div class="pcp-toast" role="status" aria-live="polite" hidden></div>
    <noscript><div class="pcp-noscript">Enable JavaScript to build your order here, or <a href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>">use the standard shop</a>.</div></noscript>
</div>
