<?php
/**
 * Session Bundle Frontend Class
 *
 * @package Gr8r Session Bundles for WooCommerce
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Session Bundle Frontend Class
 */
class GR8R_Woo_Session_Bundle_Frontend {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('woocommerce_single_product_summary', array($this, 'display_bundle_description'), 25);
        add_action('woocommerce_after_shop_loop_item_title', array($this, 'display_bundle_description_loop'), 15);
        add_filter('woocommerce_get_price_html', array($this, 'modify_price_display'), 10, 2);
        add_action('woocommerce_before_add_to_cart_button', array($this, 'display_bundle_contents'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
    }
    
    /**
     * Display bundle description on single product page
     */
    public function display_bundle_description() {
        global $product;
        
        if (!$product || $product->get_type() !== 'session_bundle') {
            return;
        }
        
        $bundle_description = $product->get_bundle_description();
        
        if (!empty($bundle_description)) {
            echo '<div class="session-bundle-description">';
            echo $bundle_description;
            echo '</div>';
        }
    }
    
    /**
     * Display bundle description in product loop
     */
    public function display_bundle_description_loop() {
        global $product;
        
        if (!$product || $product->get_type() !== 'session_bundle') {
            return;
        }
        
        $bundled_products = $product->get_bundled_products();
        
        if (!empty($bundled_products)) {
            $product_count = count($bundled_products);
            echo '<div class="session-bundle-loop-info">';
            printf(
                '<span class="bundle-product-count">%s</span>',
                sprintf(
                    _n('%d product', '%d products', $product_count, 'gr8r-woo-session-bundles'),
                    $product_count
                )
            );
            echo '</div>';
        }
    }
    
    /**
     * Modify price display for Session Bundle products
     */
    public function modify_price_display($price, $product) {
        if ($product && $product->get_type() === 'session_bundle') {
            $custom_price = get_post_meta($product->get_id(), '_bundle_price', true);
            
            if (!empty($custom_price)) {
                return wc_price($custom_price);
            } else {
                $bundle_price = $product->get_bundle_price();
                if ($bundle_price > 0) {
                    return wc_price($bundle_price);
                }
            }
        }
        
        return $price;
    }
    
    /**
     * Display bundle contents before add to cart button
     */
    public function display_bundle_contents() {
        global $product;
        
        if (!$product || $product->get_type() !== 'session_bundle') {
            return;
        }
        
        $bundled_products = $product->get_bundled_products();
        
        if (!empty($bundled_products)) {
            echo '<div class="session-bundle-contents-summary">';
            echo '<h4>' . __('This bundle includes:', 'gr8r-woo-session-bundles') . '</h4>';
            echo '<ul class="bundle-contents-list">';
            
            foreach ($bundled_products as $product_id => $quantity) {
                $bundled_product = wc_get_product($product_id);
                if ($bundled_product) {
                    echo '<li>';
                    echo '<span class="bundle-product-name">' . esc_html($bundled_product->get_name()) . '</span>';
                    echo '<span class="bundle-product-quantity">× ' . esc_html($quantity) . '</span>';
                    echo '</li>';
                }
            }
            
            echo '</ul>';
            echo '</div>';
        }
    }
    
    /**
     * Enqueue frontend scripts
     */
    public function enqueue_frontend_scripts() {
        if (is_product() || is_shop() || is_product_category()) {
                    wp_enqueue_style(
            'gr8r-woo-session-bundles-frontend',
            GR8R_WOO_SESSION_BUNDLE_PLUGIN_URL . 'assets/css/frontend.css',
            array(),
            GR8R_WOO_SESSION_BUNDLE_VERSION
        );
        
        wp_enqueue_script(
            'gr8r-woo-session-bundles-frontend',
            GR8R_WOO_SESSION_BUNDLE_PLUGIN_URL . 'assets/js/frontend.js',
            array('jquery'),
            GR8R_WOO_SESSION_BUNDLE_VERSION,
            true
        );
        }
    }
    
    /**
     * Get bundle summary for cart/checkout
     */
    public static function get_bundle_summary($product_id) {
        $product = wc_get_product($product_id);
        
        if (!$product || $product->get_type() !== 'session_bundle') {
            return '';
        }
        
        $bundled_products = $product->get_bundled_products();
        $summary = '';
        
        if (!empty($bundled_products)) {
            $summary .= '<div class="session-bundle-summary">';
            $summary .= '<strong>' . __('Bundle contents:', 'gr8r-woo-session-bundles') . '</strong><br>';
            
            foreach ($bundled_products as $bundle_product_id => $quantity) {
                $bundle_product = wc_get_product($bundle_product_id);
                if ($bundle_product) {
                    $summary .= sprintf(
                        '• %s × %s<br>',
                        esc_html($bundle_product->get_name()),
                        esc_html($quantity)
                    );
                }
            }
            
            $summary .= '</div>';
        }
        
        return $summary;
    }
} 