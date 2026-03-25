# WP Configurator Wizard - Project Overview

## Current State (v3.5.2 - Stable)
**Live**: https://all-tech-plus.com/wizard
**Shortcode**: `[wp_configurator_wizard]`
**Stack**: PHP (WordPress), jQuery, custom CSS; no build step
**Currency**: EUR
**GitHub**: https://github.com/buttonsbond/atp-configurator

**For detailed historical changelog of stats enhancements, see `fridaytodo.md`.
For current refactoring roadmap, see `REFACTORINGTODO.md`.**

## Git Workflow
**Important**: Development work happens locally without automatic commits to GitHub. Only push changes when explicitly instructed. Follow the workflow in `CLAUDE.md` for releasing new versions.

### Features
- Frontend: drag-and-drop wizard, real-time pricing, responsive, sticky header, configurable "Convert to Quote" button, contact modal, webhook + email notifications.
- Admin: tabbed categories (CRUD, DnD), tile-based feature grid (modal edit/add/delete, DnD reorder), export/import, toast notifications.
- Data: `wp_configurator_options` (categories, features, settings) + `wp_configurator_quote_requests` table.

### Recent Changes Summary

For a detailed chronological record of all feature development (especially the Stats dashboard evolution), see **`fridaytodo.md`**.

Key milestones:
- **v2.9.29**: Admin Stats tab with Chart.js
- **v2.9.30**: Frontend interaction tracking
- **v2.9.31-2.9.32**: IP exclusion, initial engagement metrics
- **v2.9.49-2.9.54**: Date filtering, recurring revenue cards, chart improvements
- **v2.9.55**: Early admin template extraction
- **v3.0.0**: Complete modular refactor (see `REFACTORINGTODO.md`)
- **v3.1.5-3.1.8**: Ongoing enhancements, bug fixes, and minor features (including built-in emoji picker)
- **v3.2.7**: Category Information Field (frontend display)
- **v3.2.8**: Bug fix: category info now correctly placed inside collapsible sections
- **v3.4.11**: State Persistence Revolution - all admin UI state (tabs, collapse, sections) persists across page reloads; GitHub Actions integration complete
- **v3.4.12**: Enhanced email formatting and testing:
  - Admin notification emails now use beautiful HTML format (matching client email styling)
  - System Status tab: Split into two separate test buttons - "Send Test Client Email" and "Send Test Admin Email"
  - Improved email testing workflow for both email types
- **v3.4.13**: Admin UI modernization (stable release):
  - Miscellaneous Settings: Card-based layout with toggle switches, live preview, collapsible sections
  - Quote Requests: Enhanced responsive design and UI improvements
  - Stats Dashboard: Card grouping and visual refinements
  - System Status: Modern card-based diagnostic display with color-coded status indicators
- **v3.4.14**: Miscellaneous Settings enhancements:
  - All sections now collapsible with smooth animations
  - Sections start collapsed by default
  - State persistence via localStorage for collapsed/expanded state
  - Live preview toggle (enable/disable)
  - Simplified Advanced Settings structure
  - Bug fix: `enable_live_preview` setting now properly saved
- **v3.5.0 (2026-03-24)**: Stats Dashboard Refinement & Admin Modularization
  - Admin JavaScript modularization (from v3.4.17): Split admin.js into 5 modules, implemented global state, zero breaking changes
  - Stats Dashboard complete redesign:
    - Grouped metrics into 2 sections (Revenue Summary, Conversion & Engagement)
    - Removed 7 less-critical cards (Total Interactions, Features Added, Content Performance)
    - Streamlined charts to 3 key visualizations (Revenue Trend, Quote Requests, Billing Breakdown)
    - Removed doughnut and Top 10 Features charts
    - Made charts 40% more compact (180px height, 12px padding)
    - Responsive improvements: charts stack on tablets, cards use tighter spacing
    - Default date filter: All Time; cookie persistence (30 days)
  - Overall: cleaner, faster, more focused admin analytics experience

