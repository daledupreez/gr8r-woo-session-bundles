<?php
/**
 * Session Bundle Admin Class
 *
 * @package Gr8r_Woo_Session_Bundles
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Session Bundle Admin Class
 */
class GR8R_Woo_Session_Bundles_Admin {

	private static $actions = [];

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
		add_action( 'woocommerce_product_options_general_product_data', array( $this, 'add_session_bundle_options' ) );
		add_action( 'woocommerce_admin_process_product_object', array( $this, 'save_bundled_session_meta' ) );

		add_filter( 'product_type_options', array( $this, 'add_session_bundle_toggle' ) );

		add_action( 'dokan_enqueue_scripts', array( $this, 'enqueue_dokan_admin_scripts' ) );
		add_action( 'dokan_product_edit_after_title', array( $this, 'render_dokan_session_bundle_toggle' ), 10, 1 );
		add_action( 'dokan_product_edit_after_pricing_fields', array( $this, 'add_session_bundle_options' ) );

		add_action( 'dokan_new_product_added', array( $this, 'save_bundled_session_meta_for_dokan' ), 10, 2 );
		add_action( 'dokan_product_updated', array( $this, 'save_bundled_session_meta_for_dokan' ), 10, 2 );

		// AJAX handlers.
		add_action( 'wp_ajax_gr8r_woo_session_bundles_search_products', array( $this, 'ajax_search_products' ) );
		add_action( 'wp_ajax_gr8r_woo_session_bundles_get_summary', array( $this, 'ajax_get_bundle_summary' ) );
		add_action( 'wp_ajax_gr8r_woo_session_bundles_check_stock', array( $this, 'ajax_check_stock' ) );
		add_action( 'wp_ajax_gr8r_woo_session_bundles_get_product_data', array( $this, 'ajax_get_product_data' ) );
	
