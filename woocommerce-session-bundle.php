<?php
/**
 * Plugin Name: WooCommerce Session Bundle
 * Plugin URI: https://example.com/woocommerce-session-bundle
 * Description: Adds a new Session Bundle product type to WooCommerce that allows bundling multiple products with custom quantities.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * Text Domain: woocommerce-session-bundle
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * WC requires at least: 5.0
 * WC tested up to: 8.0
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('WC_SESSION_BUNDLE_VERSION', '1.0.0');
define('WC_SESSION_BUNDLE_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WC_SESSION_BUNDLE_PLUGIN_PATH', plugin_dir_path(__FILE__));

/**
 * Main WooCommerce Session Bundle Class
 */
class WC_Session_Bundle {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('plugins_loaded', array($this, 'init'));
    }
    
    /**
     * Initialize the plugin
     */
    public function init() {
        // Check if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', array($this, 'woocommerce_missing_notice'));
            return;
        }
        
        // Load text domain
        load_plugin_textdomain('woocommerce-session-bundle', false, dirname(plugin_basename(__FILE__)) . '/languages');
        
        // Include required files
        $this->includes();
        
        // Initialize hooks
        $this->init_hooks();
    }
    
    /**
     * Include required files
     */
    private function includes() {
        require_once WC_SESSION_BUNDLE_PLUGIN_PATH . 'includes/class-wc-product-session-bundle.php';
        require_once WC_SESSION_BUNDLE_PLUGIN_PATH . 'includes/class-wc-session-bundle-admin.php';
        require_once WC_SESSION_BUNDLE_PLUGIN_PATH . 'includes/class-wc-session-bundle-frontend.php';
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Add product type
        add_filter('product_type_selector', array($this, 'add_session_bundle_product_type'));
        add_filter('woocommerce_product_class', array($this, 'load_session_bundle_product_class'), 10, 2);
        
        // Initialize admin and frontend
        if (is_admin()) {
            new WC_Session_Bundle_Admin();
        }
        new WC_Session_Bundle_Frontend();
    }
    
    /**
     * Add Session Bundle to product type selector
     */
    public function add_session_bundle_product_type($types) {
        $types['session_bundle'] = __('Session Bundle', 'woocommerce-session-bundle');
        return $types;
    }
    
    /**
     * Load Session Bundle product class
     */
    public function load_session_bundle_product_class($classname, $product_type) {
        if ($product_type === 'session_bundle') {
            return 'WC_Product_Session_Bundle';
        }
        return $classname;
    }
    
    /**
     * WooCommerce missing notice
     */
    public function woocommerce_missing_notice() {
        echo '<div class="error"><p>' . 
             __('WooCommerce Session Bundle requires WooCommerce to be installed and active.', 'woocommerce-session-bundle') . 
             '</p></div>';
    }
}

// Initialize the plugin
new WC_Session_Bundle(); 