- **v3.5.1 (2026-03-25)**: GitHub Release Checker & System Status Finalization
  - **GitHub Release Checker**: Automatic version comparison with GitHub releases
    - System Status card: "Up to date" / "New release available" / "Working on Dev. Version"
    - Admin-wide dismissible banner when update available
    - "Force Check" button to bypass 12-hour cache for immediate verification
    - Smart cache invalidation ensuring button always appears
  - Completed System Status modernization from v3.5.0:
    - Consistent card-based layout across all checks
    - Force Check button added to GitHub Update Check card
    - Fixed admin notice to only show for actual updates (not dev versions)
  - Technical: Added AJAX endpoint, nonce security, 12-hour transient caching

- **v3.5.2 (2026-03-25)**: Category & Feature Images
  - **Image Upload for Categories and Features**: Upload/select product images via WordPress media library
    - New optional image field in Category Edit modal (replaces emoji icon when set)
    - New optional image field in Feature Edit modal (replaces emoji icon when set)
    - Frontend: Images display instead of emoji icons, with responsive sizing (24px for categories, variable for features)
    - Admin: Image previews in modals with remove button; thumbnail display in category tabs and feature grid
    - Fallback to emoji icons when no image uploaded (zero breaking change)
    - Uses `category_image_id` and `feature_image_id` fields in options array
  - Backend: Auto-enrichment of image URLs in Admin_UI and frontend wizard via `wp_get_attachment_image_url()`
  - Admin UI: Styled image previews with proper sizing and remove functionality
  - Settings Manager: Sanitization for new image ID fields (text sanitization)
  - Asset Manager: No changes needed (no new assets)
  - Database: No schema changes (uses existing options table)

---

### Completed (Highlights)

- **Feature incompatibility system**: Admin multi-select UI, frontend enforcement
- **Compulsory categories**: Dynamic badges and validation
- **Email/Webhook notifications**: With delivery status tracking
- **Quote management**: Responsive table, bulk actions, status updates
- **Import/Export settings**: Backup and restore
- **Comprehensive Stats Dashboard**: Date filters, engagement metrics, revenue trends, top features
- **Modular Architecture Refactor**: Split into Settings_Manager, Database_Manager, Admin_UI, Ajax_Handler, Stats_Renderer, Asset_Manager, Quote_Requests_View, System_Status_View
- **Built-in Emoji Picker**: Visual emoji selector for category and feature icons in admin modals
- **Emoji Picker Categorization**: All 419 emoji buttons manually categorized (smileys, hearts, stars, weather, animals, objects, symbols, flags) for optimal filtering performance
- **Emoji Picker Expansion**: Added 106+ new UI/web emojis (documents, email, arrows, status indicators, colors, clocks, business charts, file icons) and 34 essential country flags (EU + major European nations + US, UK, Canada, Australia). Total emoji count increased from 285 to 391 unique emojis.
- **Email & Webhook Notifications**: Fixed missing notification system - admin notification emails, client confirmation emails (with customizable message and HTML formatting), and webhook firing now all work automatically on quote submission. Database flags properly track delivery status.
- **System Status - Email/Webhook Diagnostics**: Added configuration checks for email and webhook settings in System Status tab with "Send Test Email" and "Test Webhook" buttons. Allows admins to verify SMTP and webhook endpoints directly from the dashboard.
- **Critical Bug Fix**: Fixed fatal error "Call to undefined method Settings_Manager::get_currency_symbol()" that prevented quote submissions from completing. Replaced with hardcoded € currency symbol in email templates.
- **Beautiful HTML Email Templates**: Redesigned client confirmation email with professional gradient header, card-based layout, styled tables, call-to-action button, and responsive design. Uses inline CSS for maximum email client compatibility.
- **Dedicated Test Email Setting**: Added optional "Test Email Address" field in Miscellaneous settings. When configured, the "Send Test Email" button in System Status uses this address instead of the admin notification email, preventing test emails from cluttering real inboxes.
- **Realistic Test Email**: The "Send Test Email" button now sends an actual sample client email with dummy data (sample products, prices, totals) so you can see exactly how the formatted email will look in real inboxes. Includes a notice that it's a test email.
- **Category Information Field**: Added optional descriptive text to categories that displays at the top of each category section on the frontend wizard, providing context and guidance to users (v3.2.7)
- **Category info collapsible fix**: Fixed placement so category info text correctly appears inside collapsible sections and hides when collapsed (v3.2.8)
- **Category & Feature Images**: Upload custom images for categories and features via WordPress media library; replaces emoji icons; zero breaking changes (v3.5.2)

