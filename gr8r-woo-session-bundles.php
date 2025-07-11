<?php
/**
 * Plugin Name: Gr8r Session Bundles for WooCommerce
 * Plugin URI: https://example.com/gr8r-woo-session-bundle
 * Description: Adds a new Session Bundle product type to WooCommerce that allows bundling multiple products with custom quantities.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * Text Domain: gr8r-woo-session-bundle
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
define('GR8R_WOO_SESSION_BUNDLE_VERSION', '1.0.0');
define('GR8R_WOO_SESSION_BUNDLE_PLUGIN_URL', plugin_dir_url(__FILE__));
define('GR8R_WOO_SESSION_BUNDLE_PLUGIN_PATH', plugin_dir_path(__FILE__));

/**
 * Main Gr8r Session Bundles for WooCommerce Class
 */
class GR8R_Woo_Session_Bundle {
    
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
        load_plugin_textdomain('gr8r-woo-session-bundle', false, dirname(plugin_basename(__FILE__)) . '/languages');
        
        // Include required files
        $this->includes();
        
        // Initialize hooks
        $this->init_hooks();
    }
    
    /**
     * Include required files
     */
    private function includes() {
        require_once GR8R_WOO_SESSION_BUNDLE_PLUGIN_PATH . 'includes/class-gr8r-woo-session-bundle-product.php';
        require_once GR8R_WOO_SESSION_BUNDLE_PLUGIN_PATH . 'includes/class-gr8r-woo-session-bundle-admin.php';
        require_once GR8R_WOO_SESSION_BUNDLE_PLUGIN_PATH . 'includes/class-gr8r-woo-session-bundle-frontend.php';
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
            new GR8R_Woo_Session_Bundle_Admin();
        }
        new GR8R_Woo_Session_Bundle_Frontend();
    }
    
    /**
     * Add Session Bundle to product type selector
     */
    public function add_session_bundle_product_type($types) {
        $types['session_bundle'] = __('Session Bundle', 'gr8r-woo-session-bundle');
        return $types;
    }
    
    /**
     * Load Session Bundle product class
     */
    public function load_session_bundle_product_class($classname, $product_type) {
        if ($product_type === 'session_bundle') {
            return 'GR8R_Woo_Session_Bundle_Product';
        }
        return $classname;
    }
    
    /**
     * WooCommerce missing notice
     */
    public function woocommerce_missing_notice() {
        echo '<div class="error"><p>' . 
             __('Gr8r Session Bundles for WooCommerce requires WooCommerce to be installed and active.', 'gr8r-woo-session-bundle') . 
             '</p></div>';
    }
}

// Initialize the plugin
new GR8R_Woo_Session_Bundle(); 