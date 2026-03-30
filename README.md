# ATP Quote Configurator

A modern, modular WordPress plugin for creating interactive cost estimation wizards with drag-and-drop functionality, real-time analytics, and comprehensive quote management.

**Current version: 3.5.5** (stable)
**GitHub Repository**: https://github.com/buttonsbond/atp-configurator

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
- **Category & Feature Images**: Upload custom images via WordPress media library to replace emoji icons with product photos or custom graphics (optional, zero breaking changes)

## Changelog

### Version 3.5.5 (2026-03-30)
- **Added**: URL Parameter Tracking & Marketing Attribution
  - Capture UTM parameters (source, medium, campaign) and custom variables (webURL, botID) from URL
  - Store URL parameters with interaction events and quote requests in `metadata` JSON column
  - New "Marketing Attribution" section in Stats dashboard:
    - Top Sources (horizontal bar chart) showing quote counts and revenue
    - Top Campaigns (horizontal bar chart) showing quote counts and revenue
    - Top Clients (webURL) list showing quotes and revenue
    - Bot Performance (botID) list showing quotes and revenue
  - URL parameters displayed as badges in Recent Interactions and Quote Requests admin tables
  - Automatic database upgrade: `metadata` column added to `wp_configurator_quote_requests` table via `admin_init`
- **Improved**: Admin UI with new URL parameter badges and enhanced analytics

### Version 3.5.2.1 (2026-03-26) - Patch Release
**Important**: This release fixes critical bugs found in v3.5.2. Please update immediately.

- **Fixed**: Category ID changes now properly update associated features
  - Previously, editing a category ID would orphan its features
  - Features now correctly follow category ID changes via automatic remapping
- **Fixed**: Admin category tabs HTML structure
  - Action buttons (duplicate/edit/delete) now render inline with the tab
  - Resolved invalid nested button markup that caused visual separation
- **Fixed**: Debug mode data corruption
  - Reference variables properly unset to prevent array corruption when `?debug=1` is enabled
  - Features like "Up to 4 pages" now appear correctly in debug mode
- **Improved**: Feature orphan detection and automatic repair
  - More robust data integrity checks in settings manager
  - Enhanced Unicode-aware whitespace handling

### Version 3.5.2 (2026-03-25)
- **Added**: Category & Feature Images
  - Upload custom images via WordPress media library for categories and features
  - Images replace emoji icons in frontend wizard and admin UI with responsive sizing
  - Admin modals: image preview with remove button, fallback to emoji when no image
  - Zero breaking changes: existing installations continue to use emoji icons
- **Added**: GitHub Release Checker
  - Automatic version comparison with GitHub releases API (12-hour caching)
  - System Status card shows "Up to date" / "New release available" / "Working on Dev. Version"
  - Admin-wide dismissible banner when updates available
  - "Force Check" button to bypass cache for immediate verification
- **Added**: Weekly Donors Sync (cron)
  - Scheduled weekly sync of donor information (e.g., from GitHub Sponsors)
  - System Status tab displays current donors list
  - Manual "Sync Now" button for on-demand updates
- **Enhanced**: System Status tab with consistent card-based layout and improved diagnostics
- **Technical**: Added AJAX endpoint for GitHub check, nonce security, and transient caching
- **Fixed**: Order of initialization (system_status_view before ajax_handler) for proper dependency injection

### Version 3.5.0 (2026-03-24)
- **Refactored**: Admin JavaScript modularization complete
  - Split monolithic `admin.js` (1599 lines) into 5 focused modules
  - New modules: `admin-common.js` (utilities + global state), `admin-tabs.js`, `admin-settings.js`, `admin-emoji.js`, `admin-import-export.js`
  - Implemented `window.WPConfiguratorAdmin` global state container for cross-module communication
  - Updated `class-asset-manager.php` with proper script dependencies
  - Zero breaking changes; admin.js reduced to ~1117 lines
- **Redesigned**: Stats Dashboard - cleaner, more focused UX
  - Grouped cards into 2 logical sections: Revenue Summary + Conversion & Engagement
  - Reduced from 18 cards to 11; removed less-critical metrics (Total Interactions, Features Added, Content Performance)
  - Streamlined charts: keep only 3 most important (Revenue Trend, Quote Requests, Billing Breakdown)
  - Removed doughnut and Top 10 Features charts to reduce clutter
  - Charts more compact: 180px height, 12px padding (was 300px, 20px)
  - Responsive layout: charts stack vertically on tablets and smaller screens
  - Default date filter changed to "All Time" with 30-day cookie persistence
  - Removed intro description; tighter heading spacing (2px margins)
- **Improved**: Overall admin interface consistency and performance

### Version 3.4.14 (2026-03-23)
- **Enhanced**: Miscellaneous Settings with collapsible sections (all 5 main sections now collapse/expand)
- **Enhanced**: Sections start collapsed by default to reduce page scrolling
- **Enhanced**: State persistence via localStorage for section collapse states
- **Enhanced**: Logical grouping of settings into clear card categories
- **Enhanced**: Live preview pane now has enable/disable toggle
- **Simplified**: Advanced Settings structure (removed redundant nested accordion)
- **Fixed**: `enable_live_preview` setting now properly saved and sanitized
- **Added**: `enable_live_preview` option to settings defaults

### Version 3.4.13 (2026-03-19)
- **Modernized**: Miscellaneous Settings tab with card-based layout, toggle switches, live preview, collapsible sections
- **Modernized**: Quote Requests tab with enhanced responsive design and UI improvements
- **Modernized**: Stats Dashboard with card grouping and visual refinements
- **Modernized**: System Status tab with modern card-based diagnostic display and color-coded status indicators
- **Improved**: Overall admin UI consistency and user experience
- **Status**: Testing release

### Version 3.4.12 (2026-03-14)
- **Enhanced**: Admin notification emails now use beautiful HTML format (matching client email styling)
- **Enhanced**: System Status tab split into two separate test buttons - "Send Test Client Email" and "Send Test Admin Email"
- **Improved**: Email testing workflow for both email types

### Version 3.4.11 (2026-03-11)
- **Fixed**: Navigation tab state persistence now works correctly (scope issue resolved)
- **Fixed**: Category tab selection now persists across page reloads
- **Fixed**: Header collapse/expand state persists with proper icon orientation and padding
- **Fixed**: Donors and Interactions collapsible sections now correctly restore state
- **Technical**: Moved `loadAdminState`/`saveAdminState` to global scope for cross-module access
- **Technical**: Unified state management class usage (`.collapsed`) and jQuery data access (`.data('categoryId')`)

### Version 3.4.0 – 3.4.10 (Earlier 2026)
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

## GitHub Integration

This repository is integrated with GitHub Actions for automated Claude Code reviews:
- Pull requests trigger automated code reviews
- Commits to main can be reviewed via workflow dispatch

See `.github/workflows/` for configuration details.

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