---

### Licensing & Distribution

**Goal**: Apply GPLv2+ license (WordPress-compatible) and add donation/support prompt in admin interface.

**Rationale**:
- WordPress requires GPL-compatible licenses for plugins in the official repository
- GPLv2+ ensures no closed-source derivatives (copyleft)
- GPL allows commercial use but in practice, community respects free distribution
- Donation model aligns with open source ethos and WordPress ecosystem

**Implementation Steps**:

1. **Add GPL License Header** (`wp-configurator-wizard.php`):
   - Update plugin header comment (top of file) to include:
     ```php
     * License: GPL v3 or later
     * License URI: https://www.gnu.org/licenses/gpl-3.0.html
     ```
   - Ensure `Text Domain: wp-configurator` is present

2. **Create `donors.txt`** (manual maintenance):
   - Simple text file with one donor name per line
   - Place in plugin root for easy editing by admin
   - Initial sample names provided; admin manually adds/removes
   - Displayed in admin UI as "badge" list

3. **Add `license.txt`** file to plugin root:
   - Update plugin header comment (top of file) to include:
     ```php
     * License: GPL v2 or later
     * License URI: https://www.gnu.org/licenses/gpl-2.0.html
     ```
   - Ensure `Text Domain: wp-configurator` is present

2. **Add `license.txt`** file to plugin root:
   - Include full GPLv2 license text
   - Standard practice for WordPress plugins

3. **Add "Support Development" notice in Admin UI**:
   - Location: Admin Configurator page, below the page title/intro (in `templates/admin/page-wrapper.php` or similar)
   - Design: Subtle notice with "Support this plugin" or "Consider donating" message
   - Link to donation URL (to be provided - can be PayPal, GitHub Sponsors, etc.)
   - Optional: "Donate" button or link in the admin footer/sidebar
   - Keep it tasteful - not intrusive

4. **Update README.md**:
   - License declaration: `License: GPLv2 or later`
   - Add section: "Donations" with link and brief explanation
   - Clarify that plugin is free and open source, but donations welcome

5. **Version bump**: After implementation, bump to v3.2.9 (or v3.3.0 if significant)

6. **Testing**:
   - Verify license header appears in plugin file
   - Check admin UI displays support notice correctly
   - Ensure donation link works (or placeholder if URL not yet set)
   - Confirm README updates are visible on wordpress.org (if submitting there later)

**Note**: Donation URL can be added later; for now, implement placeholder or use a generic "support development" message without link, or use a temporary link to a donation page if available.

---

### Stretch / Future Ideas

#### Performance Optimization: Lazy Loading for Admin Tabs
**Analysis**: Current single-page admin loads ALL tab content (Categories, Miscellaneous, Quote Requests, Stats, System Status) on every page load. Stats and Quote Requests tabs perform heavier database queries, slowing initial load even when not viewing those tabs.

**Recommendation**: Implement **AJAX-based lazy loading** instead of splitting into submenu pages.
- ✅ Preserves smooth instant tab-switching UX
- ✅ Only loads data when needed
- ✅ Better perceived performance
- ❌ Not needed: Submenu would cause full page reloads and break the current tabbed interface

