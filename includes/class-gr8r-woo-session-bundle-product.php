<?php
/**
 * Session Bundle Product Class
 *
 * @package Gr8r_Woo_Session_Bundles
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Session Bundle Product Class
 *
 * @since 1.0.0
 */
class GR8R_Woo_Session_Bundle_Product extends WC_Product {

	/**
	 * Initialize Session Bundle product
	 *
	 * @param mixed $product Product object or ID.
	 * @since 1.0.0
	 */
	public function __construct( $product = 0 ) {
		$this->product_type = 'session_bundle';
		parent::__construct( $product );
	}

	/**
	 * Get bundled products
	 *
	 * @return array
	 * @since 1.0.0
	 */
	public function get_bundled_products() {
		$bundled_products = get_post_meta( $this->get_id(), '_bundled_products', true );
		return is_array( $bundled_products ) ? $bundled_products : array();
	}

	/**
	 * Set bundled products
	 *
	 * @param array $bundled_products Array of product IDs and quantities.
	 * @since 1.0.0
	 */
	public function set_bundled_products( $bundled_products ) {
		update_post_meta( $this->get_id(), '_bundled_products', $bundled_products );
	}

	/**
	 * Get bundled product quantity
	 *
	 * @param int $product_id Product ID.
	 * @return int
	 * @since 1.0.0
	 */
	public function get_bundled_product_quantity( $product_id ) {
		$bundled_products = $this->get_bundled_products();
		return isset( $bundled_products[ $product_id ] ) ? intval( $bundled_products[ $product_id ] ) : 0;
	}

	/**
	 * Set bundled product quantity
	 *
	 * @param int $product_id Product ID.
	 * @param int $quantity Quantity.
	 * @since 1.0.0
	 */
	public function set_bundled_product_quantity( $product_id, $quantity ) {
		$bundled_products = $this->get_bundled_products();
		$bundled_products[ $product_id ] = intval( $quantity );
		$this->set_bundled_products( $bundled_products );
	}

	/**
	 * Get bundle description
	 *
	 * @return string
	 * @since 1.0.0
	 */
	public function get_bundle_description() {
		$bundled_products = $this->get_bundled_products();
		$description      = '';

		if ( ! empty( $bundled_products ) ) {
			$description .= '<div class="session-bundle-contents">';
			$description .= '<h4>' . esc_html__( 'Bundle Contents:', 'gr8r-woo-session-bundles' ) . '</h4>';
			$description .= '<ul>';

			foreach ( $bundled_products as $product_id => $quantity ) {
				$product = wc_get_product( $product_id );
				if ( $product ) {
					$description .= sprintf(
						'<li>%s × %s</li>',
						esc_html( $product->get_name() ),
						esc_html( $quantity )
					);
				}
			}

			$description .= '</ul>';
			$description .= '</div>';
		}

		return $description;
	}

	/**
	 * Get bundle price
	 *
	 * @return float
	 * @since 1.0.0
	 */
	public function get_bundle_price() {
		$bundled_products = $this->get_bundled_products();
		$total_price      = 0;

		foreach ( $bundled_products as $product_id => $quantity ) {
			$product = wc_get_product( $product_id );
			if ( $product ) {
				$total_price += $product->get_price() * $quantity;
			}
		}

		return $total_price;
	}

	/**
	 * Check if product is purchasable
	 *
	 * @return bool
	 * @since 1.0.0
	 */
	public function is_purchasable() {
		$bundled_products = $this->get_bundled_products();

		if ( empty( $bundled_products ) ) {
			return false;
		}

		// Check if all bundled products are purchasable.
		foreach ( $bundled_products as $product_id => $quantity ) {
			$product = wc_get_product( $product_id );
			if ( ! $product || ! $product->is_purchasable() ) {
				return false;
			}
		}

		return parent::is_purchasable();
	}

	/**
	 * Check if product is in stock
	 *
	 * @return bool
	 * @since 1.0.0
	 */
	public function is_in_stock() {
		$bundled_products = $this->get_bundled_products();

		if ( empty( $bundled_products ) ) {
			return false;
		}

		// Check if all bundled products are in stock.
		foreach ( $bundled_products as $product_id => $quantity ) {
			$product = wc_get_product( $product_id );
			if ( ! $product || ! $product->is_in_stock() || $product->get_stock_quantity() < $quantity ) {
				return false;
			}
		}

		return parent::is_in_stock();
	}

	/**
	 * Get stock quantity
	 *
	 * @return int
	 * @since 1.0.0
	 */
	public function get_stock_quantity() {
		$bundled_products = $this->get_bundled_products();

		if ( empty( $bundled_products ) ) {
			return 0;
		}

		$min_stock = null;

		foreach ( $bundled_products as $product_id => $quantity ) {
			$product = wc_get_product( $product_id );
			if ( $product && $product->managing_stock() ) {
				$available_stock = intval( $product->get_stock_quantity() / $quantity );
				if ( null === $min_stock || $available_stock < $min_stock ) {
					$min_stock = $available_stock;
				}
			}
		}

		return null !== $min_stock ? $min_stock : parent::get_stock_quantity();
	}
} 