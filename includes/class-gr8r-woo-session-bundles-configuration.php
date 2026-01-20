<?php
/**
 * Session Bundle Configuration Class
 *
 * @package Gr8r_Woo_Session_Bundles
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Session Bundle Configuration Class
 */
class GR8R_Woo_Session_Bundles_Configuration {

	/**
	 * The singleton instance of the class.
	 *
	 * @var GR8R_Woo_Session_Bundles_Configuration|null
	 */
	private static ?GR8R_Woo_Session_Bundles_Configuration $instance = null;

	/**
	 * Constructor. Private to enforce singleton pattern.
	 */
	private function __construct() {}

	/**
	 * Get the singleton instance of the class.
	 *
	 * @return GR8R_Woo_Session_Bundles_Configuration The singleton instance of the class.
	 */
	public static function get_instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Get the supported validity periods that will be stored in the database.
	 * Derived from the richer data supplied by {@see get_validity_periods()}.
	 *
	 * @return string[] The supported validity periods.
	 */
	public function get_supported_validity_periods(): array {
		return array_keys( $this->get_validity_periods() );
	}

	/**
	 * Get the supported validity periods.
	 *
	 * @return array {
	 *     The supported validity periods. Keys are the raw values that will be stored in the database.
	 *
	 *     @type string                $label        The label of the validity period.
	 *     @type callable(int): string $get_validity The function to get the validity expression for the validity period.
	 * }
	 */
	public function get_validity_periods(): array {
		return array(
			'day'   => [
				'label'        => __( 'Days', 'gr8r-woo-session-bundles' ),
				'get_validity' => function ( int $count ): string {
					return sprintf(
						_n( 'Valid for %d day', 'Valid for %d days', $count, 'gr8r-woo-session-bundles' ),
						$count
					);
				}
			],
			'week'  => [
				'label'        => __( 'Weeks', 'gr8r-woo-session-bundles' ),
				'get_validity' => function ( int $count ): string {
					return sprintf(
						_n( 'Valid for %d week', 'Valid for %d weeks', $count, 'gr8r-woo-session-bundles' ),
						$count
					);
				}
			],
			'month' => [
				'label'        => __( 'Months', 'gr8r-woo-session-bundles' ),
				'get_validity' => function ( int $count ): string {
					return sprintf(
						_n( 'Valid for %d month', 'Valid for %d months', $count, 'gr8r-woo-session-bundles' ),
						$count
					);
				}
			],
			'year'  => [
				'label'        => __( 'Years', 'gr8r-woo-session-bundles' ),
				'get_validity' => function ( int $count ): string {
					return sprintf(
						_n( 'Valid for %d year', 'Valid for %d years', $count, 'gr8r-woo-session-bundles' ),
						$count
					);
				}
			],
		);
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
}
