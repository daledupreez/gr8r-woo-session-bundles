<?php

/**
 * Session Bundle Product Utilities
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Save session bundle meta for a specified product.
 *
 * @param int $product_id The product ID.
 * @param int[]|null $session_bundle_data The session bundle data. Specifying null or an empty array will delete the meta.
 */
function gr8r_session_bundles_save_product_bundle_meta( $product_id, ?array $session_bundle_data ): void {
	if ( ! $product_id ) {
		return;
	}

	$sanitized_data = null;
	if ( is_array( $session_bundle_data ) ) {
		$sanitized_data = gr8r_session_bundles_sanitize_bundle_data( $session_bundle_data );
	}

	if ( null === $sanitized_data || array() === $sanitized_data ) {
		delete_post_meta( $product_id, '_gr8r_bundled_sessions' );
		return;
	}

	update_post_meta( $product_id, '_gr8r_bundled_sessions', json_encode( $session_bundle_data ) );
}

/**
 * Get session bundle meta for a specified product.
 *
 * @param int $product_id The product ID.
 * @return int[] The session bundle data.
 */
function gr8r_session_bundles_get_product_bundle_meta( $product_id ): array {
	$stored_meta = get_post_meta( $product_id, '_gr8r_bundled_sessions', true );

	if ( empty( $stored_meta ) ) {
		return array();
	}

	$decoded_meta = json_decode( $stored_meta, true );

	if ( ! is_array( $decoded_meta ) ) {
		return array();
	}

	return gr8r_session_bundles_sanitize_bundle_data( $decoded_meta );
}

/**
 * Sanitize session bundle data.
 *
 * @param array $session_bundle_data The session bundle data to sanitize.
 * @return int[] The sanitized session bundle data.
 */
function gr8r_session_bundles_sanitize_bundle_data( array $session_bundle_data ): array {
	return array_filter(
		$session_bundle_data,
		function( $quantity, $product_id ) {
			return is_int( $quantity ) && $quantity > 0 && is_int( $product_id ) && $product_id > 0;
		},
		ARRAY_FILTER_USE_BOTH
	);
}

/**
 * Check if a product is a session bundle product.
 *
 * @param int $product_id The product ID.
 * @return bool True if the product is a session bundle product, false otherwise.
 */
function gr8r_session_bundles_is_bundle_product( $product_id ): bool {
	$is_session_bundle_meta = get_post_meta( $product_id, '_gr8r_is_session_bundle', true );

	return 'yes' === $is_session_bundle_meta;
}

/**
 * Save the is_session_bundle meta flag for a specified product.
 *
 * @param int $product_id The product ID.
 * @param bool $is_session_bundle True if the product is a session bundle product, false otherwise.
 */
function gr8r_session_bundles_save_product_is_bundle_meta( $product_id, bool $is_session_bundle ): void {
	if ( $is_session_bundle ) {
		update_post_meta( $product_id, '_gr8r_is_session_bundle', 'yes' );
	} else {
		delete_post_meta( $product_id, '_gr8r_is_session_bundle' );
	}
}

/**
 * Get bundle description for a product.
 *
 * @param int $product_id The product ID.
 * @return string The HTML description for the bundle.
 */
function gr8r_session_bundles_get_product_bundle_description( $product_id ): string {
	$bundled_products = gr8r_session_bundles_get_product_bundle_meta( $product_id );
	if ( empty( $bundled_products ) ) {
		return '';
	}

	return gr8r_session_bundles_get_bundle_description( $bundled_products, array(), gr8r_session_bundles_get_product_bundle_validity_meta( $product_id ) );
}

/**
 * Save validity period meta for a specified product.
 *
 * @param int         $product_id         The product ID.
 * @param int|null    $valid_period_count The validity period count. Null to delete.
 * @param string|null $valid_period       The validity period ('week', 'month', 'year'). Null to delete.
 */
function gr8r_session_bundles_save_product_validity_period_meta( $product_id, ?int $valid_period_count, ?string $valid_period ): void {
	if ( ! $product_id ) {
		return;
	}

	if ( null === $valid_period_count || null === $valid_period ) {
		delete_post_meta( $product_id, '_gr8r_valid_period_count' );
		delete_post_meta( $product_id, '_gr8r_valid_period' );
		return;
	}

	$allowed_periods = GR8R_Woo_Session_Bundles_Configuration::get_instance()->get_supported_validity_periods();
	if ( ! in_array( $valid_period, $allowed_periods, true ) ) {
		return;
	}

	// Validate count
	if ( $valid_period_count <= 0 ) {
		return;
	}

	update_post_meta( $product_id, '_gr8r_valid_period_count', $valid_period_count );
	update_post_meta( $product_id, '_gr8r_valid_period', $valid_period );
}

/**
 * Normalize validity meta to ensure consistent structure.
 *
 * Validates that the period is in the allowed list and count is a positive integer.
 *
 * @param array $validity_meta The validity meta array to normalize.
 * @return array{count: int|null, period: string|null} The normalized validity meta.
 */
