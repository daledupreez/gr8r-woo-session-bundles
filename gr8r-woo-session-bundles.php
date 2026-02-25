<?php
/**
 * Plugin Name: GR8R Session Bundles for WooCommerce
 * Plugin URI: https://github.com/daledupreez/gr8r-woo-session-bundles
 * Description: Adds session bundling to WooCommerce.
 * Version: 0.2.1
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
define( 'GR8R_WOO_SESSION_BUNDLE_VERSION', '0.2.0' );
define( 'GR8R_WOO_SESSION_BUNDLE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'GR8R_WOO_SESSION_BUNDLE_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );

/**
 * Main Gr8r Session Bundles for WooCommerce Class
 */
class GR8R_Woo_Session_Bundles {

	/**
	 * Plugin instance.
	 *
	 * @var GR8R_Woo_Session_Bundles
	 */
	private static $instance = null;

	/**
	 * Admin instance.
	 *
	 * @var GR8R_Woo_Session_Bundles_Admin
	 */
	private $admin;

	/**
	 * Frontend instance.
	 *
	 * @var GR8R_Woo_Session_Bundles_Frontend
	 */
	private $frontend;

	/**
	 * Private constructor to prevent direct instantiation
	 */
	private function __construct() {
		add_action( 'init', array( $this, 'init' ) );
	}

	/**
	 * Get plugin instance
	 *
	 * @return GR8R_Woo_Session_Bundles
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Prevent cloning of the instance
	 */
	public function __clone() {
		// Prevent cloning.
	}

	/**
	 * Prevent unserializing of the instance
	 */
	public function __wakeup() {
		// Prevent unserializing.
	}

	/**
	 * Initialize the plugin
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
		require_once GR8R_WOO_SESSION_BUNDLE_PLUGIN_PATH . 'includes/class-gr8r-woo-session-bundles-logger.php';
		require_once GR8R_WOO_SESSION_BUNDLE_PLUGIN_PATH . 'includes/gr8r-woo-session-bundles-product-utils.php';
		require_once GR8R_WOO_SESSION_BUNDLE_PLUGIN_PATH . 'includes/gr8r-woo-session-bundles-order-utils.php';
		require_once GR8R_WOO_SESSION_BUNDLE_PLUGIN_PATH . 'includes/class-gr8r-woo-session-bundles-configuration.php';
		require_once GR8R_WOO_SESSION_BUNDLE_PLUGIN_PATH . 'includes/class-gr8r-woo-session-bundles-admin.php';
		require_once GR8R_WOO_SESSION_BUNDLE_PLUGIN_PATH . 'includes/class-gr8r-woo-session-bundles-frontend.php';
		require_once GR8R_WOO_SESSION_BUNDLE_PLUGIN_PATH . 'includes/class-gr8r-woo-session-bundles-coupon-utils.php';

		// Initialize hooks.
		$this->init_hooks();
	}

	/**
	 * Initialize hooks
	 */
	private function init_hooks() {
		// Initialize admin and frontend.
		if ( is_admin() || $this->is_dokan_product_edit_page() ) {
			$this->admin = new GR8R_Woo_Session_Bundles_Admin();
		}

		$this->frontend = new GR8R_Woo_Session_Bundles_Frontend();
	}

	/**
	 * WooCommerce missing notice
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
	 */
	public function get_admin() {
		return $this->admin;
	}

	/**
	 * Get frontend instance
	 *
	 * @return GR8R_Woo_Session_Bundles_Frontend
	 */
	public function get_frontend() {
		return $this->frontend;
	}

	/**
	 * Check if the current page is the Dokan product edit page.
	 *
	 * @return bool True if we are on a Dokan product edit page, false otherwise.
	 */
	public function is_dokan_product_edit_page() {
		$context = [
			'request_uri' => $_SERVER['REQUEST_URI'] ?? '(unknown)',
			'is_admin'    => is_admin(),
		];

		$request_url = $_SERVER['REQUEST_URI'] ?? '';
		if ( ! str_starts_with( $request_url, '/dashboard/products/' ) ) {
			return false;
		}
		if ( 'edit' !== ( $_REQUEST['action'] ?? '' ) || ! ctype_digit( $_REQUEST['product_id'] ) ) {
			return false;
		}

		if ( ! function_exists( 'dokan_is_seller_enabled' ) || ! dokan_is_seller_enabled( get_current_user_id() ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Plugin activation function
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
	 */
	public static function deactivate() {
		// Clean up if necessary.
		flush_rewrite_rules();
	}

	/**
	 * Plugin uninstall function
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
