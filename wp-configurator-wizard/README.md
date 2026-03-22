# ATP Quote Configurator

A modern, modular WordPress plugin for creating interactive cost estimation wizards with drag-and-drop functionality, real-time analytics, and comprehensive quote management.

**Current version: 3.4.11**

## Features

- **Drag & Drop Interface**: Users can drag features from the left panel to the right drop zone
- **Real-time Pricing**: Cost updates instantly as items are added/removed
- **Configurable Options**: All features and pricing are configurable via admin settings
- **Responsive Design**: Works on all devices with mobile optimization
- **Sticky Price Display**: Total cost stays visible at top of page
- **Tabbed Admin Interface**: Manage categories and features in a unified tabbed UI with drag-and-drop reordering
- **Interaction Tracking**: Comprehensive analytics with events like wizard views, feature additions, checkout starts, and quote submissions
- **Feature Incompatibility**: Define mutually exclusive features; system prevents conflicting selections
- **Compulsory Categories**: Ensure users select at least one feature from required categories
- **Quote Management**: View, update status, and delete quote requests in the admin
- **Email & Webhook Notifications**: Send quotes to clients and forward to external systems
- **Import/Export Settings**: Backup and restore configuration
- **Statistics Dashboard**: Charts and metrics including engagement rates, revenue trends, and feature popularity
- **Built-in Emoji Picker**: Visual emoji selector with category tabs (Smileys, Hearts, Weather, Objects, Symbols, Flags) for quick icon selection in admin modals
- **Category Information Text**: Add optional descriptive text to each category that appears at the top of the category section on the frontend, providing context and guidance to users

## Changelog

### Version 3.4.11 (2025)
- **Fixed**: Navigation tab state persistence now works correctly (scope issue resolved)
- **Fixed**: Category tab selection now persists across page reloads
- **Fixed**: Header collapse/expand state persists with proper icon orientation and padding
- **Fixed**: Donors and Interactions collapsible sections now correctly restore state
- **Technical**: Moved `loadAdminState`/`saveAdminState` to global scope for cross-module access
- **Technical**: Unified state management class usage (`.collapsed`) and jQuery data access (`.data('categoryId')`)

### Version 3.4.0 – 3.4.10 (Earlier 2025)
- See full history in wp-configurator-wizard.php (plugin header)

## Installation

1. Upload the plugin to your WordPress site
2. Activate from WordPress Dashboard → Plugins
3. Configure options from WordPress Dashboard → ATP Configurator
4. Use the shortcode `[wp_configurator_wizard]` in any page/post

## Configuration

After activation, you can configure the configurator from the admin menu:

1. **Dashboard → ATP Configurator** - Main settings page with tabs:
   - **Categories & Features**: Define feature categories (tabs) and individual features with pricing
   - **Miscellaneous Settings**: Webhook URL, email settings, tile layout preferences, client message, etc.
   - **Quote Requests**: View and manage submitted quotes
   - **Stats**: Analytics dashboard with charts and key metrics
2. **Categories** - Manage feature categories as tabs; add, edit, delete, and reorder them. Category IDs are auto-generated from names.
3. **Features** - Add/remove individual features; assign to categories; features are filtered by the selected category tab.
4. **Page Packages** - (Deprecated) Page packages are now represented as a compulsory category.

## Customization

The plugin is fully customizable:

- Add/remove feature categories
- Add/remove features with custom pricing
- Enable/disable individual features
- Change icons and descriptions
- Set billing types (one-off, monthly, quarterly, annual)
- Define incompatible feature pairs
- Configure compulsory categories
- Add category information text (contextual help shown on frontend)
- Customize tile layout (columns for desktop/tablet/mobile)
- Set notification emails and webhook endpoints

## Usage

Simply add the shortcode `[wp_configurator_wizard]` to any page or post where you want the configurator to appear.

## Data Model

The plugin stores all configuration in a single WordPress option: `wp_configurator_options` (serialized array). This includes:

- `categories`: Array of category objects `{id, name, icon, compulsory, order, info}`
- `features`: Array of feature objects `{category_id, name, description, icon, price, billing_type, enabled, order, sku, incompatible_with}`
- `settings`: Various plugin settings including webhook URL, notification email, layout preferences, etc.

Additional tables:

- `wp_configurator_quote_requests` – stores submitted quotes
- `wp_configurator_interactions` – tracks user engagement events

## Developer Notes

### File Structure

```
wp-configurator-wizard/
├── wp-configurator-wizard.php    # Main plugin bootstrap (~575 lines)
├── includes/
│   ├── class-database-manager.php
│   ├── class-settings-manager.php
│   ├── class-admin-ui.php
│   ├── class-ajax-handler.php     # Uses traits for logic
│   ├── class-stats-renderer.php
│   ├── class-asset-manager.php
│   ├── class-quote-requests-view.php
│   ├── class-system-status-view.php
│   └── traits/
│       ├── trait-cost-calculation.php
│       ├── trait-quote-management.php
│       ├── trait-interaction-tracking.php
│       └── trait-data-io.php
├── templates/
│   ├── wizard.php                # Frontend HTML
│   └── admin/
│       ├── page-wrapper.php
│       ├── tabs/
│       │   ├── categories-features.php
│       │   ├── miscellaneous.php
│       │   ├── quote-requests.php
│       │   ├── stats.php
│       │   └── system-status.php
│       └── partials/
│           ├── global-actions.php
│           ├── modals.php          # Category & feature edit modals
│           └── recent-interactions.php
├── assets/
│   ├── css/
│   │   ├── style.css
│   │   ├── admin.css
│   │   └── admin-stats.css
│   └── js/
│       ├── wizard.js
│       └── admin.js              # Drag/drop, emoji picker, modals
└── languages/
```

### Key Hooks

- `wp_ajax_calculate_cost` – frontend cost calculation
- `wp_ajax_submit_quote_request` – quote submission
- `wp_ajax_track_interaction` – analytics tracking
- `wp_ajax_export_settings` / `import_settings` – settings export/import
- `wp_ajax_delete_quote_request` / `update_quote_status` – admin quote actions

### Filters

- `wp_configurator_total_cost` – modify calculated total cost

## Credits

Developed by [All Tech Plus](https://all-tech-plus.com) and [AICognitio](https://aicognitio.com).

## License

This plugin is licensed under the GPLv3 or later. This means you are free to use, modify, and distribute the plugin for any purpose, including commercial use, as long as you maintain the same license for any distributed modifications.

See the [LICENSE.txt](LICENSE.txt) file for the full license text.

## Support Development

This plugin is completely free and open source. If you find it useful, please consider making a donation to support ongoing development and maintenance:

[Donate via PayPal](https://www.paypal.com/paypalme/alltechplus)

Your support helps keep the plugin updated and compatible with the latest WordPress versions!

### Donor Acknowledgments

In the plugin's admin interface, we proudly display the names of supporters who have contributed via PayPal. If you'd like to be recognized:

1. Make a donation through the PayPal link above
2. Contact the plugin author with your preferred name to display
3. Your name will be added to the donors wall in the admin dashboard

*(Note: Donor names are manually maintained in `donors.txt` by the plugin administrator.)*
