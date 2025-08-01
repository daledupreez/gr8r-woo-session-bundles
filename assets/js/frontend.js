/**
 * WooCommerce Session Bundle Frontend JavaScript
 */

jQuery(document).ready(function($) {
    'use strict';
    
    // Initialize Session Bundle functionality
    function initSessionBundle() {
        // Add bundle summary to cart items
        addBundleSummaryToCart();
        
        // Handle quantity changes for bundle products
        //handleBundleQuantityChanges();
        
        // Add bundle info to product variations if needed
        //handleBundleVariations();
    }
    
    // Add bundle summary to cart items
    function addBundleSummaryToCart() {
        $('.cart_item, .order_item').each(function() {
            var $item = $(this);
            var $productName = $item.find('.product-name a, .product-name');
            var productId = $productName.attr('href') ? 
                $productName.attr('href').match(/product_id=(\d+)/) : null;
            
            if (productId && productId[1]) {
                // Check if this is a session bundle product
                $.ajax({
                    url: wc_session_bundle.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'gr8r_woo_session_bundle_get_summary',
                        nonce: gr8r_woo_session_bundle.nonce,
                        product_id: productId[1]
                    },
                    success: function(response) {
                        if (response.success && response.data.summary) {
                            $item.find('.product-name').append(response.data.summary);
                        }
                    }
                });
            }
        });
    }
    
    // Handle quantity changes for bundle products
    /*
    function handleBundleQuantityChanges() {
        $(document).on('change', '.qty', function() {
            var $input = $(this);
            var $form = $input.closest('form');
            var $product = $form.find('input[name="add-to-cart"]');
            
            if ($product.length && $product.val()) {
                // Check if this is a session bundle product
                $.ajax({
                    url: wc_session_bundle.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'gr8r_woo_session_bundle_check_stock',
                        nonce: gr8r_woo_session_bundle.nonce,
                        product_id: $product.val(),
                        quantity: $input.val()
                    },
                    success: function(response) {
                        if (response.success) {
                            if (!response.data.in_stock) {
                                showStockWarning(response.data.message);
                            } else {
                                hideStockWarning();
                            }
                        }
                    }
                });
            }
        });
    }
    */
    
    // Handle bundle variations
    /*
    function handleBundleVariations() {
        // Listen for variation changes
        $(document).on('found_variation', 'form.variations_form', function(event, variation) {
            if (variation.product_type === 'session_bundle') {
                updateBundleInfo(variation);
            }
        });
    }
    */
    
    // Update bundle info when variation changes
    /*
    function updateBundleInfo(variation) {
        if (variation.bundle_summary) {
            $('.session-bundle-contents-summary').html(variation.bundle_summary);
        }
    }
    */
    
    // Show stock warning
    /*
    function showStockWarning(message) {
        var $warning = $('.session-bundle-stock-warning');
        
        if ($warning.length === 0) {
            $warning = $('<div class="session-bundle-stock-warning woocommerce-error" role="alert"></div>');
            $('.woocommerce-error, .woocommerce-message').after($warning);
        }
        
        $warning.html(message).show();
    }
    */
    
    // Hide stock warning
    /*
    function hideStockWarning() {
        $('.session-bundle-stock-warning').hide();
    }
    */

    // Add bundle info to product data
	/*
    function addBundleInfoToProductData() {
        $('.single-product').each(function() {
            var $product = $(this);
            var productId = $product.find('input[name="add-to-cart"]').val();
            
            if (productId) {
                $.ajax({
                    url: wc_session_bundle.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'gr8r_woo_session_bundle_get_product_data',
                        nonce: gr8r_woo_session_bundle.nonce,
                        product_id: productId
                    },
                    success: function(response) {
                        if (response.success && response.data.is_bundle) {
                            // Add bundle-specific data to product
                            $product.addClass('session-bundle-product');
                            
                            // Update price if needed
                            if (response.data.price_html) {
                                $('.price .amount').html(response.data.price_html);
                            }
                            
                            // Add bundle indicator
                            if (!$('.session-bundle-indicator').length) {
                                $('.product_title').after('<span class="session-bundle-indicator">Bundle Product</span>');
                            }
                        }
                    }
                });
            }
        });
    }
    */
    
    // Initialize tooltips for bundle information
    function initBundleTooltips() {
        $('.gr8r-session-bundle-description, .session-bundle-contents-summary').each(function() {
            var $element = $(this);
            
            // Add tooltip functionality if needed
            if ($element.find('li').length > 3) {
                $element.addClass('bundle-collapsible');
                
                var $toggle = $('<button class="bundle-toggle" type="button">Show More</button>');
                $element.append($toggle);
                
                $element.find('li:gt(2)').hide();
                
                $toggle.on('click', function() {
                    var $hidden = $element.find('li:hidden');
                    if ($hidden.length) {
                        $hidden.show();
                        $toggle.text('Show Less');
                    } else {
                        $element.find('li:gt(2)').hide();
                        $toggle.text('Show More');
                    }
                });
            }
        });
    }
    
    // Handle bundle product quick view
    function handleBundleQuickView() {
        $(document).on('click', '.quick-view-button', function(e) {
            var $button = $(this);
            var productId = $button.data('product-id');
            
            if (productId) {
                $.ajax({
                    url: wc_session_bundle.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'gr8r_woo_session_bundle_quick_view',
                        nonce: gr8r_woo_session_bundle.nonce,
                        product_id: productId
                    },
                    success: function(response) {
                        if (response.success) {
                            // Display quick view modal with bundle info
                            showQuickViewModal(response.data);
                        }
                    }
                });
            }
        });
    }
    
    // Show quick view modal
    function showQuickViewModal(data) {
        var modal = $('<div class="session-bundle-quick-view-modal"></div>');
        modal.html(data.html);
        
        $('body').append(modal);
        
        modal.on('click', '.close-modal', function() {
            modal.remove();
        });
        
        modal.on('click', function(e) {
            if (e.target === modal[0]) {
                modal.remove();
            }
        });
    }
    
    // Initialize when document is ready
    if (typeof gr8r_woo_session_bundle !== 'undefined') {
        initSessionBundle();
        //addBundleInfoToProductData();
        initBundleTooltips();
        handleBundleQuickView();
    }
    
    // Handle AJAX cart updates
    $(document.body).on('added_to_cart', function(event, fragments, cart_hash, button) {
        // Reinitialize bundle functionality after cart update
        setTimeout(function() {
            addBundleSummaryToCart();
        }, 100);
    });
    
    // Handle page load for cart/checkout pages
    if ($('.woocommerce-cart, .woocommerce-checkout').length) {
        addBundleSummaryToCart();
    }
}); 