		add_filter( 'woocommerce_hidden_order_itemmeta', array( $this, 'hide_order_item_bundle_meta' ) );
	}

	/**
	 * Add Session Bundle options to general product data
	 */
	public function add_session_bundle_options() {
		global $post;

		$product = wc_get_product( $post->ID );

		if ( ! $product || ! in_array( $product->get_type(), GR8R_Woo_Session_Bundles_Configuration::get_instance()->get_supported_product_types(), true ) ) {
			return;
		}

		$bundled_sessions       = gr8r_session_bundles_get_product_bundle_meta( $product->get_id() );
		$bundled_sessions_count = count( $bundled_sessions );
		$add_button_class       = $bundled_sessions_count > 0 ? 'button-secondary' : 'button-primary';
		$bundled_sessions_json  = $bundled_sessions_count > 0 ? json_encode( $bundled_sessions, true ) : '{}';

		$is_subscription  = 'subscription' === $product->get_type();
		$validity_meta    = gr8r_session_bundles_get_product_bundle_validity_meta( $product->get_id() );
		$validity_count   = $validity_meta['count'] ?? '';
		$validity_period  = $validity_meta['period'] ?? '';
		$validity_periods = GR8R_Woo_Session_Bundles_Configuration::get_instance()->get_validity_periods();

		$group_class = is_admin() ? 'options_group' : 'dokan-form-group';
		// Hide by default and show when we detect the value in the client data.
		?>
			<div class="<?php esc_attr_e( $group_class ); ?> show_if_bundled_sessions" style="display: none;">
				<input type="hidden" id="gr8r-bundled-sessions" name="gr8r_bundled_sessions" value="<?php echo esc_attr( $bundled_sessions_json ); ?>" />
		<?php

		wp_nonce_field( 'gr8r_woo_session_bundles_meta', '_gr8r_woo_session_bundles_meta_nonce' );

		if ( ! $is_subscription ) : ?>
		<div class="gr8r-woo-session-bundles-validity-period hide_if_subscription <?php echo esc_attr( $group_class ); ?>" style="display: none;">
			<h3 class="gr8r-woo-session-bundles-title">
				<?php esc_html_e( 'Bundle Validity', 'gr8r-woo-session-bundles' ); ?>
			</h3>
			<p class="form-field gr8r-woo-session-bundles-validity-period-field">
				<label for="gr8r-bundle-validity-period-count"><?php esc_html_e( 'How long the bundle should be valid for.', 'gr8r-woo-session-bundles' ); ?></label>
				<input
					type="number"
					id="gr8r-bundle-validity-period-count"
					name="gr8r_bundle_validity_period_count"
					value="<?php echo esc_attr( $validity_count ); ?>"
					min="1"
					step="1"
					placeholder="<?php esc_attr_e( 'e.g., 3', 'gr8r-woo-session-bundles' ); ?>"
				/>
				<select
					id="gr8r-bundle-validity-period"
					name="gr8r_bundle_validity_period"
				>
					<option value=""><?php esc_html_e( 'Select period...', 'gr8r-woo-session-bundles' ); ?></option>
					<?php foreach ( $validity_periods as $period => $configuration ) : ?>
						<option value="<?php echo esc_attr( $period ); ?>" <?php echo ( $validity_period === $period ) ? 'selected' : ''; ?>><?php echo esc_html( $configuration['label'] ); ?></option>
					<?php endforeach; ?>
				</select>
			</p>
		</div>
		<?php endif; ?>

		<div class="gr8r-woo-session-bundles-bundled-session-list">
			<h3 class="gr8r-woo-session-bundles-title">
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
	 * Render the session bundle toggle for products in the Dokan product editor.
	 *
	 * @param WP_Post $post The post object.
	 * @return void
	 */
	public function render_dokan_session_bundle_toggle( $post ) {
		$show_if_classes = $this->get_show_if_wrapper_class();
		$is_session_bundle = gr8r_session_bundles_is_bundle_product( $post->ID ?? 0 );

		?>
		<div class="dokan-form-group dokan-product-type-container <?php echo esc_attr( $show_if_classes ); ?>">
			<div class="content-half-part">
				<label>
					<input type="checkbox" <?php checked( $is_session_bundle, true ); ?> class="_gr8r_is_session_bundle" name="_gr8r_is_session_bundle" id="_gr8r_is_session_bundle"> <?php esc_html_e( 'Session Bundle', 'gr8r-woo-session-bundles' ); ?> <i class="fas fa-question-circle tips" aria-hidden="true" data-title="<?php esc_attr_e( 'Session bundles represent a bundle of sessions that can be purchased at once, and then booked later.', 'gr8r-woo-session-bundles' ); ?>"></i>
				</label>
			</div>
			<div class="dokan-clearfix"></div>
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
	 * Enqueue admin scripts.
	 *
	 * @param string $hook Current admin page.
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

		$this->really_enqueue_admin_scripts();
	}

	/**
	 * Enqueue admin scripts for the Dokan product editor.
	 */
	public function enqueue_dokan_admin_scripts() {
		if ( ! GR8R_Woo_Session_Bundles::instance()->is_dokan_product_edit_page() ) {
			return;
		}

		$current_post = get_post( (int) wc_clean( wp_unslash( $_GET['product_id'] ?? '' ) ) );

		$this->really_enqueue_admin_scripts( $current_post, 'dokan' );
	}

	/**
	 * Helper function to actually enqueue the admin scripts.
	 *
	 * @param WP_Post|null $current_post The current post object.
	 * @param string $context The context of the scripts.
	 * @return void
	 */
	protected function really_enqueue_admin_scripts( $current_post = null, string $context = 'admin' ) {
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
		if ( ! $current_post ) {
			$screen = get_current_screen();
			if ( $screen && $screen->id === 'product' && $screen->base === 'post' ) {
				$current_post = get_post();
			}
		}

		if ( $current_post && gr8r_session_bundles_is_bundle_product( $current_post->ID ) ) {
			$bundled_product_details = $this->get_product_details_for_bundled_sessions( $current_post->ID, $context );
		}

		wp_localize_script(
			'gr8r-woo-session-bundles-admin',
			'gr8r_woo_session_bundles_admin',
			array(
				'ajax_url'                => admin_url( 'admin-ajax.php' ),
				'nonce'                   => wp_create_nonce( 'gr8r_woo_session_bundles_admin_nonce' ),
				'bundled_product_details' => $bundled_product_details,
				'product_types'           => GR8R_Woo_Session_Bundles_Configuration::get_instance()->get_supported_product_types(),
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

	/**
	 * Helper function to get the product details for the bundled sessions.
	 *
	 * @param int    $product_id The product ID.
	 * @param string $context    The context we will be showing the product details in.
	 * @return array The product details for the bundled sessions.
	 */
	protected function get_product_details_for_bundled_sessions( $product_id, string $context = 'admin' ): array {
		$product_details = array();

		$bundled_sessions = gr8r_session_bundles_get_product_bundle_meta( $product_id );

		foreach ( array_keys( $bundled_sessions ) as $bundled_product_id ) {
			$bundled_product = wc_get_product( $bundled_product_id );
			if ( $bundled_product ) {
				$product_details[ '_' . $bundled_product_id ] = $this->get_product_details( $bundled_product, $context );
			}
		}

		return $product_details;
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
		$supported_product_types = GR8R_Woo_Session_Bundles_Configuration::get_instance()->get_supported_product_types();

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

	/**
	 * Save the bundled session meta for a Dokan product.
	 *
	 * @param int   $product_id The product ID.
	 * @param array $post_data The post data.
	 * @return void
	 */
	public function save_bundled_session_meta_for_dokan( $product_id, $post_data ) {
		if ( ! function_exists( 'dokan_is_user_seller') || ! dokan_is_user_seller( get_current_user_id() ) ) {
			return;
		}

		$product = wc_get_product( $product_id );
		
		if ( ! $product instanceof WC_Product ) {
			return;
		}

		$cleaned_fields = $this->get_save_fields( $post_data, false );

		$dokan_vendor_id = dokan_get_current_user_id();

		$this->really_save_bundled_session_meta( $product, $cleaned_fields, $dokan_vendor_id );
	}

	/**
	 * Save the bundled session meta for a product. Relies on POSTed data to build the data.
	 *
	 * @param WC_Product $product The product object.
	 * @return void
	 */
	public function save_bundled_session_meta( $product ) {
		$cleaned_fields = $this->get_save_fields( $_POST, true );

		$this->really_save_bundled_session_meta( $product, $cleaned_fields, null );
	}

	/**
	 * Really save the bundled session meta for a product.
	 * Requires callers to have extracted the relevant data from the submission.
	 *
	 * @param WC_Product $product         The product object.
	 * @param array      $fields          The data extracted from the product save submission.
	 * @param int|null   $dokan_vendor_id The Dokan vendor ID.
	 * @return void
	 */
	protected function really_save_bundled_session_meta( $product, array $fields, ?int $dokan_vendor_id = null ) {
		if ( ! isset( $fields['_gr8r_woo_session_bundles_meta_nonce'] ) || ! wp_verify_nonce( $fields['_gr8r_woo_session_bundles_meta_nonce'], 'gr8r_woo_session_bundles_meta' ) ) {
			return;
		}

		if ( ! isset( $fields['_gr8r_is_session_bundle'] ) || ! in_array( $fields['_gr8r_is_session_bundle'], array( 'yes', 'on', 'checked' ), true ) ) {
			return;
		}

		if ( ! $product instanceof WC_Product ) {
			return;
		}

		$supported_product_types = GR8R_Woo_Session_Bundles_Configuration::get_instance()->get_supported_product_types();

		if ( ! in_array( $product->get_type(), $supported_product_types, true ) ) {
			return;
		}

		if ( $this->is_action_done( 'save_bundled_session_meta', $product->get_id() ) ) {
			return;
		}

		$gr8r_bundled_sessions = array();
		if ( isset( $fields['gr8r_bundled_sessions'] ) ) {
			$gr8r_bundled_sessions = json_decode( $fields['gr8r_bundled_sessions'], true );

			if ( ! is_array( $gr8r_bundled_sessions ) ) {
				$gr8r_bundled_sessions = array();
			} else {
				// Basic value checks
				$gr8r_bundled_sessions = gr8r_session_bundles_sanitize_bundle_data( $gr8r_bundled_sessions );
				$gr8r_bundled_sessions = array_filter(
					$gr8r_bundled_sessions,
					function( $product_id ) use ( $dokan_vendor_id ) {
						if ( gr8r_session_bundles_is_bundle_product( $product_id ) ) {
							return false;
						}

						if ( $dokan_vendor_id ) {
							$post_author = get_post_field( 'post_author', $product_id );
							if ( '' === $post_author || '0' === $post_author ) {
								return false;
							}

							return $dokan_vendor_id === (int) $post_author;
						}
						return true;
					},
					ARRAY_FILTER_USE_KEY
				);
			}
		}

		gr8r_session_bundles_save_product_bundle_meta( $product->get_id(), $gr8r_bundled_sessions );
		gr8r_session_bundles_save_product_is_bundle_meta( $product->get_id(), true );

		// Save validity period fields only for non-subscription products
		if ( 'subscription' !== $product->get_type() ) {
			$validity_count = null;
			$validity_period = null;

			if ( isset( $fields['gr8r_bundle_validity_period_count'] ) && ! empty( $fields['gr8r_bundle_validity_period_count'] ) ) {
				$validity_count = absint( $fields['gr8r_bundle_validity_period_count'] );
				if ( $validity_count <= 0 ) {
					$validity_count = null;
				}
			}

			if ( isset( $fields['gr8r_bundle_validity_period'] ) && ! empty( $fields['gr8r_bundle_validity_period'] ) ) {
				$validity_period = sanitize_text_field( $fields['gr8r_bundle_validity_period'] );
				$allowed_periods = GR8R_Woo_Session_Bundles_Configuration::get_instance()->get_supported_validity_periods();
				if ( ! in_array( $validity_period, $allowed_periods, true ) ) {
					$validity_period = null;
				}
			}

			// Only save if both are provided, otherwise delete
			if ( null !== $validity_count && null !== $validity_period ) {
				gr8r_session_bundles_save_product_validity_period_meta( $product->get_id(), $validity_count, $validity_period );
			} else {
				gr8r_session_bundles_save_product_validity_period_meta( $product->get_id(), null, null );
			}
		} else {
			// For subscription products, ensure validity period is deleted
			gr8r_session_bundles_save_product_validity_period_meta( $product->get_id(), null, null );
		}

		$this->mark_action_done( 'save_bundled_session_meta', $product->get_id() );
	}

	/**
	 * Helper function to get the fields to save for the bundled session meta.
	 *
	 * @param array $post_data      The POSTed data.
	 * @param bool  $should_unslash Whether to unslash the data.
	 * @return array The fields to save.
	 */
	protected function get_save_fields( $post_data, bool $should_unslash = true ) {
		$fields = array(
			'_gr8r_woo_session_bundles_meta_nonce',
			'_gr8r_is_session_bundle',
			'gr8r_bundled_sessions',
			'gr8r_bundle_validity_period_count',
			'gr8r_bundle_validity_period',
		);

		$cleaned_fields = array();
		foreach ( $fields as $field ) {
			if ( isset( $post_data[ $field ] ) ) {
				if ( $should_unslash ) {
					$cleaned_fields[ $field ] = wc_clean( wp_unslash( $post_data[ $field ] ) );
				} else {
					$cleaned_fields[ $field ] = wc_clean( $post_data[ $field ] );
				}
			}
		}

		return $cleaned_fields;
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
		$include_ids = array();
		$exclude_ids = array();

		$http_referrer = sanitize_url( wp_unslash( $_SERVER['HTTP_REFERER'] ?? '' ) );
		$dokan_vendor_id = null;
		if ( '' !== $http_referrer ) {
			$referrer_path = parse_url( $http_referrer, PHP_URL_PATH );
			if ( str_starts_with( $referrer_path, '/dashboard/' ) && function_exists( 'dokan' ) ) {
				$dokan_vendor_id = dokan_get_current_user_id();
			}
		}

		if ( ! empty( $_GET['exclude_ids'] ) ) {
			$exclude_ids = array_map( 'absint', (array) wp_unslash( $_GET['exclude_ids'] ) );
		}

		if ( ! empty( $search_term ) ) {
			$allowed_bundled_product_types = GR8R_Woo_Session_Bundles_Configuration::get_instance()->get_allowed_bundled_product_types();

			if ( ! empty( $allowed_bundled_product_types ) ) {
				if ( $dokan_vendor_id ) {
					$include_ids = dokan()->product->all( array( 'author' => $dokan_vendor_id, 'fields' => 'ids', 'product_type' => $allowed_bundled_product_types ) )->posts;
				}

				$data_store  = WC_Data_Store::load( 'product' );
				$product_ids = $data_store->search_products( $search_term, '', false, false, 30, $include_ids, $exclude_ids );

				foreach ( $product_ids as $product_id ) {
					$product = wc_get_product( $product_id );

					if ( ! $product ) {
						continue;
					}

					// If we have a vendor, we already filterd on product type, otherwise we still need to filter
					if ( $dokan_vendor_id || in_array( $product->get_type(), $allowed_bundled_product_types, true ) ) {
						$products[] = $this->get_product_details( $product, $dokan_vendor_id ? 'dokan' : 'admin' );
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
	protected function get_product_details( $product, string $context = 'admin' ): array {
		$edit_url = $context === 'dokan' && function_exists( 'dokan_edit_product_url' ) ? dokan_edit_product_url( $product ) : get_edit_post_link( $product->get_id() );
		return array(
			'id'        => $product->get_id(),
			'name'      => $product->get_name(),
			'price'     => $product->get_price(),
			'priceHTML' => $product->get_price_html(),
			'url'       => $product->get_permalink(),
			'edit_url'  => $edit_url,
		);
	}

	/**
	 * AJAX handler for getting bundle summary
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
