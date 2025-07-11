/**
 * WooCommerce Session Bundle Admin JavaScript
 */

jQuery(document).ready(function($) {
    'use strict';
    
    // Initialize product selector
    function initProductSelector() {
        $('#session-bundle-product-selector').select2({
            ajax: {
                url: wc_session_bundle.ajax_url,
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return {
                        action: 'wc_session_bundle_search_products',
                        nonce: wc_session_bundle.nonce,
                        search: params.term,
                        page: params.page || 1
                    };
                },
                processResults: function(data, params) {
                    params.page = params.page || 1;
                    
                    return {
                        results: data.data,
                        pagination: {
                            more: data.more
                        }
                    };
                },
                cache: true
            },
            placeholder: wc_session_bundle.strings.select_products,
            minimumInputLength: 2,
            templateResult: formatProductOption,
            templateSelection: formatProductSelection
        });
    }
    
    // Format product option for dropdown
    function formatProductOption(product) {
        if (product.loading) {
            return product.text;
        }
        
        if (!product.id) {
            return product.text;
        }
        
        var $option = $(
            '<div class="product-option">' +
                '<div class="product-name">' + product.text + '</div>' +
                '<div class="product-price">' + product.price + '</div>' +
            '</div>'
        );
        
        return $option;
    }
    
    // Format selected product
    function formatProductSelection(product) {
        return product.text || product.id;
    }
    
    // Add product to bundle
    function addProductToBundle() {
        var $selector = $('#session-bundle-product-selector');
        var selectedProduct = $selector.select2('data')[0];
        
        if (!selectedProduct || !selectedProduct.id) {
            alert('Please select a product first.');
            return;
        }
        
        // Check if product is already in bundle
        if ($('.session-bundle-product-item[data-product-id="' + selectedProduct.id + '"]').length > 0) {
            alert('This product is already in the bundle.');
            return;
        }
        
        // Create product item HTML
        var $productItem = $(
            '<div class="session-bundle-product-item" data-product-id="' + selectedProduct.id + '">' +
                '<div class="product-info">' +
                    '<span class="product-title">' + selectedProduct.text + '</span>' +
                    '<span class="product-price">' + selectedProduct.price + '</span>' +
                '</div>' +
                '<div class="product-quantity">' +
                    '<label for="bundle_quantity_' + selectedProduct.id + '">' + wc_session_bundle.strings.quantity + '</label>' +
                    '<input type="number" id="bundle_quantity_' + selectedProduct.id + '" ' +
                           'name="bundle_quantities[' + selectedProduct.id + ']" value="1" min="1" class="bundle-quantity-input" />' +
                '</div>' +
                '<button type="button" class="remove-bundle-product button-secondary">' + wc_session_bundle.strings.remove_product + '</button>' +
            '</div>'
        );
        
        // Add to bundle
        $('.session-bundle-products-list').append($productItem);
        
        // Clear selector
        $selector.val(null).trigger('change');
        
        // Update bundle total
        updateBundleTotal();
    }
    
    // Remove product from bundle
    function removeProductFromBundle() {
        $(this).closest('.session-bundle-product-item').remove();
        updateBundleTotal();
    }
    
    // Update quantity
    function updateQuantity() {
        updateBundleTotal();
    }
    
    // Calculate and update bundle total
    function updateBundleTotal() {
        var total = 0;
        
        $('.session-bundle-product-item').each(function() {
            var $item = $(this);
            var productId = $item.data('product-id');
            var quantity = parseInt($item.find('.bundle-quantity-input').val()) || 0;
            
            // Get product price from the price span
            var priceText = $item.find('.product-price').text();
            var price = parseFloat(priceText.replace(/[^\d.,]/g, '')) || 0;
            
            total += price * quantity;
        });
        
        // Update total display
        $('#bundle-total-price').text(formatPrice(total));
    }
    
    // Format price
    function formatPrice(price) {
        return wc_session_bundle.currency_symbol + price.toFixed(2);
    }
    
    // Initialize when document is ready
    if ($('#session-bundle-products-container').length > 0) {
        initProductSelector();
        
        // Bind events
        $(document).on('click', '#add-bundle-product', addProductToBundle);
        $(document).on('click', '.remove-bundle-product', removeProductFromBundle);
        $(document).on('change', '.bundle-quantity-input', updateQuantity);
        
        // Initial total calculation
        updateBundleTotal();
    }
    
    // Handle product type change
    $('select#product-type').on('change', function() {
        var productType = $(this).val();
        
        if (productType === 'session_bundle') {
            $('#session-bundle-products-container').show();
        } else {
            $('#session-bundle-products-container').hide();
        }
    });
    
    // Show/hide bundle container based on initial product type
    if ($('select#product-type').val() === 'session_bundle') {
        $('#session-bundle-products-container').show();
    } else {
        $('#session-bundle-products-container').hide();
    }
}); 