**Implementation Steps**:
1. Add AJAX endpoints in `includes/class-ajax-handler.php`:
   - `wp_ajax_wp_configurator_load_stats_tab()`: Returns stats HTML
   - `wp_ajax_wp_configurator_load_quotes_tab()`: Returns quote requests table HTML
2. Modify `templates/admin/tabs/stats.php` and `quote-requests.php`: Wrap content in identifiable container, add loading placeholder
3. Update `templates/admin/navigation.php` and JS: Attach click handlers to tabs that check if content loaded; if not, fetch via AJAX and inject into DOM
4. Use sessionStorage or data attribute to cache loaded tab HTML per page load
5. Test: Verify no duplicated queries, console errors, smooth UX

#### Feature: Category Information Field
**Goal**: Add optional descriptive text to categories that displays at the top of collapsible sections on the frontend wizard.

**Use Case**: Provide context to users (e.g., "Select at least one from this required section", "These features require a base package", etc.)

**Implementation Steps**:
1. **Database schema**: No changes needed (stored in `wp_configurator_options` serialized array)
2. **Settings_Manager** (`includes/class-settings-manager.php`):
   - Add `'info' => ''` to category sanitization in `sanitize_categories()` (line 152)
   - Update: add `'info' => sanitize_textarea_field( $cat['info'] ?? '' )` to the returned array structure
3. **Admin modal** (`templates/admin/partials/modals.php`):
   - In Category Edit Modal (around line 80), add new form row after Compulsory checkbox:
     ```html
     <div class="form-row">
         <label for="edit-category-info"><?php esc_html_e( 'Category Information', 'wp-configurator' ); ?></label>
         <textarea id="edit-category-info" rows="3" placeholder="Optional help text shown to users..."></textarea>
         <p class="description"><?php esc_html_e( 'Optional: Informational text displayed at the top of this category section on the frontend (supports line breaks).', 'wp-configurator' ); ?></p>
     </div>
     ```
   - Add JS to populate this textarea when opening modal (ensure existing data binds)
4. **Frontend display** (`templates/wizard.php`):
   - In collapsible category block (around line 86-98), after opening `<div class="category-section collapsible">` and before `<div class="category-header">`, add:
     ```php
     <?php if ( ! empty( $category['info'] ) ) : ?>
         <div class="category-info-text">
             <?php echo nl2br( esc_html( $category['info'] ) ); ?>
         </div>
     <?php endif; ?>
     ```
   - Add CSS for `.category-info-text` (padding, margin, background, border-radius)
5. **JavaScript**: No changes needed (info text is static)
6. **Testing**:
   - Create/edit category with info text (multi-line)
   - Verify admin modal saves and loads correctly
   - Frontend: confirm info displays only when set, with line breaks preserved
   - Non-collapsible mode: Decide where to show (maybe below category title) - follow same pattern
7. **Documentation**: Update README.md to mention new category info field

#### Filter Bot/Crawler User Agents from Interaction Tracking ✅ **COMPLETED**
**Goal**: Exclude interactions from known crawlers/bots to improve analytics accuracy.

**Implementation Completed**:

1. **Settings** (`includes/class-settings-manager.php`):
   - Added `exclude_bot_user_agents` (default: **disabled** - user must opt-in)
   - Added `bot_user_agents` with 20 pre-configured bot patterns
   - Sanitization: boolean for toggle, `sanitize_textarea_field()` for patterns

2. **Admin UI** (`templates/admin/tabs/miscellaneous.php` - Tracking & Privacy section):
   - **Bot & Crawler Filtering** card with:
     - Toggle switch (unchecked by default)
     - Textarea for patterns (placeholder shown when empty)
     - **"Restore defaults"** button to reset patterns
     - **Auto-fill**: Enabling toggle auto-populates patterns if field is empty
   - **Layout improvement**: IP Exclusion and Statistics cards now display side-by-side on ≥768px screens
   - Grid layout with responsive single-column on mobile

