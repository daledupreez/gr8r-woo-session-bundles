/**
 * WooCommerce Session Bundle Frontend JavaScript
 */

jQuery(document).ready(function($) {
    'use strict';
    
    // Initialize Session Bundle functionality
    function initSessionBundle() {
        // Add bundle summary to cart items
        addBundleSummaryToCart();
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