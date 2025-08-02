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

		//add_action( 'woocommerce_before_add_to_cart_button', array( $this, 'display_bundle_contents' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_scripts' ) );

		add_action( 'woocommerce_new_order_item', array( $this, 'handle_new_order_item' ), 10, 2 );

		// Order display
		add_filter( 'woocommerce_order_item_class', array( $this, 'add_bundle_order_item_class' ), 10, 2 );
		add_action( 'woocommerce_order_item_meta_end', array( $this, 'render_order_item_bundle_details' ), 10, 4 );
		add_action( 'woocommerce_after_order_itemmeta', array( $this, 'render_order_line_item_bundle' ), 10, 2 );

	}

	public function debug_filter( $result, ...$args ) {
		gr8r_debug( [
			'current_filter' => current_filter(),
			'result' => $result,
			'args' => $args,
		] );
		return $result;
	}

	/**
	 * Render the bundle details for an order line item.
	 *
	 * @param int                   $order_item_id Order item ID.
	 * @param WC_Order_Item_Product $order_item    Order item product.
	 */
	public function render_order_line_item_bundle( $order_item_id, $order_item ) {
		if ( ! gr8r_session_bundles_is_bundle_order_item( $order_item ) ) {
			return;
		}

		$bundled_products = gr8r_session_bundles_get_order_item_bundle_meta( $order_item_id );

		if ( empty( $bundled_products ) ) {
			return;
		}

		echo gr8r_session_bundles_get_bundle_description(
			$bundled_products,
			array(
				'wrapper_class'  => 'gr8r-woo-session-bundles-order-item-meta-details',
			)
		);
	}

	/**
	 * Add bundle order item class to order items that are bundles.
	 *
	 * @param string $class Order item class.
	 * @param WC_Order_Item_Product $item Order item product.
	 * @param WC_Order $order Order object.
	 * @return string
	 * @since 1.0.0
	 */
	public function add_bundle_order_item_class( $class, $item ) {
		if ( gr8r_session_bundles_is_bundle_order_item( $item ) ) {
			$class .= ' gr8r-woo-session-bundles-order-item';
		}
		return $class;
	}

	/**
	 * Render the bundle details for an order item. Intended to use a hook for wp-admin and email generation.
	 *
	 * @param int                   $order_item_id Order item ID.
	 * @param WC_Order_Item_Product $order_item    Order item product.
	 * @param WC_Order              $order         Order object.
	 * @param bool                  $is_plain_text Whether the email is plain text or not.
	 */
	public function render_order_item_bundle_details( $order_item_id, $order_item, $order, $is_plain_text ): void {
		if ( ! gr8r_session_bundles_is_bundle_order_item( $order_item ) ) {
			return;
		}

		$bundled_products = gr8r_session_bundles_get_order_item_bundle_meta( $order_item_id );

		if ( empty( $bundled_products ) ) {
			return;
		}

		if ( $is_plain_text ) {
			$this->render_order_item_bundle_details_plain_text( $bundled_products );
			return;
		}

		echo gr8r_session_bundles_get_bundle_description(
			$bundled_products,
			array(
				'wrapper_class'  => 'gr8r-woo-session-bundles-order-item-details',
			)
		);
	}

	/**
	 * Render the bundle details for an order item in plain text.
	 *
	 * @param int[] $bundled_products The bundled products.
	 */
	protected function render_order_item_bundle_details_plain_text( array $bundled_products ): void {
		echo "\n -";
		esc_html_e( 'Included sessions:', 'gr8r-woo-session-bundles' );
		echo "\n";
		foreach ( $bundled_products as $product_id => $quantity ) {
			$product = wc_get_product( $product_id );
			if ( $product ) {
				echo "\n   * " . sprintf(
					'%1$s × %2$s - %3$s %4$s',
					esc_html( $quantity ),
					esc_html( strip_tags( $product->get_name() ) ),
					esc_html__( 'each valued at', 'gr8r-woo-session-bundles' ),
					strip_tags( $product->get_price_html() )
				) . "\n";
			}
		}
	}

	/**
	 * Display bundle description on single product page.
	 *
	 * @since 1.0.0
	 */
	public function display_bundle_description() {
		global $product;

		if ( ! $product || ! gr8r_session_bundles_is_bundle_product( $product->get_id() ) ) {
			return;
		}

		$bundle_description = gr8r_session_bundles_get_product_bundle_description( $product->get_id() );

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
	 * Hook into the `woocommerce_new_order_item` action to save the bundle meta data in the newly created order item.
	 *
	 * @param int                   $order_item_id The order item ID.
	 * @param WC_Order_Item_Product $order_item    The order item product.
	 * @since 1.0.0
	 */
	public function handle_new_order_item( $order_item_id, $order_item ) {
		if ( ! $order_item || ! $order_item instanceof WC_Order_Item_Product ) {
			return;
		}

		$product_id = $order_item->get_product_id();

		if ( ! gr8r_session_bundles_is_bundle_product( $product_id ) ) {
			return;
		}

		$bundled_products = gr8r_session_bundles_get_product_bundle_meta( $product_id );

		if ( is_array( $bundled_products ) && [] !== $bundled_products ) {
			gr8r_session_bundles_save_order_item_bundle_meta( $order_item_id, $bundled_products );
			gr8r_session_bundles_save_order_item_is_bundle_meta( $order_item_id, true );
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
