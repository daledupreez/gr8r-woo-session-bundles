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
 */
class GR8R_Woo_Session_Bundles_Frontend {

	/**
	 * Constructor
	 */
	public function __construct() {
		if ( class_exists( 'Elementor\Plugin' ) ) {
			add_filter( 'the_content', array( $this, 'add_bundle_description_to_content' ), 25, 1 );
		} else {
			add_action( 'woocommerce_single_product_summary', array( $this, 'display_bundle_description' ), 25 );
		}
		add_action( 'woocommerce_after_shop_loop_item_title', array( $this, 'display_bundle_description_loop' ), 15 );

		//add_action( 'woocommerce_before_add_to_cart_button', array( $this, 'display_bundle_contents' ) );
		add_action( 'woocommerce_after_add_to_cart_button', array( $this, 'render_available_coupon_details' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_scripts' ) );

		add_action( 'woocommerce_new_order_item', array( $this, 'handle_new_order_item' ), 10, 2 );

		// Order display for bundle
		add_filter( 'woocommerce_order_item_class', array( $this, 'add_bundle_order_item_class' ), 10, 2 );
		add_action( 'woocommerce_order_item_meta_end', array( $this, 'render_order_item_bundle_details' ), 10, 4 );
		add_action( 'woocommerce_after_order_itemmeta', array( $this, 'render_order_line_item_bundle' ), 10, 2 );

		add_action( 'woocommerce_payment_complete', array( $this, 'handle_payment_complete' ), 10, 2 );

		add_action( 'woocommerce_add_to_cart', array( $this, 'maybe_add_coupon_to_cart' ), 10, 2 );
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
	 */
	public function display_bundle_description() {
		$bundle_description = $this->get_bundle_description();

		if ( '' !== $bundle_description ) {
			echo $bundle_description; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}

	/**
	 * Get the bundle description HTML for a single product page.
	 *
	 * @return string
	 */
	public function get_bundle_description() {
		global $product;

		if ( ! $product || ! gr8r_session_bundles_is_bundle_product( $product->get_id() ) ) {
			return '';
		}

		$bundle_description = gr8r_session_bundles_get_product_bundle_description( $product->get_id() );

		if ( empty( $bundle_description ) ) {
			return '';
		}

		return '<div class="gr8r-session-bundle-description">' . $bundle_description . '</div>';
	}

	/**
	 * Add the bundle description to the content.
	 *
	 * @param string $content The content.
	 * @return string The content with the bundle description added.
	 */
	public function add_bundle_description_to_content( string $content ): string {
		$bundle_description = $this->get_bundle_description();

		if ( '' === $bundle_description ) {
			return $content;
		}

		return $bundle_description . $content;
	}

	/**
	 * Display bundle description in product loop
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
	 * Maybe add a coupon to the cart based on the product being added to the cart.
	 *
	 * @param string $cart_item_key The cart item key.
	 * @param int    $product_id    The product ID being added to the cart.
	 */
	public function maybe_add_coupon_to_cart( $cart_item_key, $product_id ): void {
		if ( ! $product_id ) {
			return;
		}

		$user_id = get_current_user_id();
		if ( 0 >= $user_id ) {
			return;
		}

		/**
		 * Make it possible to use an alternative mechanism to apply credits to the cart when adding an eligible product.
		 *
		 * @param bool   $apply_credits Whether to apply credits to the cart. Defaults to true.
		 * @param int    $product_id    The product ID being added to the cart.
		 * @param string $cart_item_key The cart item key.
		 * @param int    $user_id       The user ID of the purchaser.
		 */
		$apply_credits = apply_filters( 'gr8r_woo_session_bundles_apply_credits_to_cart', true, $product_id, $cart_item_key, $user_id );
		if ( false === $apply_credits ) {
			return;
		}

		$coupon_id = GR8R_Woo_Session_Bundles_Coupon_Utils::get_instance()->get_next_available_coupon_id( $product_id, $user_id );
		if ( null === $coupon_id ) {
			return;
		}

		$coupon = new WC_Coupon( $coupon_id );

		WC()->cart->apply_coupon( $coupon->get_code() );
	}

	/**
	 * Render details about an available coupon in the product content.
	 *
	 * @return void
	 */
	public function render_available_coupon_details(): void {
		global $product;

		// If we have a product, or we have a bundled product, no need to check for coupons.
		if ( ! $product || gr8r_session_bundles_is_bundle_product( $product->get_id() ) ) {
			return;
		}

		// If we don't have a logged-in user, no need to check either.
		$wp_user_id = get_current_user_id();
		if ( $wp_user_id <= 0 ) {
			return;
		}

		$coupon_id = GR8R_Woo_Session_Bundles_Coupon_Utils::get_instance()->get_next_available_coupon_id( $product->get_id(), $wp_user_id );
		if ( null === $coupon_id ) {
			return;
		}

		$product_type = $product->get_type();
		if ( 'booking' === $product_type ) {
			$bundle_notice = __( 'You can book this session for free, as you already have a bundle.', 'gr8r-woo-session-bundles' );
		} else {
			$bundle_notice = __( 'You have a bundle that includes this product for free.', 'gr8r-woo-session-bundles' );
		}

		/**
		 * Filter the text that we will show when a user has a bundle for that product. Note that the text will be HTML-escaped.
		 *
		 * @param string $notice_text  The text to show to the user.
		 * @param string $product_type The product type so the text can be product-specific.
		 * @param int    $product_id   The ID of the product.
		 * @param int    $coupon_id    The ID of the first-expiring coupon.
		 * @param int    $user_id      The current user ID.
		 */
		$bundle_notice = apply_filters( 'gr8r_bundled_product_notice', $bundle_notice, $product_type, $product->get_id(), $coupon_id, $wp_user_id );

		echo '<div class="gr8r-bundled-product-notice">';
		echo esc_html( $bundle_notice );
		echo '</div>';
	}

	/**
	 * Hook into the `woocommerce_new_order_item` action to save the bundle meta data in the newly created order item.
	 *
	 * @param int                   $order_item_id The order item ID.
	 * @param WC_Order_Item_Product $order_item    The order item product.
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

	/**
	 * Handle the payment complete action.
	 *
	 * @param int    $order_id       The order ID.
	 * @param string $transaction_id The transaction ID.
	 */
	public function handle_payment_complete( $order_id, $transaction_id ): void {
		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			return;
		}

		$order_items = $order->get_items();

		foreach ( $order_items as $order_item ) {
			if ( gr8r_session_bundles_is_bundle_order_item( $order_item ) ) {
				$this->generate_credits_for_order_item( $order_item );
			}
		}
	}

	/**
	 * Generate credits for an order item.
	 *
	 * @param WC_Order_Item_Product $order_item The order item.
	 */
	protected function generate_credits_for_order_item( $order_item ): void {
		$purchased_product_id = $order_item->get_product_id();

		$bundled_products = gr8r_session_bundles_get_order_item_bundle_meta( $order_item->get_id() );

		if ( empty( $bundled_products ) ) {
			return;
		}

		$purchased_product = wc_get_product( $purchased_product_id );
		if ( ! $purchased_product ) {
			return;
		}

		$coupon_utils = GR8R_Woo_Session_Bundles_Coupon_Utils::get_instance();
		foreach ( $bundled_products as $product_id => $quantity ) {
			$coupon_utils->generate_coupon_for_product_and_user( $product_id, $quantity, $order_item, $purchased_product );
		}
	}
}
