# WP Configurator Wizard - Project Overview

## Current State (v3.4.11)
**Live**: https://all-tech-plus.com/wizard
**Shortcode**: `[wp_configurator_wizard]`
**Stack**: PHP (WordPress), jQuery, custom CSS; no build step
**Currency**: EUR
**GitHub**: https://github.com/buttonsbond/atp-configurator

**For detailed historical changelog of stats enhancements, see `fridaytodo.md`.
For current refactoring roadmap, see `REFACTORINGTODO.md`.**

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

#### Filter Bot/Crawler User Agents from Interaction Tracking
**Goal**: Exclude interactions from known crawlers/bots to improve analytics accuracy.

**Implementation**:

1. **Settings: Add new fields** (`templates/admin/tabs/miscellaneous.php` in "Tracking & Privacy" section):
   - Checkbox: `exclude_bot_user_agents` (default checked)
   - Textarea: `bot_user_agents` (default populated with common bot patterns)
   ```
   Common settings:
   - One pattern per line
   - Case-insensitive substring matching
   - Pre-populated with: Googlebot, Bingbot, Yahoo! Slurp, DuckDuckBot, Baiduspider, YandexBot, Sogou, Exabot, facebot, IA_Archiver, Twitterbot, LinkedInBot, Slackbot, Discordbot, WhatsApp, Telegram, curl, wget, python-requests, Scrapy
   ```

2. **Settings_Manager** (`includes/class-settings-manager.php`):
   - In `sanitize_settings()` (line 232), add:
     ```php
     } elseif ( $key === 'exclude_bot_user_agents' ) {
         $sanitized['exclude_bot_user_agents'] = ! empty( $value ) ? 1 : 0;
     } elseif ( $key === 'bot_user_agents' ) {
         $sanitized['bot_user_agents'] = sanitize_textarea_field( $value );
     }
     ```
   - Add defaults in `get_default_options()` (line 320) under 'settings':
     ```php
     'exclude_bot_user_agents' => 1,
     'bot_user_agents' => "Googlebot\nBingbot\nYahoo! Slurp\nDuckDuckBot\nBaiduspider\nYandexBot\nSogou\nExabot\nfacebot\nIA_Archiver\nTwitterbot\nLinkedInBot\nSlackbot\nDiscordbot\nWhatsApp\nTelegram\ncurl\nwget\npython-requests\nScrapy",
     ```

3. **Interaction Tracking** (`includes/traits/trait-interaction-tracking.php`):
   - In `ajax_track_interaction()` method, after the admin IP check (around line 89), add bot detection:
     ```php
     // Check if this is a bot (skip tracking if enabled)
     $options = $this->settings_manager->get_options();
     $exclude_bots = ! empty( $options['settings']['exclude_bot_user_agents'] );
     $bot_patterns = ! empty( $options['settings']['bot_user_agents'] ) ? explode( "\n", $options['settings']['bot_user_agents'] ) : array();

     if ( $exclude_bots && ! empty( $bot_patterns ) ) {
         $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
         foreach ( $bot_patterns as $pattern ) {
             $pattern = trim( $pattern );
             if ( $pattern !== '' && stripos( $user_agent, $pattern ) !== false ) {
                 // Silently ignore bot interaction
                 if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                     error_log( "Interaction excluded due to bot detection: $pattern" );
                 }
                 wp_send_json_success( array( 'message' => 'Bot interaction excluded' ) );
                 return;
             }
         }
     }
     ```

4. **Recent Interactions Display** (`templates/admin/partials/recent-interactions.php`):
   - Currently shows all interactions. Modify the query to filter out bots (if desired) to keep admin view clean:
     - Option A: Always filter bots from admin display (they're not useful for analysis)
     - Option B: Add a checkbox to toggle bot visibility in Recent Interactions section
   - Recommended: Filter automatically by default (simpler UX). Add a filter toggle if needed later.
   - To implement: Join with implicit knowledge of bot patterns or query all and filter in PHP:
     ```php
     // After fetching $recent_events, filter if setting enabled
     $exclude_bots = ! empty( $options['settings']['exclude_bot_user_agents'] );
     $bot_patterns = ! empty( $options['settings']['bot_user_agents'] ) ? explode( "\n", $options['settings']['bot_user_agents'] ) : array();

     if ( $exclude_bots && ! empty( $bot_patterns ) ) {
         $filtered_events = array();
         foreach ( $recent_events as $event ) {
             // Need to fetch user_agent for this event
             // Modify query to include user_agent column
             // Or join with table to get full row including user_agent
         }
         // Update: modify query on line 25-32 to SELECT * (or include user_agent)
         // Then filter in PHP loop
     }
     ```
   - Better: Modify DB query to include `user_agent` and filter in PHP (simpler than complex SQL WHERE clauses).

5. **JavaScript (optional)**: No changes needed.

6. **Testing**:
   - Enable/disable bot filtering checkbox
   - Add custom bot patterns (e.g., "MyCrawler")
   - Simulate bot user agent (via browser dev tools or curl) and verify interaction not recorded
   - Verify legitimate user interactions still tracked
   - Recent Interactions section should not show bot events
   - Stats tab: Should bot-filtered interactions affect metrics? Currently stats query interactions table directly. Best: keep consistent - stats should also respect bot exclusion. Will be addressed separately if stats enhancement is needed.

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

##### 3. System Status Tab

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

**Implementation Steps**:
1. Redesign `class-system-status-view.php` to output div structure instead of table
2. Create CSS for status cards: padding, border-left color (dynamic by status), shadow on hover
3. Move action buttons into dedicated `.status-actions` container within card
4. Group checks: create categories (e.g., "Core Components", "Caching & Performance", "Email & Webhook", "Environment")
5. Add collapsible detail sections for long instructions (use `<details>` element or custom JS)
6. Improve copy-button styling: only show on hover, positioned top-right of code block
7. Add an overall health summary bar at top (e.g., "3 warnings, 1 error - review below")
8. Consider adding "Mark as reviewed" dismiss functionality for warnings

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
- Tooltips explaining why a tile is disabled (incompatibility reason)
- Option to force-add incompatible items with override warning
- Show incompatibility warnings in admin when editing features
- Further main plugin cleanup (move remaining rendering methods to view classes)

