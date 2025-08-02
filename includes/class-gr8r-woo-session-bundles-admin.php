<?php
/**
 * Session Bundle Admin Class
 *
 * @package Gr8r_Woo_Session_Bundles
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Session Bundle Admin Class
 *
 * @since 1.0.0
 */
class GR8R_Woo_Session_Bundles_Admin {

	private static $actions = [];

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
		add_action( 'woocommerce_product_options_general_product_data', array( $this, 'add_session_bundle_options' ) );
		add_action( 'woocommerce_admin_process_product_object', array( $this, 'save_bundled_session_meta' ) );

		add_filter( 'product_type_options', array( $this, 'add_session_bundle_toggle' ) );

		// AJAX handlers.
		add_action( 'wp_ajax_gr8r_woo_session_bundles_search_products', array( $this, 'ajax_search_products' ) );
		add_action( 'wp_ajax_gr8r_woo_session_bundles_get_summary', array( $this, 'ajax_get_bundle_summary' ) );
		add_action( 'wp_ajax_gr8r_woo_session_bundles_check_stock', array( $this, 'ajax_check_stock' ) );
		add_action( 'wp_ajax_gr8r_woo_session_bundles_get_product_data', array( $this, 'ajax_get_product_data' ) );
	
		add_filter( 'woocommerce_hidden_order_itemmeta', array( $this, 'hide_order_item_bundle_meta' ) );
	}

	/**
	 * Add Session Bundle options to general product data
	 *
	 * @since 1.0.0
	 */
	public function add_session_bundle_options() {
		global $post;

		$product = wc_get_product( $post->ID );

		if ( ! $product || ! in_array( $product->get_type(), $this->get_supported_product_types(), true ) ) {
			return;
		}

		$bundled_sessions = gr8r_session_bundles_get_product_bundle_meta( $product->get_id() );
		$bundled_sessions_count = count( $bundled_sessions );
		$add_button_class = $bundled_sessions_count > 0 ? 'button-secondary' : 'button-primary';
		$bundled_sessions_json =  $bundled_sessions_count > 0 ? json_encode( $bundled_sessions, true ) : '{}';

		// Hide by default and show when we detect the value in the client data.
		?>
			<div class="options_group show_if_bundled_sessions" style="display: none;">
				<input type="hidden" id="gr8r-bundled-sessions" name="gr8r_bundled_sessions" value="<?php echo esc_attr( $bundled_sessions_json ); ?>" />
		<?php

		wp_nonce_field( 'gr8r_woo_session_bundles_meta', '_gr8r_woo_session_bundles_meta_nonce' );

		?>
		<div class="gr8r-woo-session-bundles-bundled-session-list">
			<h3 class="gr8r-woo-session-bundles-bundled-session-list-title">
				<?php esc_html_e( 'Bundled Sessions', 'gr8r-woo-session-bundles' ); ?>
			</h3>
			<p class="gr8r-woo-session-bundles-no-bundled-sessions" style="<?php echo $bundled_sessions_count > 0 ? 'display: none;' : ''; ?>">
				<?php esc_html_e( 'No bundled sessions found.', 'gr8r-woo-session-bundles' ); ?>
			</p>
			<table class="gr8r-woo-session-bundles-bundled-session-list-table" style="<?php echo $bundled_sessions_count === 0 ? 'display: none;' : ''; ?>">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Product Details', 'gr8r-woo-session-bundles' ); ?></th>
						<th><?php esc_html_e( 'Price', 'gr8r-woo-session-bundles' ); ?></th>
						<th><?php esc_html_e( 'Quantity', 'gr8r-woo-session-bundles' ); ?></th>
						<th><?php esc_html_e( 'Actions', 'gr8r-woo-session-bundles' ); ?></th>
					</tr>
				</thead>
				<tbody class="gr8r-woo-session-bundles-bundled-session-list-body"></tbody>
			</table>
		</div>
		<div class="gr8r-woo-session-bundles-bundled-session-value">
			<?php esc_html_e( 'Effective bundle value:', 'gr8r-woo-session-bundles' ); ?>
			<span class="gr8r-woo-session-bundles-bundled-session-value-total">...</span>
		</div>
		<div class="gr8r-woo-session-bundles-add-bundled-session">
			<select
				id="gr8r-woo-session-bundles-add-bundled-product-selector"
				data-placeholder="<?php esc_attr_e( 'Search for products...', 'gr8r-woo-session-bundles' ); ?>"
			>
				<option value=""><?php esc_html_e( 'Search for products...', 'gr8r-woo-session-bundles' ); ?></option>
			</select>
			<button type="button" id="gr8r-woo-session-bundles-add-bundle-product" class="<?php esc_attr_e( $add_button_class ); ?>">
				<?php esc_html_e( 'Add Product', 'gr8r-woo-session-bundles' ); ?>
			</button>
		</div>
		</div>
		<?php
	}

	/**
	 * Helper function to ensure that we don't render the gr8r meta fields in the orders UI.
	 *
	 * @param string[] $hidden_order_itemmeta The hidden meta fields.
	 * @return string[]
	 */
	public function hide_order_item_bundle_meta( array $hidden_order_itemmeta ): array {
		$hidden_order_itemmeta[] = '_gr8r_is_bundle';
		$hidden_order_itemmeta[] = '_gr8r_bundled_products';
		return $hidden_order_itemmeta;
	}

	/**
	 * Enqueue admin scripts
	 *
	 * @param string $hook Current admin page.
	 * @since 1.0.0
	 */
	public function enqueue_admin_scripts( $hook ) {
		global $post_type;

		$should_enqueue = false;
		$screen = get_current_screen();

		if ( 'product' === $post_type || 'shop_order' === $post_type ) {
			$should_enqueue = true;
		} elseif ( 'shop_order' === $screen->post_type ) {
			$should_enqueue = true;
		}

		if ( ! $should_enqueue ) {
			return;
		}

		wp_enqueue_script(
			'gr8r-woo-session-bundles-admin',
			GR8R_WOO_SESSION_BUNDLE_PLUGIN_URL . 'assets/js/admin.js',
			array( 'jquery', 'select2' ),
			GR8R_WOO_SESSION_BUNDLE_VERSION,
			true
		);

		wp_enqueue_style(
			'gr8r-woo-session-bundles-admin',
			GR8R_WOO_SESSION_BUNDLE_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			GR8R_WOO_SESSION_BUNDLE_VERSION
		);

		$bundled_product_details = array();

		if ( $screen && $screen->id === 'product' && $screen->base === 'post' ) {
			$current_post = get_post();
			if ( $current_post ) {
				if ( gr8r_session_bundles_is_bundle_product( $current_post->ID ) ) {
					$bundled_product_details = $this->get_product_details_for_bundled_sessions( $current_post->ID );
				}
			}
		}

		wp_localize_script(
			'gr8r-woo-session-bundles-admin',
			'gr8r_woo_session_bundles_admin',
			array(
				'ajax_url'                => admin_url( 'admin-ajax.php' ),
				'nonce'                   => wp_create_nonce( 'gr8r_woo_session_bundles_admin_nonce' ),
				'bundled_product_details' => $bundled_product_details,
				'product_types'           => $this->get_supported_product_types(),
				'strings'                 => array(
					'select_products' => __( 'Search for products...', 'gr8r-woo-session-bundles' ),
					'remove_product'  => __( 'Remove', 'gr8r-woo-session-bundles' ),
					'quantity'        => __( 'Quantity', 'gr8r-woo-session-bundles' ),
					'view_product'    => __( 'View', 'gr8r-woo-session-bundles' ),
					'edit_product'    => __( 'Edit', 'gr8r-woo-session-bundles' ),
				),
			)
		);
	}

	protected function get_product_details_for_bundled_sessions( $product_id ): array {
		$product_details = array();

		$bundled_sessions = gr8r_session_bundles_get_product_bundle_meta( $product_id );
		
		foreach ( array_keys( $bundled_sessions ) as $bundled_product_id ) {
			$bundled_product = wc_get_product( $bundled_product_id );
			if ( $bundled_product ) {
				$product_details[ '_' . $bundled_product_id ] = $this->get_product_details( $bundled_product );
			}
		}

		return $product_details;
	}

	/**
	 * Get the supported product types for session bundles.
	 *
	 * @return string[] The supported product types.
	 */
	public function get_supported_product_types(): array {
		$default_product_types = array( 'simple', 'subscription' );

		/**
		 * Filter the supported product types for session bundles. Defaults to simple and subscription products.
		 *
		 * @param string[] $supported_product_types The supported product types.
		 * @since 1.0.0
		 */
		return apply_filters( 'gr8r_woo_session_bundles_supported_product_types', $default_product_types );
	}

	/**
	 * Get the allowed bundled product types for session bundles.
	 *
	 * @return string[] The allowed bundled product types.
	 */
	public function get_allowed_bundled_product_types(): array {
		$default_product_types = array( 'booking' );

		/**
		 * Filter the allowed bundled product types for session bundles. Defaults to booking products.
		 *
		 * @param string[] $allowed_bundled_product_types The allowed bundled product types.
		 */
		return apply_filters( 'gr8r_woo_session_bundles_allowed_bundled_product_types', $default_product_types );
	}

	/**
	 * Add Session Bundle checkbox to simple and subscription products.
	 */
	public function add_session_bundle_toggle( array $options ) {
		$options['gr8r_is_session_bundle'] = array(
			'id'            => '_gr8r_is_session_bundle',
			'wrapper_class' => $this->get_show_if_wrapper_class(),
			'label'         => __( 'Session Bundle', 'gr8r-woo-session-bundles' ),
			'description'   => __( 'Session bundles represent a bundle of sessions that can be purchased at once, and then booked later.', 'gr8r-woo-session-bundles' ),
			'default'       => 'no',
		);
		return $options;
	}

	protected function get_show_if_wrapper_class(): string {
		$supported_product_types = $this->get_supported_product_types();

		return implode(
			' ',
			array_map(
				function( $type ) {
					return 'show_if_' . $type;
				},
				$supported_product_types
			)
		);
	}

	public function save_bundled_session_meta( $product ) {
		if ( ! isset( $_POST['_gr8r_woo_session_bundles_meta_nonce'] ) || ! wp_verify_nonce( wc_clean( wp_unslash( $_POST['_gr8r_woo_session_bundles_meta_nonce'] ) ), 'gr8r_woo_session_bundles_meta' ) ) {
			return;
		}

		if ( ! isset( $_POST['_gr8r_is_session_bundle'] ) || ! in_array( $_POST['_gr8r_is_session_bundle'], array( 'yes', 'on', 'checked' ), true ) ) {
			return;
		}

		if ( ! $product instanceof WC_Product ) {
			return;
		}

		$supported_product_types = $this->get_supported_product_types();

		if ( ! in_array( $product->get_type(), $supported_product_types, true ) ) {
			return;
		}

		if ( $this->is_action_done( 'save_bundled_session_meta', $product->get_id() ) ) {
			return;
		}

		$gr8r_bundled_sessions = array();
		if ( isset( $_POST['gr8r_bundled_sessions'] ) ) {
			$gr8r_bundled_sessions = json_decode( wc_clean( wp_unslash( $_POST['gr8r_bundled_sessions'] ) ), true );

			if ( ! is_array( $gr8r_bundled_sessions ) ) {
				$gr8r_bundled_sessions = array();
			} else {
				// Basic value checks
				$gr8r_bundled_sessions = gr8r_session_bundles_sanitize_bundle_data( $gr8r_bundled_sessions );
				$gr8r_bundled_sessions = array_filter(
					$gr8r_bundled_sessions,
					function( $product_id ) {
						if ( gr8r_session_bundles_is_bundle_product( $product_id ) ) {
							return false;
						}

						// TODO: Check if the product belongs to the same vendor as the bundled product.

						return true;
					},
					ARRAY_FILTER_USE_KEY
				);
			}
		}

		gr8r_session_bundles_save_product_bundle_meta( $product->get_id(), $gr8r_bundled_sessions );
		gr8r_session_bundles_save_product_is_bundle_meta( $product->get_id(), true );

		$this->mark_action_done( 'save_bundled_session_meta', $product->get_id() );
	}

	protected function mark_action_done( string $action, $post_id = null) {
		if ( ! $post_id ) {
			self::$actions[ $action ] = true;
			return;
		}

		$post_key = "post_{$post_id}";
		if ( ! isset( self::$actions[ $action ] ) ) {
			self::$actions[ $action ] = array();
		}

		self::$actions[ $action ][ $post_key ] = true;
	}

	protected function is_action_done( string $action, $post_id = null ): bool {
		if ( ! isset( self::$actions[ $action ] ) ) {
			return false;
		}

		if ( ! $post_id ) {
			return ! empty( self::$actions[ $action ] );
		}

		$post_key = "post_{$post_id}";

		return isset( self::$actions[ $action ][ $post_key ] ) && true === self::$actions[ $action ][ $post_key ];
	}
	/**
	 * AJAX handler for searching products
	 *
	 * @since 1.0.0
	 */
	public function ajax_search_products() {
		// Check nonce.
		check_ajax_referer( 'gr8r_woo_session_bundles_admin_nonce', 'nonce' );

		// Check permissions.
		if ( ! current_user_can( 'edit_products' ) ) {
			wp_send_json_error( 'insufficient_permissions' );
			wp_die();
		}

		$search_term = sanitize_text_field( wp_unslash( $_GET['search'] ?? '' ) );
		$page        = intval( $_GET['page'] ?? 1 );
		$result      = array();
		$products    = array();
		$exclude_ids = array();

		if ( ! empty( $_GET['exclude_ids'] ) ) {
			$exclude_ids = array_map( 'absint', (array) wp_unslash( $_GET['exclude_ids'] ) );
		}

		// TODO: Add a filter or logic to limit the results to the vendor for the current user. Maybe Dokan offers a vendor-specific search API?

		if ( ! empty( $search_term ) ) {
			$allowed_bundled_product_types = $this->get_allowed_bundled_product_types();

			if ( ! empty( $allowed_bundled_product_types ) ) {
				$data_store  = WC_Data_Store::load( 'product' );
				$product_ids = $data_store->search_products( $search_term, '', false, false, 30, array(), $exclude_ids );

				foreach ( $product_ids as $product_id ) {
					$product = wc_get_product( $product_id );

					if ( $product && in_array( $product->get_type(), $allowed_bundled_product_types, true ) ) {
						$products[] = $this->get_product_details( $product );
					}
				}
			}
		}

		$result['products'] = $products;

		wp_send_json( $result );
	}

	/**
	 * Helper function to build consistent product details.
	 */
	protected function get_product_details( $product ): array {
		return array(
			'id'        => $product->get_id(),
			'name'      => $product->get_name(),
			'price'     => $product->get_price(),
			'priceHTML' => $product->get_price_html(),
			'url'       => $product->get_permalink(),
			'edit_url'  => get_edit_post_link( $product->get_id() ),
		);
	}

	/**
	 * AJAX handler for getting bundle summary
	 *
	 * @since 1.0.0
	 */
	public function ajax_get_bundle_summary() {
		// Check nonce and permissions.
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'gr8r_woo_session_bundles_admin_nonce' ) || ! current_user_can( 'edit_products' ) ) {
			wp_die();
		}

		$product_id = intval( $_POST['product_id'] );
		$product    = wc_get_product( $product_id );

		if ( $product ) {
			$summary = GR8R_Woo_Session_Bundles_Frontend::get_bundle_summary( $product_id );
			wp_send_json_success( $summary );
		}

		wp_send_json_error();
	}

	/**
	 * AJAX handler for checking stock
	 *
	 * @since 1.0.0
	 */
	public function ajax_check_stock() {
		// Check nonce and permissions.
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'gr8r_woo_session_bundles_admin_nonce' ) || ! current_user_can( 'edit_products' ) ) {
			wp_die();
		}

		$product_id = intval( $_POST['product_id'] );
		$quantity   = intval( $_POST['quantity'] );
		$product    = wc_get_product( $product_id );

		if ( $product ) {
			$stock_status = array(
				'in_stock'    => $product->is_in_stock(),
				'stock_qty'   => $product->get_stock_quantity(),
				'can_purchase' => $product->is_purchasable(),
			);
			wp_send_json_success( $stock_status );
		}

		wp_send_json_error();
	}

	/**
	 * AJAX handler for getting product data
	 *
	 * @since 1.0.0
	 */
	public function ajax_get_product_data() {
		// Check nonce and permissions.
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'gr8r_woo_session_bundles_admin_nonce' ) || ! current_user_can( 'edit_products' ) ) {
			wp_die();
		}

		$product_id = intval( $_POST['product_id'] );
		$product    = wc_get_product( $product_id );

		if ( $product ) {
			$product_data = array(
				'id'       => $product->get_id(),
				'name'     => $product->get_name(),
				'price'    => $product->get_price(),
				'price_html' => $product->get_price_html(),
				'in_stock' => $product->is_in_stock(),
				'stock_qty' => $product->get_stock_quantity(),
			);
			wp_send_json_success( $product_data );
		}

		wp_send_json_error();
	}
}
