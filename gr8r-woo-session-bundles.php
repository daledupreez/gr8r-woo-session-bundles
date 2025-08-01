<?php
/**
 * Plugin Name: GR8R Session Bundles for WooCommerce
 * Plugin URI: https://github.com/daledupreez/gr8r-woo-session-bundles
 * Description: Adds session bundling to WooCommerce.
 * Version: 0.1.1
 * Author: GR8R Than Fitness
 * Author URI: https://gr8r.fit
 * Text Domain: gr8r-woo-session-bundles
 * Domain Path: /languages
 * Requires at least: 6.6
 * Tested up to: 6.8.2
 * WC requires at least: 9.8
 * WC tested up to: 10.0
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
	 * @var GR8R_Woo_Session_Bundles_Admin
	 * @since 1.0.0
	 */
	private $admin;

	/**
	 * Frontend instance.
	 *
	 * @var GR8R_Woo_Session_Bundles_Frontend
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
	public function __clone() {
		// Prevent cloning.
	}

	/**
	 * Prevent unserializing of the instance
	 *
	 * @since 1.0.0
	 */
	public function __wakeup() {
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

		// TODO: Check for other dependencies and hooks, e.g. Dokan, WooCommerce Bookings

		// Include required files.
		require_once GR8R_WOO_SESSION_BUNDLE_PLUGIN_PATH . 'includes/gr8r-woo-session-bundles-product-utils.php';
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
			$this->admin = new GR8R_Woo_Session_Bundles_Admin();
		}
		$this->frontend = new GR8R_Woo_Session_Bundles_Frontend();
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
			return 'GR8R_Woo_Session_Bundles_Product';
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
	 * @return GR8R_Woo_Session_Bundles_Admin|null
	 * @since 1.0.0
	 */
	public function get_admin() {
		return $this->admin;
	}

	/**
	 * Get frontend instance
	 *
	 * @return GR8R_Woo_Session_Bundles_Frontend
	 * @since 1.0.0
	 */
	public function get_frontend() {
		return $this->frontend;
	}

	/**
	 * Plugin activation function
	 *
	 * @since 1.0.0
	 */
	public static function activate() {
		// Check requirements.
		if ( version_compare( PHP_VERSION, '7.4.0', '<' ) ) {
			deactivate_plugins( plugin_basename( __FILE__ ) );
			wp_die( 'Gr8r Session Bundles for WooCommerce requires PHP 7.4 or higher.' );
		}

		if ( ! class_exists( 'WooCommerce' ) ) {
			deactivate_plugins( plugin_basename( __FILE__ ) );
			wp_die( 'Gr8r Session Bundles for WooCommerce requires WooCommerce to be installed and activated.' );
		}

		// Create version options.
		update_option( 'wc_session_bundle_version', GR8R_WOO_SESSION_BUNDLE_VERSION );

		// Flush rewrite rules.
		flush_rewrite_rules();
	}

	/**
	 * Plugin deactivation function
	 *
	 * @since 1.0.0
	 */
	public static function deactivate() {
		// Clean up if necessary.
		flush_rewrite_rules();
	}

	/**
	 * Plugin uninstall function
	 *
	 * @since 1.0.0
	 */
	public static function uninstall() {
		// Remove plugin data.
		delete_option( 'wc_session_bundle_version' );

		// Remove any custom post meta.
		global $wpdb;
		$wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '_bundled_products'" );
		$wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '_bundle_price'" );
	}
}

// Initialize the plugin.
GR8R_Woo_Session_Bundles::instance();

// Activation hook.
register_activation_hook( __FILE__, array( GR8R_Woo_Session_Bundles::class, 'activate' ) );

// Deactivation hook.
register_deactivation_hook( __FILE__, array( GR8R_Woo_Session_Bundles::class, 'deactivate' ) );

// Uninstall hook.
register_uninstall_hook( __FILE__, array( GR8R_Woo_Session_Bundles::class, 'uninstall' ) );

// Declare compatibility with WooCommerce HPOS.
add_action( 'before_woocommerce_init', function() {
	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
	}
} );