3. **Interaction Tracking** (`includes/traits/trait-interaction-tracking.php`):
   - Bot check inserted after admin IP exclusion (lines 91-106)
   - Uses case-insensitive `stripos()` for substring matching against patterns
   - Silently returns success JSON when bot detected (no DB insert)
   - WP_DEBUG logging for troubleshooting

4. **Recent Interactions Display** (`templates/admin/partials/recent-interactions-content.php`):
   - Query modified to include `user_agent` column (lines 10, 15)
   - Post-query filtering (lines 19-40): removes bot events based on same settings
   - Keeps admin view clean and consistent with tracking behavior

**Default Bot Patterns** (20 total):
```
Googlebot, Bingbot, Yahoo! Slurp, DuckDuckBot, Baiduspider, YandexBot, Sogou,
Exabot, facebot, IA_Archiver, Twitterbot, LinkedInBot, Slackbot, Discordbot,
WhatsApp, Telegram, curl, wget, python-requests, Scrapy
```

**UX Flow**:
- Fresh install: Toggle OFF, patterns field empty (placeholder visible)
- User enables toggle → patterns auto-fill with defaults
- User can edit patterns, click "Restore defaults" to reset, or leave as-is
- Must click "Save Features" to persist changes
- Bot filtering active immediately on next interactions (no page refresh needed)

**Files Modified**:
- `includes/class-settings-manager.php`
- `templates/admin/tabs/miscellaneous.php`
- `includes/traits/trait-interaction-tracking.php`
- `templates/admin/partials/recent-interactions-content.php`
- `assets/css/admin.css` (grid layout)

---

#### Modernize Admin UI Layouts (Miscellaneous, Quote Requests, System Status)

**Goal**: Replace basic WordPress tables/forms with modern, responsive, and user-friendly interfaces using contemporary UI patterns.

---

##### 1. Miscellaneous Settings Tab

**Current Issues**:
- Long vertical form with many fields grouped in sections
- No visual hierarchy improvement (all fields equal weight)
- Uses standard WordPress form controls (plain checkboxes, textareas)

**Modern UI Proposal**:
- Group related settings in **card containers** with icons and subtle shadows
- Use **switch toggles** for boolean settings (more intuitive than checkboxes)
- Implement **accordion sections** for advanced settings (User-Agent filtering from previous task would fit here)
- Add **real-time preview** where applicable (e.g., frontend title/subtitle live preview)
- Use **tooltips** for descriptive text instead of inline paragraphs
- Better spacing and visual separation

**Implementation Steps**:
1. Create CSS for card layout (shadows, rounded corners, padding)
2. Replace checkbox inputs with toggle switch CSS + same underlying HTML
3. Add collapsible sections using details/summary or custom JS for accordion
4. Add live preview pane (AJAX not needed - just JS DOM manipulation)
5. Use Dashicons or emoji for section icons inline
6. Improve form field grouping: use CSS Grid for multi-column layouts on wide screens

**Status**: ✅ Completed (2026-03-23)
- Modern card-based layout with icons and enhanced shadows
- Toggle switches replace all checkboxes with smooth animations
- Live preview for frontend title and subtitle with toggle to enable/disable
- Tooltips added for field guidance
- Advanced settings moved to collapsible accordion section
- Settings reorganized into logical card groups (Email & Notifications, Frontend Content, Display & Layout, Tracking & Privacy)
- **All main sections are collapsible** with smooth animations and toggle arrows
- **Sections start collapsed by default** (reduces page scrolling; click header to expand)
- State persists via localStorage (remembers collapsed/expanded across page reloads)
- Full responsive design maintains usability on mobile

---

##### 2. Quote Requests Tab

**Current Issues**:
- Wide table with 11 columns (Date, Name, Business, Email, Phone, Total, Status, Admin Email, Client Email, Webhook, Actions)
- Inline status dropdowns work but table is cramped on smaller screens
- No search/filter capabilities
- No pagination (could be issue with many records)
- Status shown as dropdown (editable) with inline spinner - good UX but table crowded

