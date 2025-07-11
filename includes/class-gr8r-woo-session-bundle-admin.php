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
class GR8R_Woo_Session_Bundle_Admin {

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'add_session_bundle_meta_box' ) );
		add_action( 'save_post', array( $this, 'save_session_bundle_data' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
		add_action( 'woocommerce_product_options_general_product_data', array( $this, 'add_session_bundle_options' ) );

		// AJAX handlers.
		add_action( 'wp_ajax_gr8r_woo_session_bundles_search_products', array( $this, 'ajax_search_products' ) );
		add_action( 'wp_ajax_gr8r_woo_session_bundles_get_summary', array( $this, 'ajax_get_bundle_summary' ) );
		add_action( 'wp_ajax_gr8r_woo_session_bundles_check_stock', array( $this, 'ajax_check_stock' ) );
		add_action( 'wp_ajax_gr8r_woo_session_bundles_get_product_data', array( $this, 'ajax_get_product_data' ) );
	}

	/**
	 * Add Session Bundle meta box
	 *
	 * @since 1.0.0
	 */
	public function add_session_bundle_meta_box() {
		add_meta_box(
			'session-bundle-products',
			__( 'Session Bundle Products', 'gr8r-woo-session-bundles' ),
			array( $this, 'render_session_bundle_meta_box' ),
			'product',
			'normal',
			'high'
		);
	}

	/**
	 * Render Session Bundle meta box
	 *
	 * @param WP_Post $post Post object.
	 * @since 1.0.0
	 */
	public function render_session_bundle_meta_box( $post ) {
		$product = wc_get_product( $post->ID );

		if ( ! $product || 'session_bundle' !== $product->get_type() ) {
			return;
		}

		$bundled_products = $product->get_bundled_products();

		wp_nonce_field( 'save_session_bundle_data', 'session_bundle_nonce' );
		?>
		<div id="session-bundle-products-container">
			<div class="session-bundle-products-list">
				<?php if ( ! empty( $bundled_products ) ) : ?>
					<?php foreach ( $bundled_products as $product_id => $quantity ) : ?>
						<?php $bundled_product = wc_get_product( $product_id ); ?>
						<?php if ( $bundled_product ) : ?>
							<div class="session-bundle-product-item" data-product-id="<?php echo esc_attr( $product_id ); ?>">
								<div class="product-info">
									<span class="product-title"><?php echo esc_html( $bundled_product->get_name() ); ?></span>
									<span class="product-price"><?php echo $bundled_product->get_price_html(); ?></span>
								</div>
								<div class="product-quantity">
									<label for="bundle_quantity_<?php echo esc_attr( $product_id ); ?>">
										<?php esc_html_e( 'Quantity:', 'gr8r-woo-session-bundles' ); ?>
									</label>
									<input 
										type="number" 
										id="bundle_quantity_<?php echo esc_attr( $product_id ); ?>"
										name="bundle_quantities[<?php echo esc_attr( $product_id ); ?>]" 
										value="<?php echo esc_attr( $quantity ); ?>" 
										min="1" 
										class="bundle-quantity-input"
									/>
								</div>
								<button type="button" class="remove-bundle-product button-secondary">
									<?php esc_html_e( 'Remove', 'gr8r-woo-session-bundles' ); ?>
								</button>
							</div>
						<?php endif; ?>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>

			<div class="session-bundle-add-product">
				<select id="session-bundle-product-selector" class="wc-product-search" data-placeholder="<?php esc_attr_e( 'Search for products...', 'gr8r-woo-session-bundles' ); ?>">
					<option value=""><?php esc_html_e( 'Search for products...', 'gr8r-woo-session-bundles' ); ?></option>
				</select>
				<button type="button" id="add-bundle-product" class="button-secondary">
					<?php esc_html_e( 'Add Product', 'gr8r-woo-session-bundles' ); ?>
				</button>
			</div>

			<div class="session-bundle-total">
				<strong><?php esc_html_e( 'Bundle Total:', 'gr8r-woo-session-bundles' ); ?></strong>
				<span id="bundle-total-price"><?php echo wc_price( $product->get_bundle_price() ); ?></span>
			</div>
		</div>
		<?php
	}

	/**
	 * Add Session Bundle options to general product data
	 *
	 * @since 1.0.0
	 */
	public function add_session_bundle_options() {
		global $post;

		$product = wc_get_product( $post->ID );

		if ( ! $product || 'session_bundle' !== $product->get_type() ) {
			return;
		}

		echo '<div class="options_group show_if_session_bundle">';

		woocommerce_wp_text_input(
			array(
				'id'                => '_bundle_price',
				'label'             => __( 'Bundle Price', 'gr8r-woo-session-bundles' ),
				'desc_tip'          => true,
				'description'       => __( 'Set a custom price for the bundle (leave empty to calculate from bundled products).', 'gr8r-woo-session-bundles' ),
				'type'              => 'number',
				'custom_attributes' => array(
					'step' => 'any',
					'min'  => '0',
				),
			)
		);

		echo '</div>';
	}

	/**
	 * Save Session Bundle data
	 *
	 * @param int $post_id Post ID.
	 * @since 1.0.0
	 */
	public function save_session_bundle_data( $post_id ) {
		// Check nonce.
		if ( ! isset( $_POST['session_bundle_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['session_bundle_nonce'] ) ), 'save_session_bundle_data' ) ) {
			return;
		}

		// Check permissions.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$product = wc_get_product( $post_id );

		if ( ! $product || 'session_bundle' !== $product->get_type() ) {
			return;
		}

		// Save bundled products.
		if ( isset( $_POST['bundle_quantities'] ) && is_array( $_POST['bundle_quantities'] ) ) {
			$bundled_products = array();

			foreach ( $_POST['bundle_quantities'] as $product_id => $quantity ) {
				$product_id = intval( $product_id );
				$quantity   = intval( $quantity );

				if ( $product_id > 0 && $quantity > 0 ) {
					$bundled_product = wc_get_product( $product_id );
					if ( $bundled_product ) {
						$bundled_products[ $product_id ] = $quantity;
					}
				}
			}

			$product->set_bundled_products( $bundled_products );
		}

		// Save bundle price.
		if ( isset( $_POST['_bundle_price'] ) ) {
			$bundle_price = wc_format_decimal( sanitize_text_field( wp_unslash( $_POST['_bundle_price'] ) ) );
			update_post_meta( $post_id, '_bundle_price', $bundle_price );
		}

		$product->save();
	}

	/**
	 * Enqueue admin scripts
	 *
	 * @param string $hook Current admin page.
	 * @since 1.0.0
	 */
	public function enqueue_admin_scripts( $hook ) {
		global $post_type;

		if ( 'product' !== $post_type ) {
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

		wp_localize_script(
			'gr8r-woo-session-bundles-admin',
			'gr8r_woo_session_bundles_admin',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'gr8r_woo_session_bundles_admin_nonce' ),
			)
		);
	}

	/**
	 * AJAX handler for searching products
	 *
	 * @since 1.0.0
	 */
	public function ajax_search_products() {
		// Check nonce.
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'gr8r_woo_session_bundles_admin_nonce' ) ) {
			wp_die();
		}

		// Check permissions.
		if ( ! current_user_can( 'edit_products' ) ) {
			wp_die();
		}

		$search_term = sanitize_text_field( wp_unslash( $_POST['search_term'] ) );
		$products    = array();

		if ( ! empty( $search_term ) ) {
			$args = array(
				'post_type'      => 'product',
				'post_status'    => 'publish',
				'posts_per_page' => 20,
				's'              => $search_term,
				'meta_query'     => array(
					array(
						'key'     => '_product_type',
						'value'   => 'session_bundle',
						'compare' => '!=',
					),
				),
			);

			$query = new WP_Query( $args );

			if ( $query->have_posts() ) {
				while ( $query->have_posts() ) {
					$query->the_post();
					$product = wc_get_product( get_the_ID() );
					if ( $product ) {
						$products[] = array(
							'id'    => $product->get_id(),
							'text'  => $product->get_name(),
							'price' => $product->get_price(),
						);
					}
				}
			}
			wp_reset_postdata();
		}

		wp_send_json( $products );
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
			$summary = GR8R_Woo_Session_Bundle_Frontend::get_bundle_summary( $product_id );
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