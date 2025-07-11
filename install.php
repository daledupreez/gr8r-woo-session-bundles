<?php
/**
 * WooCommerce Session Bundle Installation Script
 * 
 * This script helps with plugin installation and testing.
 * Run this script to check if your WordPress environment is ready for the plugin.
 *
 * @package Gr8r_Woo_Session_Bundles
 * @since 1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) && ! defined( 'WP_CLI' ) ) {
	// If not in WordPress context, simulate basic checks.
	echo "WooCommerce Session Bundle - Environment Check\n";
	echo "==============================================\n\n";

	// Check PHP version.
	if ( version_compare( PHP_VERSION, '7.4.0', '>=' ) ) {
		echo "✓ PHP Version: " . PHP_VERSION . " (OK)\n";
	} else {
		echo "✗ PHP Version: " . PHP_VERSION . " (Requires 7.4+)\n";
	}

	// Check if required extensions are available.
	$required_extensions = array( 'json', 'mbstring', 'curl' );
	foreach ( $required_extensions as $ext ) {
		if ( extension_loaded( $ext ) ) {
			echo "✓ Extension: $ext (OK)\n";
		} else {
			echo "✗ Extension: $ext (Missing)\n";
		}
	}

	echo "\nInstallation Instructions:\n";
	echo "1. Upload the plugin folder to /wp-content/plugins/\n";
	echo "2. Activate the plugin in WordPress admin\n";
	echo "3. Ensure WooCommerce is installed and activated\n";
	echo "4. Create a new product and select 'Session Bundle' type\n";

	exit;
}

// WordPress context checks.
if ( defined( 'ABSPATH' ) ) {
	// Check if WooCommerce is active.
	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action(
			'admin_notices',
			function() {
				echo '<div class="error"><p>';
				echo 'WooCommerce Session Bundle requires WooCommerce to be installed and activated.';
				echo '</p></div>';
			}
		);
	}

	// Check WordPress version.
	if ( version_compare( get_bloginfo( 'version' ), '5.0', '<' ) ) {
		add_action(
			'admin_notices',
			function() {
				echo '<div class="error"><p>';
				echo 'WooCommerce Session Bundle requires WordPress 5.0 or higher.';
				echo '</p></div>';
			}
		);
	}

	// Check PHP version.
	if ( version_compare( PHP_VERSION, '7.4.0', '<' ) ) {
		add_action(
			'admin_notices',
			function() {
				echo '<div class="error"><p>';
				echo 'WooCommerce Session Bundle requires PHP 7.4 or higher.';
				echo '</p></div>';
			}
		);
	}
}

// Activation hook.
register_activation_hook( __FILE__, 'wc_session_bundle_activate' );

/**
 * Plugin activation function
 *
 * @since 1.0.0
 */
function wc_session_bundle_activate() {
	// Check requirements.
	if ( version_compare( PHP_VERSION, '7.4.0', '<' ) ) {
		deactivate_plugins( plugin_basename( __FILE__ ) );
		wp_die( 'WooCommerce Session Bundle requires PHP 7.4 or higher.' );
	}

	if ( version_compare( get_bloginfo( 'version' ), '5.0', '<' ) ) {
		deactivate_plugins( plugin_basename( __FILE__ ) );
		wp_die( 'WooCommerce Session Bundle requires WordPress 5.0 or higher.' );
	}

	if ( ! class_exists( 'WooCommerce' ) ) {
		deactivate_plugins( plugin_basename( __FILE__ ) );
		wp_die( 'WooCommerce Session Bundle requires WooCommerce to be installed and activated.' );
	}

	// Create necessary database tables or options.
	add_option( 'wc_session_bundle_version', '1.0.0' );

	// Flush rewrite rules.
	flush_rewrite_rules();
}

// Deactivation hook.
register_deactivation_hook( __FILE__, 'wc_session_bundle_deactivate' );

/**
 * Plugin deactivation function
 *
 * @since 1.0.0
 */
function wc_session_bundle_deactivate() {
	// Clean up if necessary.
	flush_rewrite_rules();
}

// Uninstall hook.
register_uninstall_hook( __FILE__, 'wc_session_bundle_uninstall' );

/**
 * Plugin uninstall function
 *
 * @since 1.0.0
 */
function wc_session_bundle_uninstall() {
	// Remove plugin data.
	delete_option( 'wc_session_bundle_version' );

	// Remove any custom post meta.
	global $wpdb;
	$wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '_bundled_products'" );
	$wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '_bundle_price'" );
} 