**Modern UI Proposal**:
- **Card-based layout** for each quote request on mobile/tablet (already partially implemented via responsive CSS)
- Add **search and filter bar** above table: by status, date range, email search
- Implement **client-side pagination** or server-side pagination for large datasets
- Replace checkbox selection with **bulk action toolbar** that appears when items selected
- Use **status badges** (colored pills) instead of dropdown where view-only; keep dropdown for bulk updates only
- Add **quick view modal** to see full request details without scrolling table
- Show **items summary** as expandable accordion row (current approach good)
- Consider **split view**: left sidebar for filters/search, right panel for results

**Implementation Steps**:
1. Add search input and status filter dropdown above table
2. Implement JavaScript to filter table rows client-side (for moderate data sizes)
3. Enhance status display: when not editing, show styled badge; on double-click or edit button, show dropdown
4. Add pagination controls (simple: show 20/50/100 per page, or paginate with page numbers)
5. Add floating bulk action bar that appears when checkboxes selected
6. Create modal template for quick view (populate with full JSON items/totals)
7. Improve responsive: at <768px, use full-width cards with grid layout for fields
8. Add column sorting (click headers to sort by date, name, total, status)

---

##### 3. System Status Tab ✅ **COMPLETED** (2026-03-25)

**Current Issues**:
- Uses table layout (functional but dated)
- Long action HTML blocks with configuration instructions can overflow
- Copy buttons for code snippets (good feature)
- Refresh button (good)

**Modern UI Proposal**:
- Replace table with **card-based check items** grouped by category
- Each card: title, status icon (large, colored circle), description, action area
- Use **color-coded borders** or left accent stripe for quick visual scanning
- Group related checks: Database, Caching, Version Checks, Configuration, Notifications
- Use **progress indicator** or overall health summary at top
- Action buttons as primary buttons (not in a description block)
- Add **expandable details** for verbose instructions (chevron toggle)
- Better code block presentation: syntax highlighting, one-click copy (already present - enhance style)

**Implementation Completed**:
- Replaced table with responsive card grid (1→3→4→5 columns)
- Compact card design with status-colored left accent borders
- Grouped checks into 5 sections: Core Components, Caching & Performance, Communications, Environment, Analytics
- Added health summary bar with counts
- Added PHP Memory Limit check (recommends 256M+)
- Preserved all functionality: refresh, test emails, webhook testing, copy buttons
- Removed redundant intro text and legend (icons self-explanatory)

**Files Modified**:
- `includes/class-system-status-view.php`
- `assets/css/admin.css`
- `templates/admin/tabs/system-status.php`

---

#### Refine Stats Dashboard Cards (Amalgamation & Grouping)

**Current State**: 17 individual stat cards displayed in a single horizontal scrollable row. Too many to digest at once; overwhelms users; key metrics get lost.

**Proposed Grouping** (reduce from 17 to ~8-9 logical groups):

1. **Requests Overview** (Card):
   - Total Quote Requests
   - Wizard Views
   - View→Quote Rate (%)

2. **Revenue Summary** (Card):
   - Quote Value (Total)
   - Monthly Recurring (MRR)
   - Quarterly Recurring (QRR)
   - Annual Recurring (ARR)
   - Cash Collected (Invoiced) as smaller secondary values

3. **Engagement** (Card):
   - Total Interactions
   - Features Added
   - Initial Engagement
   - Engagement Rate (%)

4. **Conversion Funnel** (Card or three small cards):
   - Checkout Started
   - Quotes Submitted
   - Checkout Abandoned
   - Quote→Confirmed Rate (%)
   - Quote→Invoiced Rate (%)

5. **Content Performance** (Card):
   - Unique Features Used
   - Avg Items/Quote

6. **Status Distribution** (Already have doughnut chart - keep it)

