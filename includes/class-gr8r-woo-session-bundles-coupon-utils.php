<?php

/**
 * Coupon Utility Class
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Coupon Utility Class
 */
class GR8R_Woo_Session_Bundles_Coupon_Utils {

	/**
	 * The singleton instance of the coupon utility class.
	 */
	private static ?self $instance = null;

	/**
	 * Private constructor to require access via {@see get_instance()}.
	 */
	private function __construct() {}

	/**
	 * Get the singleton instance of the coupon utility class.
	 *
	 * @return GR8R_Woo_Session_Bundles_Coupon_Utils The singleton instance of the coupon utility class.
	 */
	public static function get_instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	* Helper function to get the next available coupon ID for the specified product ID and user ID.
	*
	* @param int $product_id The product ID to find a coupon for.
	* @param int $user_id    The user ID to find a coupon for.
	* @return int|null The next available coupon ID, or null if no coupon is available.
	*/
	public function get_next_available_coupon_id( int $product_id, int $user_id ): ?int {
		if ( 0 >= $product_id || 0 >= $user_id ) {
			return null;
	   	}
   
	   $valid_coupon_ids = get_posts(
		   array(
			   'post_type'      => 'shop_coupon',
			   'posts_per_page' => 1,
			   'post_status'    => 'publish',
			   'fields'         => 'ids',
			   'orderby'        => 'date_expires',
			   'order'          => 'ASC',
			   'meta_query'     => array(
				   'relation'       => 'AND',
				   'usage_clause'   => array(
					   'key'   => 'usage_count',
					   'value' => '0',
				   ),
				   // Note that date_expires is stored as a Unix timestamp,
				   // we need to use a numeric comparison.
				   'expiry_clause'  => array(
					   'key'     => 'date_expires',
					   'value'   => time(),
					   'compare' => '>',
					   'type'    => 'NUMERIC',
				   ),
				   'user_clause'    => array(
					   'key'   => '_gr8r_credit_user_id',
					   'value' => (string) $user_id,
				   ),
				   'product_clause' => array(
					   'key'   => 'product_ids',
					   'value' => (string) $product_id,
				   ),
			   ),
		   )
	   );
   
	   if ( empty( $valid_coupon_ids ) ) {
		   return null;
	   }
   
	   $coupon_id = reset( $valid_coupon_ids );
	   if ( ! $coupon_id ) {
		   return null;
	   }
   
	   return (int) $coupon_id;
   }

   /**
	 * Generate a coupon for a product.
	 *
	 * @param int                   $product_id        The bundled product ID.
	 * @param int                   $quantity          The quantity of the product.
	 * @param WC_Order_Item_Product $order_item        The order item.
	 * @param WC_Product            $purchased_product The purchased product that is causing us to generate a coupon/credits.
	 * @param int                   $user_id           The user ID to generate the coupon/credits for.
	 */
	public function generate_coupon_for_product_and_user( $product_id, $quantity, $order_item, $purchased_product, $user_id = null ): void {
		$product = wc_get_product( $product_id );
		if ( ! $product ) {
			return;
		}

		$order = $order_item->get_order();
		if ( ! $order ) {
			return;
		}

		if ( null === $user_id ) {
			$user_id = $order->get_customer_id();
		}

		$coupon_title_prefix = $this->get_coupon_title_prefix( $product_id, $user_id, $purchased_product );

		$email       = null;
		$customer_id = $order->get_customer_id();
		if ( $customer_id ) {
			$customer = new WC_Customer( $customer_id );
			$email = $customer->get_email();
		}

		if ( empty( $email ) ) {
			$email = $order->get_billing_email();
		}

		// TODO: Make the expiration time configurable within the bundle config.
		if ( 'subscription' !== $purchased_product->get_type() ) {
			$coupon_expiry_time = '+3 months';
		} else {
			$subscription_orders = wc_get_orders(
				[
					'customer'     => $customer_id ? $customer_id : $email,
					'product_id'   => $purchased_product->get_id(),
					'parent'       => $order->get_id(),
					'type'         => 'shop_subscription',
					'variation_id' => $purchased_product->get_variation_id(),
					'limit'        => 1,
					'orderby'      => 'date',
					'order'        => 'DESC',
				]
			);
			$coupon_expiry_time = null;
			if ( ! empty( $subscription_orders ) ) {
				$subscription_order = reset( $subscription_orders );
				if ( class_exists( 'WC_Subscription' ) && $subscription_order instanceof WC_Subscription ) {
					$coupon_expiry_time = $subscription_order->get_date( 'next_payment', 'site' );
				}
			}

			if ( empty( $coupon_expiry_time ) ) {
				$billing_period   = $purchased_product->get_billing_period();
				$billing_interval = $purchased_product->get_billing_interval();
				if ( $billing_period && $billing_interval ) {
					$coupon_expiry_time = "+{$billing_interval} {$billing_period}";
				} else {
					$coupon_expiry_time = '+1 month';
				}
			}
		}

		/**
		 * Filter the expiration time for the coupon/credit that we generate for a specific product.
		 * Note that we will set the expiry time to midnight local time at the end of the specified day
		 * after the filter.
		 *
		 * @param string     $coupon_expiry_time The expiration time expression, e.g. '+1 month', '+3 months'.
		 * @param WC_Product $purchased_product  The purchased product that includes a bundle.
		 * @param int        $product_id         The bundled product ID.
		 * @param int        $quantity           The quantity of the bundled product.
		 * @param int        $user_id            The user ID of the purchaser.
		 */
		$coupon_expiry_time = apply_filters( 'gr8r_woo_session_bundles_coupon_expiry_time', $coupon_expiry_time, $purchased_product, $product_id, $quantity, $user_id );

		// Create new timestamp using the expiration time, reset to midnight on that day, and then add one day.
		$coupon_expiry_date = new WC_DateTime( $coupon_expiry_time );
		$coupon_expiry_date->setTime( 0, 0, 0, 0 );
		$coupon_expiry_date->add( new DateInterval( 'P1D' ) );

		$coupon_created_time = new WC_DateTime();

		for ( $i = 0; $i < $quantity; $i++ ) {
			$coupon_code = $this->generate_coupon_code( $coupon_title_prefix );

			// TODO: Handle the case where we could not generate a valid coupon code.

			if ( null !== $coupon_code ) {
				$coupon = new WC_Coupon( $coupon_code );
				$coupon->set_discount_type( 'percent' );
				$coupon->set_amount( 100 );
				$coupon->set_product_ids( array( $product_id ) );
				$coupon->set_usage_limit( 1 );
				$coupon->set_usage_limit_per_user( 1 );
				$coupon->set_limit_usage_to_x_items( 1 );
				$coupon->set_date_expires( $coupon_expiry_date );
				$coupon->set_date_created( $coupon_created_time );
				if ( $customer_id ) {
					$coupon->update_meta_data( '_gr8r_credit_user_id', $customer_id );
				} elseif ( ! empty( $email ) ) {
					$coupon->set_email_restrictions( array( $email ) );
				}
				$coupon->update_meta_data( '_gr8r_credit_bundle_product_id', $purchased_product->get_id() );
				$coupon->update_meta_data( '_gr8r_credit_order_id', $order->get_id() );
				$coupon->update_meta_data( '_gr8r_credit_order_item_id', $order_item->get_id() );

				$save_result = $coupon->save();

				// TODO: Handle save errors.
			}
		}
	}

