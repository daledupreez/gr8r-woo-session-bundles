<?php
/**
 * Session Bundle Frontend Class
 *
 * @package Gr8r_Woo_Session_Bundles
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Session Bundle Frontend Class
 *
 * @since 1.0.0
 */
class GR8R_Woo_Session_Bundles_Frontend {

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'woocommerce_single_product_summary', array( $this, 'display_bundle_description' ), 25 );
		add_action( 'woocommerce_after_shop_loop_item_title', array( $this, 'display_bundle_description_loop' ), 15 );

		add_action( 'woocommerce_before_add_to_cart_button', array( $this, 'display_bundle_contents' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_scripts' ) );
	}

	/**
	 * Display bundle description on single product page
	 *
	 * @since 1.0.0
	 */
	public function display_bundle_description() {
		global $product;

		if ( ! $product || ! gr8r_session_bundles_is_bundle_product( $product->get_id() ) ) {
			return;
		}

		$bundle_description = gr8r_session_bundles_get_bundle_description( $product->get_id() );

		if ( ! empty( $bundle_description ) ) {
			echo '<div class="gr8r-session-bundle-description">';
			echo $bundle_description; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo '</div>';
		}
	}

	/**
	 * Display bundle description in product loop
	 *
	 * @since 1.0.0
	 */
	public function display_bundle_description_loop() {
		global $product;

		if ( ! $product || ! gr8r_session_bundles_is_bundle_product( $product->get_id() ) ) {
			return;
		}

		$bundled_products = gr8r_session_bundles_get_product_bundle_meta( $product->get_id() );

		if ( empty( $bundled_products ) ) {
			return;
		}

		// TODO: Check that this is displaying what we think it should.
		$product_count = count( $bundled_products );
		echo '<div class="session-bundle-loop-info">';
		printf(
			'<span class="bundle-product-count">%s</span>',
			sprintf(
				/* translators: %d: number of products */
				_n( '%d product', '%d products', $product_count, 'gr8r-woo-session-bundles' ),
				$product_count
			)
		);
		echo '</div>';
	}

	/**
	 * Display bundle contents before add to cart button
	 *
	 * @since 1.0.0
	 */
	public function display_bundle_contents() {
		global $product;

		if ( ! $product || ! gr8r_session_bundles_is_bundle_product( $product->get_id() ) ) {
			return;
		}

		$bundled_products = gr8r_session_bundles_get_product_bundle_meta( $product->get_id() );

		if ( ! empty( $bundled_products ) ) {
			echo '<div class="session-bundle-contents-summary">';
			echo '<h4>' . esc_html__( 'This bundle includes:', 'gr8r-woo-session-bundles' ) . '</h4>';
			echo '<ul class="bundle-contents-list">';

			foreach ( $bundled_products as $product_id => $quantity ) {
				$bundled_product = wc_get_product( $product_id );
				if ( $bundled_product ) {
					echo '<li>';
					echo '<span class="bundle-product-name">' . esc_html( $bundled_product->get_name() ) . '</span>';
					echo '<span class="bundle-product-quantity">× ' . esc_html( $quantity ) . '</span>';
					echo '</li>';
				}
			}

			echo '</ul>';
			echo '</div>';
		}
	}

	/**
	 * Enqueue frontend scripts
	 *
	 * @since 1.0.0
	 */
	public function enqueue_frontend_scripts() {
		if ( is_product() || is_shop() || is_product_category() ) {
			wp_enqueue_style(
				'gr8r-woo-session-bundles-frontend',
				GR8R_WOO_SESSION_BUNDLE_PLUGIN_URL . 'assets/css/frontend.css',
				array(),
				GR8R_WOO_SESSION_BUNDLE_VERSION
			);

			wp_enqueue_script(
				'gr8r-woo-session-bundles-frontend',
				GR8R_WOO_SESSION_BUNDLE_PLUGIN_URL . 'assets/js/frontend.js',
				array( 'jquery' ),
				GR8R_WOO_SESSION_BUNDLE_VERSION,
				true
			);
		}
	}

	/**
	 * Get bundle summary for cart/checkout
	 *
	 * @param int $product_id Product ID.
	 * @return string
	 * @since 1.0.0
	 */
	public static function get_bundle_summary( $product_id ) {
		$product = wc_get_product( $product_id );

		if ( ! $product || ! gr8r_session_bundles_is_bundle_product( $product->get_id() ) ) {
			return '';
		}

		$bundled_products = gr8r_session_bundles_get_product_bundle_meta( $product->get_id() );
		$summary          = '';

		if ( ! empty( $bundled_products ) ) {
			$summary .= '<div class="session-bundle-summary">';
			$summary .= '<strong>' . esc_html__( 'Bundle contents:', 'gr8r-woo-session-bundles' ) . '</strong><br>';

			foreach ( $bundled_products as $bundle_product_id => $quantity ) {
				$bundle_product = wc_get_product( $bundle_product_id );
				if ( $bundle_product ) {
					$summary .= sprintf(
						'• %s × %s<br>',
						esc_html( $bundle_product->get_name() ),
						esc_html( $quantity )
					);
				}
			}

			$summary .= '</div>';
		}

		return $summary;
	}
} 