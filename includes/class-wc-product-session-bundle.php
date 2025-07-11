<?php
/**
 * Session Bundle Product Class
 *
 * @package WooCommerce Session Bundle
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Session Bundle Product Class
 */
class WC_Product_Session_Bundle extends WC_Product {
    
    /**
     * Initialize Session Bundle product
     *
     * @param mixed $product
     */
    public function __construct($product = 0) {
        $this->product_type = 'session_bundle';
        parent::__construct($product);
    }
    
    /**
     * Get bundled products
     *
     * @return array
     */
    public function get_bundled_products() {
        $bundled_products = get_post_meta($this->get_id(), '_bundled_products', true);
        return is_array($bundled_products) ? $bundled_products : array();
    }
    
    /**
     * Set bundled products
     *
     * @param array $bundled_products
     */
    public function set_bundled_products($bundled_products) {
        update_post_meta($this->get_id(), '_bundled_products', $bundled_products);
    }
    
    /**
     * Get bundled product quantity
     *
     * @param int $product_id
     * @return int
     */
    public function get_bundled_product_quantity($product_id) {
        $bundled_products = $this->get_bundled_products();
        return isset($bundled_products[$product_id]) ? intval($bundled_products[$product_id]) : 0;
    }
    
    /**
     * Set bundled product quantity
     *
     * @param int $product_id
     * @param int $quantity
     */
    public function set_bundled_product_quantity($product_id, $quantity) {
        $bundled_products = $this->get_bundled_products();
        $bundled_products[$product_id] = intval($quantity);
        $this->set_bundled_products($bundled_products);
    }
    
    /**
     * Get bundle description
     *
     * @return string
     */
    public function get_bundle_description() {
        $bundled_products = $this->get_bundled_products();
        $description = '';
        
        if (!empty($bundled_products)) {
            $description .= '<div class="session-bundle-contents">';
            $description .= '<h4>' . __('Bundle Contents:', 'woocommerce-session-bundle') . '</h4>';
            $description .= '<ul>';
            
            foreach ($bundled_products as $product_id => $quantity) {
                $product = wc_get_product($product_id);
                if ($product) {
                    $description .= sprintf(
                        '<li>%s × %s</li>',
                        esc_html($product->get_name()),
                        esc_html($quantity)
                    );
                }
            }
            
            $description .= '</ul>';
            $description .= '</div>';
        }
        
        return $description;
    }
    
    /**
     * Get bundle price
     *
     * @return float
     */
    public function get_bundle_price() {
        $bundled_products = $this->get_bundled_products();
        $total_price = 0;
        
        foreach ($bundled_products as $product_id => $quantity) {
            $product = wc_get_product($product_id);
            if ($product) {
                $total_price += $product->get_price() * $quantity;
            }
        }
        
        return $total_price;
    }
    
    /**
     * Check if product is purchasable
     *
     * @return bool
     */
    public function is_purchasable() {
        $bundled_products = $this->get_bundled_products();
        
        if (empty($bundled_products)) {
            return false;
        }
        
        // Check if all bundled products are purchasable
        foreach ($bundled_products as $product_id => $quantity) {
            $product = wc_get_product($product_id);
            if (!$product || !$product->is_purchasable()) {
                return false;
            }
        }
        
        return parent::is_purchasable();
    }
    
    /**
     * Check if product is in stock
     *
     * @return bool
     */
    public function is_in_stock() {
        $bundled_products = $this->get_bundled_products();
        
        if (empty($bundled_products)) {
            return false;
        }
        
        // Check if all bundled products are in stock
        foreach ($bundled_products as $product_id => $quantity) {
            $product = wc_get_product($product_id);
            if (!$product || !$product->is_in_stock() || $product->get_stock_quantity() < $quantity) {
                return false;
            }
        }
        
        return parent::is_in_stock();
    }
    
    /**
     * Get stock quantity
     *
     * @return int
     */
    public function get_stock_quantity() {
        $bundled_products = $this->get_bundled_products();
        
        if (empty($bundled_products)) {
            return 0;
        }
        
        $min_stock = null;
        
        foreach ($bundled_products as $product_id => $quantity) {
            $product = wc_get_product($product_id);
            if ($product && $product->managing_stock()) {
                $available_stock = intval($product->get_stock_quantity() / $quantity);
                if ($min_stock === null || $available_stock < $min_stock) {
                    $min_stock = $available_stock;
                }
            }
        }
        
        return $min_stock !== null ? $min_stock : parent::get_stock_quantity();
    }
} 