	/**
	 * Generate a unique coupon code.
	 *
	 * This function will generate a unique coupon code by prefixing a random integer with the provided prefix.
	 * It will then check if the generated coupon code already exists, and if it does, it will generate a new one.
	 * It will continue to do this until it finds a unique coupon code or has tried 20 times.
	 *
	 * @param string $coupon_title_prefix The prefix to use for the coupon code.
	 * @return string|null The unique coupon code, or null if no unique coupon code could be generated.
	 */
	protected function generate_coupon_code( string $coupon_title_prefix ): ?string {
		$count = 0;
		// TODO: This should be improved to use a more robust approach to avoid collisions and/or 
		// allow for some level of customisation.
		while ( $count < 20 ) {
			$count++;
			$random_hex = bin2hex( random_bytes( 5 ) );
			$random_hex = str_pad( $random_hex, 10, '0', STR_PAD_LEFT );

			$coupon_code = $coupon_title_prefix . $random_hex;

			if ( ! $this->coupon_exists( $coupon_code ) ) {
				return $coupon_code;
			}
		}

		return null;
	}

	/**
	 * Get the prefix that should be used for coupons.
	 *
	 * @param int        $product_id        The bundled product ID the coupon is for.
	 * @param int        $user_id           The user ID of the purchaser.
	 * @param WC_Product $purchased_product The purchased product that is causing us to generate a coupon/credits.
	 * @return string The coupon prefix.
	 */
	protected function get_coupon_title_prefix( int $product_id, int $user_id, $purchased_product ): string {
		$product_type = 'simple';
		if ( $purchased_product instanceof WC_Product ) {
			$product_type = $purchased_product->get_type();
		} else {
			$purchased_product = null;
		}

		if ( 'subscription' === $product_type ) {
			$coupon_prefix = 'Active-Subscription-';
		} else {
			$coupon_prefix = 'Active-Bundle-';
		}

		/**
		 * Generate a consistent, shopper-friendly coupon prefix.
		 *
		 * @param string          $coupon_prefix     The coupon prefix.
		 * @param string          $product_type      The product type of the bundle being purchased.
		 * @param int             $product_id        The product ID of the bundle the coupon is for.
		 * @param WC_Product|null $purchased_product The purchased product that is causing us to generate a coupon/credits.
		 * @param int             $user_id           The user ID the coupon should be assigned to.
		 */
		return apply_filters( 'gr8r_woo_session_bundles_coupon_prefix', $coupon_prefix, $product_type, $product_id, $purchased_product, $user_id );
	}

	/**
	 * Check if a coupon exists.
	 *
	 * @param string $coupon_code The coupon code.
	 * @return bool True if the coupon exists, false otherwise.
	 */
	protected function coupon_exists( string $coupon_code ): bool {
		$coupon = new WC_Coupon( $coupon_code );

		return (bool) $coupon->get_id() || $coupon->get_virtual();
	}
}

