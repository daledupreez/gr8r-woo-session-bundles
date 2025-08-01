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

	update_post_meta( $product_id, '_gr8r_bundled_sessions', $session_bundle_data );
}

/**
 * Get session bundle meta for a specified product.
 *
 * @param int $product_id The product ID.
 * @return int[] The session bundle data.
 */
function gr8r_session_bundles_get_product_bundle_meta( $product_id ): array {
	$stored_meta = get_post_meta( $product_id, '_gr8r_bundled_sessions', true );

	if ( ! is_array( $stored_meta ) ) {
		return array();
	}

	return gr8r_session_bundles_sanitize_bundle_data( $stored_meta );
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
 * Get bundle description
 *
 * @return string
 * @since 1.0.0
 */
function gr8r_session_bundles_get_bundle_description( $product_id ): string {
	$bundled_products = gr8r_session_bundles_get_product_bundle_meta( $product_id );
	if ( empty( $bundled_products ) ) {
		return '';
	}

	$description = '<div class="gr8r-session-bundle-contents">';
	$description .= '<h4>' . esc_html__( 'Included Sessions:', 'gr8r-woo-session-bundles' ) . '</h4>';
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
	$description .= '</div>';

	return $description;
}

function gr8r_debug( $data ) {
	error_log( 'gr8r_debug: ' . json_encode( $data ) . "\n", 3, WP_CONTENT_DIR . '/debug.log' );
}
