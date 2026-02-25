# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

GR8R Session Bundles is a WordPress plugin for WooCommerce that enables creating bundles of bookable sessions. Customers purchase bundles and receive coupons (credits) that auto-apply when booking individual sessions later. The plugin is aimed at fitness/coaching businesses using WooCommerce Bookings with explicit support for WooCommerce Subscriptions and Dokan.

## Architecture

**Singleton Pattern Classes:**
- `GR8R_Woo_Session_Bundles` (main plugin) - Entry point, defines constants, registers hooks
- `GR8R_Woo_Session_Bundles_Admin` - Product editor UI, AJAX handlers, Dokan integration
- `GR8R_Woo_Session_Bundles_Frontend` - Cart/checkout integration, order display, coupon auto-application
- `GR8R_Woo_Session_Bundles_Configuration` - Centralized config for product types and validity periods
- `GR8R_Woo_Session_Bundles_Coupon_Utils` - Coupon generation and management

**Static Utility Classes:**
- `GR8R_Woo_Session_Bundles_Logger` - Wraps `wc_get_logger()`; all plugin logging goes through here. Logs appear under WooCommerce > Status > Logs (source: `gr8r-session-bundles`). `debug/info/notice` are suppressed unless the logging level filter is set to `'debug'`; `warning` and above always log.

**Utility Files (procedural):**
- `gr8r-woo-session-bundles-product-utils.php` - Product meta CRUD (`_gr8r_*` meta keys)
- `gr8r-woo-session-bundles-order-utils.php` - Order item meta CRUD

**Data Flow:**
1. Admin creates bundle product with bundled products via Select2 UI
2. Customer purchases bundle -> order item stores bundle snapshot
3. Payment completes -> coupons generated via `Coupon_Utils`
4. Customer visits/carts eligible product -> coupons auto-applied via `Frontend`

## Key Extensibility Hooks

Filters for customization:
- `gr8r_woo_session_bundles_supported_product_types` - Which product types can be bundles (default: simple, subscription)
- `gr8r_woo_session_bundles_allowed_bundled_product_types` - What can go in bundles (default: booking)
- `gr8r_woo_session_bundles_coupon_prefix` - Coupon code prefix
- `gr8r_woo_session_bundles_coupon_expiry_time` - Coupon expiry calculation
- `gr8r_woo_session_bundles_apply_credits_to_cart` - Control auto-apply behavior
- `gr8r_bundled_product_notice` - Frontend notice text
- `gr8r_woo_session_bundles_logging_level` - Minimum log level string (`'debug'` or `'warning'`). Defaults to `'warning'`, which suppresses debug/info/notice entries.

## Development

**Requirements:**
- WordPress 6.6+
- WooCommerce 9.8+
- PHP 7.4+

**Optional integrations:** Dokan, WooCommerce Bookings, WooCommerce Subscriptions

**No build process** - Pure PHP/JS/CSS, no Composer or npm dependencies.

**Testing:** No automated tests. Manual testing required with a WordPress + WooCommerce environment.

**Security patterns used:**
- Nonce verification on all AJAX handlers
- `wc_clean()`, `sanitize_text_field()`, `absint()` for input
- `esc_html()`, `esc_attr()` for output
- `current_user_can()` capability checks
- Dokan vendor ownership validation

## Meta Keys

Product meta (stored on bundle products):
- `_gr8r_is_session_bundle` - Boolean flag
- `_gr8r_bundled_products` - Array of product_id => quantity
- `_gr8r_bundle_validity_*` - Expiration settings

Order item meta (snapshot at purchase time):
- `_gr8r_bundled_products` - Copy of bundle contents
- `_gr8r_bundle_validity_*` - Copy of validity settings
