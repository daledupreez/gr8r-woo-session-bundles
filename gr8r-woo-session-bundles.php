<?php
/**
 * Plugin Name: Gr8r Session Bundles for WooCommerce
 * Plugin URI: https://example.com/gr8r-woo-session-bundles
 * Description: Adds a new Session Bundle product type to WooCommerce that allows bundling multiple products with custom quantities.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * Text Domain: gr8r-woo-session-bundles
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * WC requires at least: 5.0
 * WC tested up to: 8.0
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants.
define( 'GR8R_WOO_SESSION_BUNDLE_VERSION', '1.0.0' );
define( 'GR8R_WOO_SESSION_BUNDLE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'GR8R_WOO_SESSION_BUNDLE_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );

/**
 * Main Gr8r Session Bundles for WooCommerce Class
 *
 * @since 1.0.0
 */
class GR8R_Woo_Session_Bundles {

	/**
	 * Plugin instance.
	 *
	 * @var GR8R_Woo_Session_Bundles
	 * @since 1.0.0
	 */
	private static $instance = null;

	/**
	 * Admin instance.
	 *
	 * @var GR8R_Woo_Session_Bundle_Admin
	 * @since 1.0.0
	 */
	private $admin;

	/**
	 * Frontend instance.
	 *
	 * @var GR8R_Woo_Session_Bundle_Frontend
	 * @since 1.0.0
	 */
	private $frontend;

	/**
	 * Private constructor to prevent direct instantiation
	 *
	 * @since 1.0.0
	 */
	private function __construct() {
		add_action( 'init', array( $this, 'init' ) );
	}

	/**
	 * Get plugin instance
	 *
	 * @return GR8R_Woo_Session_Bundles
	 * @since 1.0.0
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Prevent cloning of the instance
	 *
	 * @since 1.0.0
	 */
	private function __clone() {
		// Prevent cloning.
	}

	/**
	 * Prevent unserializing of the instance
	 *
	 * @since 1.0.0
	 */
	private function __wakeup() {
		// Prevent unserializing.
	}

	/**
	 * Initialize the plugin
	 *
	 * @since 1.0.0
	 */
	public function init() {
        // Load text domain.
		load_plugin_textdomain( 'gr8r-woo-session-bundles', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

		// Check if WooCommerce is active.
		if ( ! class_exists( 'WooCommerce' ) ) {
			if ( is_admin() ) {
                add_action( 'admin_notices', array( $this, 'woocommerce_missing_notice' ) );
            }
			return;
		}

		// Include required files.
		require_once GR8R_WOO_SESSION_BUNDLE_PLUGIN_PATH . 'includes/class-gr8r-woo-session-bundles-product.php';
		require_once GR8R_WOO_SESSION_BUNDLE_PLUGIN_PATH . 'includes/class-gr8r-woo-session-bundles-admin.php';
		require_once GR8R_WOO_SESSION_BUNDLE_PLUGIN_PATH . 'includes/class-gr8r-woo-session-bundles-frontend.php';

		// Initialize hooks.
		$this->init_hooks();
	}

	/**
	 * Initialize hooks
	 *
	 * @since 1.0.0
	 */
	private function init_hooks() {
		// Add product type.
		add_filter( 'product_type_selector', array( $this, 'add_session_bundle_product_type' ) );
		add_filter( 'woocommerce_product_class', array( $this, 'load_session_bundle_product_class' ), 10, 2 );

		// Initialize admin and frontend.
		if ( is_admin() ) {
			$this->admin = new GR8R_Woo_Session_Bundle_Admin();
		}
		$this->frontend = new GR8R_Woo_Session_Bundle_Frontend();
	}

	/**
	 * Add Session Bundle to product type selector
	 *
	 * @param array $types Product types.
	 * @return array
	 * @since 1.0.0
	 */
	public function add_session_bundle_product_type( $types ) {
		$types['session_bundle'] = __( 'Session Bundle', 'gr8r-woo-session-bundles' );
		return $types;
	}

	/**
	 * Load Session Bundle product class
	 *
	 * @param string $classname Product class name.
	 * @param string $product_type Product type.
	 * @return string
	 * @since 1.0.0
	 */
	public function load_session_bundle_product_class( $classname, $product_type ) {
		if ( 'session_bundle' === $product_type ) {
			return 'GR8R_Woo_Session_Bundle_Product';
		}
		return $classname;
	}

	/**
	 * WooCommerce missing notice
	 *
	 * @since 1.0.0
	 */
	public function woocommerce_missing_notice() {
		echo '<div class="error"><p>' .
			esc_html__( 'Gr8r Session Bundles for WooCommerce requires WooCommerce to be installed and active.', 'gr8r-woo-session-bundles' ) .
			'</p></div>';
	}

	/**
	 * Get admin instance
	 *
	 * @return GR8R_Woo_Session_Bundle_Admin|null
	 * @since 1.0.0
	 */
	public function get_admin() {
		return $this->admin;
	}

	/**
	 * Get frontend instance
	 *
	 * @return GR8R_Woo_Session_Bundle_Frontend
	 * @since 1.0.0
	 */
	public function get_frontend() {
		return $this->frontend;
	}
}

// Initialize the plugin.
GR8R_Woo_Session_Bundles::instance();
