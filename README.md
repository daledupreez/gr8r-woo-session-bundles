# Gr8r Session Bundles for WooCommerce

A WordPress plugin that adds a new "Session Bundle" product type to WooCommerce, allowing you to create bundles of multiple products with custom quantities.

## Features

- **New Product Type**: Adds "Session Bundle" as a selectable product type in WooCommerce
- **Product Selection**: Choose from existing WooCommerce products to include in bundles
- **Quantity Management**: Specify custom quantities for each bundled product
- **Dynamic Pricing**: Automatically calculate bundle prices based on included products
- **Stock Management**: Intelligent stock checking for bundled products
- **Frontend Display**: Beautiful display of bundle contents on product pages
- **Cart Integration**: Bundle information displayed in cart and checkout
- **Responsive Design**: Mobile-friendly interface for both admin and frontend

## Installation

1. **Upload the Plugin**:
   - Upload the `woocommerce-session-bundle` folder to your `/wp-content/plugins/` directory
   - Or zip the folder and upload via WordPress admin

2. **Activate the Plugin**:
   - Go to **Plugins > Installed Plugins** in your WordPress admin
   - Find "WooCommerce Session Bundle" and click **Activate**

3. **Requirements**:
   - WordPress 5.0 or higher
   - WooCommerce 5.0 or higher
   - PHP 7.4 or higher

## Usage

### Creating a Session Bundle Product

1. **Add New Product**:
   - Go to **Products > Add New** in your WordPress admin
   - Set the product type to **"Session Bundle"**

2. **Configure Bundle**:
   - In the "Session Bundle Products" meta box, use the product selector to add products
   - Set quantities for each bundled product
   - The bundle total will be calculated automatically

3. **Set Bundle Price** (Optional):
   - In the "General" tab, you can set a custom bundle price
   - Leave empty to use the calculated total from bundled products

4. **Publish**:
   - Set other product details (title, description, images, etc.)
   - Click **Publish** to make the bundle available

### Managing Bundle Products

- **Add Products**: Use the search dropdown to find and add products
- **Remove Products**: Click the "Remove" button next to any product
- **Adjust Quantities**: Change the quantity input for each product
- **Real-time Updates**: Bundle total updates automatically as you make changes

## Frontend Features

### Product Pages
- Bundle contents displayed prominently
- Clear listing of included products and quantities
- Dynamic price calculation
- Stock status indicators

### Shop/Category Pages
- Bundle indicator showing number of included products
- Quick overview of bundle contents

### Cart & Checkout
- Bundle summary in cart items
- Detailed bundle information during checkout
- Stock validation for all bundled products

## File Structure

```
woocommerce-session-bundle/
├── woocommerce-session-bundle.php    # Main plugin file
├── includes/
│   ├── class-wc-product-session-bundle.php    # Product class
│   ├── class-wc-session-bundle-admin.php      # Admin functionality
│   └── class-wc-session-bundle-frontend.php   # Frontend functionality
├── assets/
│   ├── js/
│   │   ├── admin.js                  # Admin JavaScript
│   │   └── frontend.js               # Frontend JavaScript
│   └── css/
│       ├── admin.css                 # Admin styles
│       └── frontend.css              # Frontend styles
└── README.md                         # This file
```

## Hooks and Filters

### Actions
- `wc_session_bundle_before_add_to_cart` - Before adding bundle to cart
- `wc_session_bundle_after_add_to_cart` - After adding bundle to cart
- `wc_session_bundle_before_display` - Before displaying bundle contents
- `wc_session_bundle_after_display` - After displaying bundle contents

### Filters
- `wc_session_bundle_price_calculation` - Modify bundle price calculation
- `wc_session_bundle_display_description` - Modify bundle description display
- `wc_session_bundle_stock_check` - Modify stock checking logic

## Customization

### Styling
The plugin includes comprehensive CSS that can be customized:
- Admin styles: `assets/css/admin.css`
- Frontend styles: `assets/css/frontend.css`

### JavaScript
Extend functionality by modifying:
- Admin JavaScript: `assets/js/admin.js`
- Frontend JavaScript: `assets/js/frontend.js`

### PHP Classes
Extend the core classes to add custom functionality:
- `WC_Product_Session_Bundle` - Product class
- `WC_Session_Bundle_Admin` - Admin functionality
- `WC_Session_Bundle_Frontend` - Frontend functionality

## Troubleshooting

### Common Issues

1. **Bundle Products Not Saving**:
   - Ensure you have proper permissions
   - Check that WooCommerce is active
   - Verify nonce fields are present

2. **Prices Not Calculating**:
   - Check that bundled products have valid prices
   - Ensure products are published and purchasable

3. **Stock Issues**:
   - Verify all bundled products have sufficient stock
   - Check individual product stock settings

### Debug Mode
Enable WordPress debug mode to see detailed error messages:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

## Support

For support and feature requests:
- Create an issue on the plugin repository
- Check the troubleshooting section above
- Ensure you're using compatible versions of WordPress and WooCommerce

## Changelog

### Version 1.0.0
- Initial release
- Session Bundle product type
- Product selection and quantity management
- Frontend display and cart integration
- Admin interface with real-time price calculation

## License

This plugin is licensed under the GPL v2 or later.

## Credits

Developed for WooCommerce Session Bundle functionality. 