function gr8r_session_bundles_normalize_validity_meta( array $validity_meta ): array {
	$normalized_validity_meta = array(
		'count'  => null,
		'period' => null,
	);

	if ( isset( $validity_meta['period'] ) ) {
		$allowed_periods = GR8R_Woo_Session_Bundles_Configuration::get_instance()->get_supported_validity_periods();
		if ( in_array( $validity_meta['period'], $allowed_periods, true ) ) {
			$normalized_validity_meta['period'] = $validity_meta['period'];
		}
	}

	if ( ! empty( $validity_meta['count'] ) && is_numeric( $validity_meta['count'] ) && (int) $validity_meta['count'] > 0 ) {
		$normalized_validity_meta['count'] = (int) $validity_meta['count'];
	}

	return $normalized_validity_meta;
}

/**
 * Get a human-readable description of the bundle validity period.
 *
 * @param array{count: int|null, period: string|null} $bundle_validity The bundle validity data.
 * @return string The human-readable validity description (e.g., "3 months").
 */
function gr8r_session_bundles_get_bundle_validity_description( array $bundle_validity ): string {
	$validity_periods = GR8R_Woo_Session_Bundles_Configuration::get_instance()->get_validity_periods();
	if ( isset( $validity_periods[ $bundle_validity['period'] ] ) ) {
		$configuration = $validity_periods[ $bundle_validity['period'] ];
		if ( isset( $configuration['plural'] ) && is_callable( $configuration['plural'] ) ) {
			return $configuration['plural']( $bundle_validity['count'] );
		}
	}

	return $bundle_validity['count'] . ' ' . $bundle_validity['period'] . ( $bundle_validity['count'] > 1 ? 's' : '' );
}

/**
 * Get validity period meta for a specified product.
 *
 * @param int $product_id The product ID.
 * @return array{count: int|null, period: string|null} The validity period data.
 */
function gr8r_session_bundles_get_product_bundle_validity_meta( $product_id ): array {
	$validity_meta = array(
		'count'  => get_post_meta( $product_id, '_gr8r_valid_period_count', true ),
		'period' => get_post_meta( $product_id, '_gr8r_valid_period', true ),
	);

	return gr8r_session_bundles_normalize_validity_meta( $validity_meta );
}

/**
 * Get validity period count for a specified product.
 *
 * @param int $product_id The product ID.
 * @return int|null The validity period count, or null if not set.
 */
function gr8r_session_bundles_get_product_validity_period_count( $product_id ): ?int {
	$meta = gr8r_session_bundles_get_product_bundle_validity_meta( $product_id );
	return $meta['count'];
}

/**
 * Get validity period for a specified product.
 *
 * @param int $product_id The product ID.
 * @return string|null The validity period ('week', 'month', 'year'), or null if not set.
 */
function gr8r_session_bundles_get_product_validity_period( $product_id ): ?string {
	$meta = gr8r_session_bundles_get_product_bundle_validity_meta( $product_id );
	return $meta['period'];
}

/**
 * Get bundle description for some bundled products.
 *
 * @param int[] $bundled_products The bundled products.
 * @param array $options {
 *     @type bool   $skip_header    Whether to skip the header. Default false.
 *     @type string $header_element The element to use for the header. Default 'h4'.
 *     @type string $wrapper_class  The class to use for the wrapper div. Default 'gr8r-session-bundle-contents'.
 * }
 * @param array $bundle_validity {
 *     @type int|null    $count  The validity period count.
 *     @type string|null $period The validity period ('week', 'month', 'year').
 * }
 * @return string The HTML description for the bundle.
 */
function gr8r_session_bundles_get_bundle_description( array $bundled_products, array $options = array(), array $bundle_validity = array() ): string {
	$defaults = array(
		'skip_header'    => false,
		'header_element' => 'h4',
		'wrapper_class'  => 'gr8r-session-bundle-contents',
	);

	$options = wp_parse_args( $options, $defaults );

	$description = '<div class="' . esc_attr( $options['wrapper_class'] ) . '">';
	if ( ! $options['skip_header'] ) {
		$description .= '<' . esc_html( $options['header_element'] ) . '>' . esc_html__( 'Included sessions:', 'gr8r-woo-session-bundles' ) . '</' . esc_html( $options['header_element'] ) . '>';
	}
	$description .= '<ul>';

	foreach ( $bundled_products as $product_id => $quantity ) {
		$product = wc_get_product( $product_id );
		if ( $product ) {
			$description .= sprintf(
				'<li>%1$s × %2$s - %3$s %4$s</li>',
				esc_html( $quantity ),
				esc_html( $product->get_name() ),
				esc_html__( 'each valued at', 'gr8r-woo-session-bundles' ),
				$product->get_price_html()
			);
		}
	}

	$description .= '</ul>';

	if ( isset( $bundle_validity['count'] ) && isset( $bundle_validity['period'] ) && null !== $bundle_validity['count'] && null !== $bundle_validity['period'] ) {
		$validity_expression = gr8r_session_bundles_get_bundle_validity_description( $bundle_validity );
		$description .= '<div class="gr8r-session-bundle-validity">';
		$description .= esc_html__( 'Valid for', 'gr8r-woo-session-bundles' ) . ' ' . esc_html( $validity_expression );
		$description .= '</div>';
	}

	$description .= '</div>';

	return $description;
}
