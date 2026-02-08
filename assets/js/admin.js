/**
 * WooCommerce Session Bundle Admin JavaScript
 */

jQuery(document).ready(function($) {
	'use strict';

	var productDetails = {};

	var bundledSessions = {};

	var usePreloadedProducts = gr8r_woo_session_bundles_admin.use_preloaded_products ?? false;

	function getBundledProductIds() {
		return Object.keys( bundledSessions );
	}

	function getBundledSessions() {
		return bundledSessions;
	}

	function updateBundledSessions( delta ) {
		Object.entries( delta ).forEach( ( [ productId, quantity ] ) => {
			const quantityInt = parseInt( quantity, 10 );
			if ( quantityInt > 0 ) {
				bundledSessions[ productId ] = quantityInt;
			} else {
				delete bundledSessions[ productId ];
			}
		} );

		const jsonInput = $( '#gr8r-bundled-sessions' );
		jsonInput.val( JSON.stringify( bundledSessions ) );
	}

	function hydrateProductDetails() {
		// Hydrate from bundled product details (products already in the bundle).
		const bundledProductDetails = gr8r_woo_session_bundles_admin.bundled_product_details ?? {};

		Object.values( bundledProductDetails ).forEach( ( productData ) => {
			productDetails[ productData.id ] = productData;
		} );

		// Hydrate from available products (for pre-loaded mode).
		const availableProducts = gr8r_woo_session_bundles_admin.available_products ?? [];
		availableProducts.forEach( ( productData ) => {
			if ( productData?.id && ! productDetails[ productData.id ] ) {
				productDetails[ productData.id ] = productData;
			}
		} );
	}

	function hydrateBundledSessions() {
		const jsonInput = $( '#gr8r-bundled-sessions' );
		const jsonValue = jsonInput.length > 0 ? jsonInput.val() : '{}';
		bundledSessions = JSON.parse( jsonValue );
	}

	// Initialize product selector based on mode.
	function initProductSelector() {
		const commonSelectConfig = {
			allowClear: true,
			escapeMarkup: function( m ) {
				return m;
			},
			templateResult: formatProductOption,
			templateSelection: formatProductSelection,
		};

		const selectConfig = usePreloadedProducts ? {
			...commonSelectConfig,
			data: getSelectablePreloadedProductsData(),
			placeholder: gr8r_woo_session_bundles_admin.strings.select_products,
			matcher: customProductMatcher,
			language: {
				noResults: function() {
					return gr8r_woo_session_bundles_admin.strings.no_products_found || 'No results found';
				}
			}
		} : {
			...commonSelectConfig,
			minimumInputLength: 3,
			placeholder: gr8r_woo_session_bundles_admin.strings.search_products,
			ajax: {
				url: gr8r_woo_session_bundles_admin.ajax_url,
				dataType: 'json',
				delay: 250,
				cache: true,
				data: function( params ) {
					return {
						action: 'gr8r_woo_session_bundles_search_products',
						nonce: gr8r_woo_session_bundles_admin.nonce,
						search: params.term,
						page: params.page || 1,
						exclude_ids: getBundledProductIds(),
					};
				},
				processResults: function( data, params ) {
					const results = Array.isArray( data?.products ) ? data.products : [];

					// Populate the product details from the results.
					results.forEach( ( product ) => {
						if ( product?.id && ! productDetails[ product.id ] ) {
							productDetails[ product.id ] = product;
						}
					} );

					return { results };
				},
			},
		};

		$( '#gr8r-woo-session-bundles-add-bundled-product-selector' ).select2( selectConfig );
	}

	// Get selectable products data for Select2 (excludes already bundled products).
	function getSelectablePreloadedProductsData() {
		var allProducts = gr8r_woo_session_bundles_admin.available_products || [];
		var bundledIds = getBundledProductIds();

		var availableProducts = allProducts.filter( function( product ) {
			return bundledIds.indexOf( String( product.id ) ) === -1;
		} );

		return formatProductsForSelect2( availableProducts );
	}

	// Format products array for Select2 data format.
	function formatProductsForSelect2( products ) {
		return products.map( function( product ) {
			return {
				id: product.id,
				text: product.name + ' (#' + product.id + ')',
				name: product.name,
				price: product.price,
				priceHTML: product.priceHTML,
				url: product.url,
				edit_url: product.edit_url
			};
		} );
	}

	// Custom matcher for filtering pre-loaded products by name or ID.
	function customProductMatcher( params, data ) {
		// If there is no search term, return all data.
		if ( typeof params.term !== 'string' ) {
			return data;
		}

		const term = params.term.trim().toLowerCase();
		if ( term === '' ) {
			return data;
		}

		// Skip if no text to search.
		if ( typeof data.text !== 'string' ) {
			return null;
		}

		// Match against text, which includes both name and ID.
		if ( data.text.toLowerCase().indexOf( term ) > -1 ) {
			return data;
		}

		return null;
	}

	// Refresh the selectable products in the dropdown (for pre-loaded mode).
	function refreshSelectableProducts() {
		if ( ! usePreloadedProducts ) {
			return;
		}

		var $selector = $( '#gr8r-woo-session-bundles-add-bundled-product-selector' );

		// Clear current selection.
		$selector.val( null ).trigger( 'change' );

		// Destroy and reinitialize with updated data.
		$selector.empty();
		$selector.select2( 'destroy' );
		initProductSelector();
	}

	// Format product option for dropdown.
	function formatProductOption( product ) {
		if ( product.loading ) {
			return product.text;
		}

		var priceHTML = product.priceHTML || '';
		var $option = $(
			'<div>' + product.name + ' (#' + product.id + ')' + ( priceHTML ? ' — ' + priceHTML : '' ) + '</div>'
		);

		return $option;
	}

	function formatProductSelection( option ) {
		if ( option.text ) {
			return option.text;
		}

		return getBundledProductOneLine( option );
	}

	function getBundledProductOneLine( product ) {
		return `${product.name} (#${product.id})`;
	}

	// Add product to bundle.
	function addProductToBundle() {
		var $selector = $( '#gr8r-woo-session-bundles-add-bundled-product-selector' );
		const productIdToAdd = $selector.val();
		const productData = $selector.select2( 'data' )[0];

		if ( ! productIdToAdd || ! productData ) {
			return;
		}

		// Ensure productDetails has the full data.
		if ( ! productDetails[ productIdToAdd ] ) {
			productDetails[ productIdToAdd ] = {
				id: productData.id,
				name: productData.name,
				price: productData.price,
				priceHTML: productData.priceHTML,
				url: productData.url,
				edit_url: productData.edit_url
			};
		}

		const bundledElementId = 'gr8r-woo-session-bundles-bundled-session-product-' + productIdToAdd;
		const bundledElement = $( '#' + bundledElementId );

		if ( bundledElement.length > 0 ) {
			// If we already have the product in the bundle, focus on the quantity input.
			bundledElement.find( 'input.gr8r-woo-session-bundles-bundled-session-product-quantity-input' ).focus();
			return;
		}

		updateBundledSessions( { [ productIdToAdd ]: 1 } );

		renderBundledSessions();

		// Clear selection and refresh dropdown for pre-loaded mode.
		$selector.val( null ).trigger( 'change' );
		refreshSelectableProducts();
	}

	// Remove product from bundle.
	function removeProductFromBundle() {
		const productId = $(this).data( 'product-id' );

		if ( ! productId ) {
			return;
		}

		updateBundledSessions( { [ productId ]: 0 } );
		$( '#gr8r-woo-session-bundles-bundled-session-product-' + productId ).remove();

		// Update bundle total and refresh dropdown for pre-loaded mode.
		updateBundleEffectiveTotal();
		refreshSelectableProducts();
	}

	function updateBundledSessionQuantity() {
		const productId = $( this ).data( 'product-id' );
		const quantity = $( this ).val();

		if ( productId && quantity > 0 ) {
			updateBundledSessions( { [ productId ]: quantity } );
			updateBundleEffectiveTotal();
		}
	}

   // Calculate and update effective bundle value.
	function updateBundleEffectiveTotal() {
		const bundledSessions = getBundledSessions();
		const bundledSessionsValue = Object.entries( bundledSessions ).reduce(
			( total, [ productId, quantity ] ) => {
				const productData = productDetails[ productId ];
				if ( productData && quantity > 0 ) {
					return total + ( productData.price * quantity );
				}

				return total;
			},
			0
		);

		$( '.gr8r-woo-session-bundles-bundled-session-value-total' ).text( formatPrice( bundledSessionsValue ) );
	}

	function _sprintf( format, ...args ) {
		var result = String( format );

		var replacement = result.match( /%(\d+)\$(s|d)/ );
		while ( replacement ) {
			const argIndex = replacement[1];
			const replacementFormat = replacement[2];
			const arg = args[ argIndex - 1 ] ?? '';
			const formattedArg = _formatArg( arg, replacementFormat );
			result = result.replace( replacement[0], formattedArg );
			replacement = result.match( /%(\d+)\$(s|d)/ );
		}

		return result;
	}

	function _formatArg( value, format ) {
		if ( format === 'd' ) {
			return parseInt( value, 10 );
		}

		if ( format === 'f' ) {
			return parseFloat( value );
		}

		return String( value );
	}

	// Format price.
	function formatPrice(price) {
		const storeCurrency = window?.wcSettings?.currency;
		if ( ! storeCurrency ) {
			return price.toFixed(2);
		}

		const priceValue = storeCurrency.precision > 0 ? price.toFixed( storeCurrency.precision ) : price;
		return _sprintf( storeCurrency.priceFormat, storeCurrency.symbol, priceValue );
	}

	function buildBundledSession( productId, productData, quantity = 1 ) {
		const bundledElementId = `gr8r-woo-session-bundles-bundled-session-product-${ productId }`;

		return $(
			`<tr id="${ bundledElementId }" class="gr8r-woo-session-bundles-bundled-session" data-product-id="${ productId }">` +
				`<td class="gr8r-woo-session-bundles-bundled-session-product-info">${ getBundledProductOneLine( productData ) }</td>` +
				`<td class="gr8r-woo-session-bundles-bundled-session-product-price">${ productData.priceHTML }</td>` +
				'<td class="gr8r-woo-session-bundles-bundled-session-product-quantity">' +
					`<input type="number" class="gr8r-woo-session-bundles-bundled-session-product-quantity-input" ` +
						`value="${ quantity }" min="1" max="25" data-product-id="${ productId }" />` +
				'</td>' +
				'<td>' +
					'<div class="gr8r-woo-session-bundles-bundled-session-product-actions">' +
						`<button type="button" class="gr8r-woo-session-bundles-remove-bundle-product button-secondary" data-product-id="${ productId }">${ gr8r_woo_session_bundles_admin.strings.remove_product }</button>` +
						`<a href="${ productData.url }" target="_blank">${ gr8r_woo_session_bundles_admin.strings.view_product }</a>` +
						`<a href="${ productData.edit_url }" target="_blank">${ gr8r_woo_session_bundles_admin.strings.edit_product }</a>` +
					'</div>' +
				'</td>' +
			'</tr>'
		);
	}

	function renderBundledSessions() {
		const bundledSessions = getBundledSessions();
		const bundledSessionsTable = $( '.gr8r-woo-session-bundles-bundled-session-list-table' );
		const bundledSessionsTableBody = $( '.gr8r-woo-session-bundles-bundled-session-list-body' );
		bundledSessionsTableBody.empty();

		const bundledSessionEntries = Object.entries( bundledSessions );

		// Ensure we show or hide the no bundled sessions message.
		if ( bundledSessionEntries.length === 0 ) {
			$( '.gr8r-woo-session-bundles-no-bundled-sessions' ).show();
			bundledSessionsTable.hide();
		} else {
			$( '.gr8r-woo-session-bundles-no-bundled-sessions' ).hide();
			bundledSessionsTable.show();
		}

		bundledSessionEntries.forEach( ( [ productId, quantity ] ) => {
			const productData = productDetails[ productId ];
			if ( productData ) {
				const bundledSession = buildBundledSession( productId, productData, quantity );
				bundledSessionsTableBody.append( bundledSession );
			}
		} );

		updateBundleEffectiveTotal();

		$( '.gr8r-woo-session-bundles-remove-bundle-product' ).on( 'click', removeProductFromBundle );
		$( '.gr8r-woo-session-bundles-bundled-session-product-quantity-input' ).on( 'change', updateBundledSessionQuantity );
	}

	function toggleSessionBundleOptions() {
		const isSessionBundle = $( '#_gr8r_is_session_bundle' ).prop( 'checked' );
		if ( isSessionBundle ) {
			$( '.show_if_bundled_sessions' ).show();
			toggleValidityPeriodFields();
		} else {
			$( '.show_if_bundled_sessions' ).hide();
			$( '.gr8r-woo-session-bundles-validity-period' ).hide();
		}
	}

	function toggleValidityPeriodFields() {
		const productType = $( 'select#product-type' ).val() || $( 'select[name="product_type"]' ).val();
		const isSubscription = 'subscription' === productType;
		const isSessionBundle = $( '#_gr8r_is_session_bundle' ).prop( 'checked' );

		if ( isSessionBundle && ! isSubscription ) {
			$( '.gr8r-woo-session-bundles-validity-period' ).show();
		} else {
			$( '.gr8r-woo-session-bundles-validity-period' ).hide();
		}
	}

	hydrateProductDetails();
	hydrateBundledSessions();

	$( '#gr8r-woo-session-bundles-add-bundle-product' ).on( 'click', addProductToBundle );

	$( '#_gr8r_is_session_bundle' ).on( 'change', toggleSessionBundleOptions );

	// Watch for product type changes.
	$( 'select#product-type, select[name="product_type"]' ).on( 'change', toggleValidityPeriodFields );

	initProductSelector();

	renderBundledSessions();

	toggleSessionBundleOptions();
} );