**Rationale**: Group by semantic meaning rather than listing every raw number. Show trends via charts (already present), use summary cards for high-level KPIs.

**Implementation Steps**:
1. Update `class-stats-renderer.php`:
   - Create new grouped metrics array structure
   - Keep original metrics available but display as subgroups within cards
2. Update `templates/admin/tabs/stats.php` (or render method output) to use new HTML structure:
   ```html
   <div class="stats-summary-cards">
     <div class="stat-card group-card">
       <h4 class="group-title">Requests</h4>
       <div class="stat-value">...</div>
       <div class="stat-subvalue">...</div>
     </div>
     ...
   </div>
   ```
3. Add CSS for card grouping: maybe use CSS Grid with `grid-template-columns: repeat(auto-fit, minmax(300px, 1fr))` so cards can be wider
4. Inside each group card, use small typography for sub-metrics (smaller font, lighter color)
5. Optionally add a "?" icon with tooltip explaining each metric group
6. Keep all charts below (they already provide visual insights)
7. Consider adding a "Metrics Glossary" toggle at bottom explaining each metric definition

**Alternative**: Keep all 17 cards but allow **drag-and-drop customization** (user chooses which cards visible). More complex; recommend grouping first.

---

#### Additional Stretch Ideas (Not Yet Prioritized)

##### Conditional Feature Visibility by Referrer Parameters
**Goal**: Allow features to be shown/hidden based on URL parameters in the referring URL (e.g., for Facebook promotions, campaign targeting).
- Add 2 optional fields to feature edit modal:
  - `show_if_param` (text) - parameter name that must be present in URL to show this feature
  - `hide_if_param` (text) - parameter name that, if present, hides this feature
- Frontend logic: On wizard load, check `window.location.search` for these parameters
- If `show_if_param` is set and parameter NOT found → hide feature tile
- If `hide_if_param` is set and parameter IS found → hide feature tile
- Use case: Create special Facebook promo where only users with `?fb_promo=summer` see certain features

##### Feature Product Images ✅ COMPLETED (v3.5.2)
**Goal**: Allow uploading/selecting a product image for each feature, as an alternative to the emoji icon.
- Add new field in feature modal: `feature_image` (media uploader)
- Store image URL/attachment ID in feature data
- Frontend: If image exists, display `<img>`; otherwise fall back to emoji icon
- Admin grid: Show thumbnail preview in feature tile

##### Quotes Request Tab UI Improvements
**Current**: Basic table with many columns, cramped on small screens.
**Proposed**:
- Bulk action toolbar that appears when checkboxes selected
- Better status badges (colored pills) instead of inline dropdowns
- Quick view modal to see full quote details without scrolling
- Card layout on mobile/tablet
- Column sorting (click headers)
- Search/filter by status or date range

##### System Status Tab UI Improvements
**Current**: Table-based layout with long instruction blocks.
**Proposed**:
- Replace table with card-based check items grouped by category
- Each card: title, large status icon, description, action area
- Color-coded left border or background for quick scanning
- Group checks: Core Components, Caching, Email, Database, Environment
- Collapsible details for verbose instructions
- Overall health summary bar at top

##### Multi-Template Frontend Support
**Goal**: Allow selecting different frontend wizard templates/layouts from settings.
- Add setting: `frontend_template` (dropdown: 'default', 'compact', 'minimal', etc.)
- Each template is a separate PHP template file in `templates/frontend/`
- Main `wizard.php` becomes 'default' template
- Template selector in Miscellaneous Settings
- Provide 2-3 layout variations (e.g., side-by-side vs stacked panels, different tile sizes)
- Future: admin can add custom templates via filter/hook

##### Further Main Plugin Cleanup
- Move any remaining rendering methods to dedicated view classes
- Audit `wp-configurator-wizard.php` for dead code or responsibilities that belong elsewhere
- Ensure single responsibility principle: main plugin should only bootstrap/coordinate, not render

