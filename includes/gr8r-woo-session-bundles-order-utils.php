<?php

/**
 * Session Bundle Order Utilities
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function gr8r_session_bundles_save_order_item_bundle_meta( $order_item_id, ?array $session_bundle_data ): void {
	if ( ! $order_item_id ) {
		return;
	}

	$sanitized_data = null;
	if ( is_array( $session_bundle_data ) ) {
		$sanitized_data = gr8r_session_bundles_sanitize_bundle_data( $session_bundle_data );
	}

	if ( null === $sanitized_data || array() === $sanitized_data ) {
		wc_delete_order_item_meta( $order_item_id, '_gr8r_bundled_products' );
		return;
	}

	wc_update_order_item_meta( $order_item_id, '_gr8r_bundled_products', json_encode( $sanitized_data ), true );
}

function gr8r_session_bundles_get_order_item_bundle_meta( $order_item_id ): array {
	$stored_meta = wc_get_order_item_meta( $order_item_id, '_gr8r_bundled_products', true );

	if ( empty( $stored_meta ) ) {
		return array();
	}

	$decoded_meta = json_decode( $stored_meta, true );

	if ( ! is_array( $decoded_meta ) ) {
		return array();
	}

	return gr8r_session_bundles_sanitize_bundle_data( $decoded_meta );
}

function gr8r_session_bundles_is_bundle_order_item( $item ): bool {
	if ( ! $item || ! $item instanceof WC_Order_Item_Product ) {
		return false;
	}

	return 'yes' === wc_get_order_item_meta( $item->get_id(), '_gr8r_is_bundle', true );
}

function gr8r_session_bundles_save_order_item_is_bundle_meta( $order_item_id, bool $is_session_bundle ): void {
	if ( $is_session_bundle ) {
		wc_update_order_item_meta( $order_item_id, '_gr8r_is_bundle', 'yes', true );
	} else {
		wc_delete_order_item_meta( $order_item_id, '_gr8r_is_bundle' );
	}
}
