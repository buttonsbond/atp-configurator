# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working on this repository.

## Session Management
- **Always** read `TODO.md` at the start of every session to understand the current state.
- **Update** `TODO.md` after completing a sub-task (mark `[x]`) or if project state changes.
- **Prioritize**: Follow the "Next Up" items in `TODO.md` unless user specifies otherwise.

## Token Saving Rules
- **Lazy Loading**: Only read files needed for the current task.
- **Brief Responses**: Concise explanations; avoid resending large files.
- **Ask**: If a task requires >3 file reads, confirm with user before proceeding.

## Compact Instructions
- When compacting, always preserve the current high-level plan and progress.
- Maintain a list of all modified files and active test commands.
- Summarize long tool outputs into concise findings instead of keeping raw logs.
- Do not stop tasks early due to token budget; save state to memory before refreshes.

## Plugin Development

### Development Workflow
- Edit files directly in `wp-configurator-wizard/`.
- After making changes, create a new ZIP: `zip -r wp-configurator-wizard.zip wp-configurator-wizard -x "*.git*"` (stored in project root).
- Bump version in two places: plugin header (`wp-configurator-wizard.php`, line 5) and constant `VERSION` (around line 22).
- Test: use shortcode `[wp_configurator_wizard]` on a WordPress page; check admin UI at Dashboard → Configurator.
- Clear browser cache after CSS/JS changes; check console for errors.

### Activating/Deactivating
- Via WordPress admin → Plugins, or WP-CLI:
  - `wp plugin activate wp-configurator-wizard`
  - `wp plugin deactivate wp-configurator-wizard`

### Useful Commands
- List PHP files: `find wp-configurator-wizard -name "*.php"`
- List CSS: `find wp-configurator-wizard -name "*.css"`
- List JS: `find wp-configurator-wizard -name "*.js"`

## Code Architecture (Current)

### Structure (Modular Architecture)
```
wp-configurator-wizard/
├── wp-configurator-wizard.php   # Main plugin: bootstrap only, coordinates managers (~575 lines)
├── includes/                    # Core classes & traits
│   ├── class-database-manager.php
│   ├── class-settings-manager.php
│   ├── class-admin-ui.php
│   ├── class-ajax-handler.php
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
│   ├── wizard.php               # Frontend HTML (dynamic rendering)
│   └── admin/
│       ├── page-wrapper.php
│       ├── tabs/ (categories-features, miscellaneous, quote-requests, stats, system-status)
│       └── partials/ (global-actions, modals, recent-interactions)
├── assets/
│   ├── css/
│   │   ├── style.css            # Frontend + admin base
│   │   ├── admin.css            # Admin-specific styles
│   │   └── admin-stats.css      # Stats charts
│   └── js/
│       ├── wizard.js            # Frontend drag/drop, pricing
│       └── admin.js             # Admin DnD, modals, emoji picker
├── languages/                   # Translation files (empty)
└── README.md
```

### Data Model
**Options**: `wp_configurator_options` (single serialized array)
- `categories`: array of `{id, name, icon, compulsory (0/1), order, info}`
- `features`: array of `{category_id, name, description, icon, price, billing_type ('one-off'|'monthly'|'quarterly'|'annual'), enabled (0/1), order, sku, incompatible_with}`
- `settings`: Various settings (webhook_url, quote_button_text, notification_email, collapsible_categories, accordion_mode, frontend_title, frontend_subtitle, dropzone_footer_text, client_message, send_client_email, exclude_admin_ip, admin_ip_address)

**Tables**:
- `wp_configurator_quote_requests` – stores submitted quotes with status, emails, webhook data
- `wp_configurator_interactions` – tracks user engagement events

### Admin Interface
- Admin UI rendered via template files in `templates/admin/`
- Category tabs (drag-drop reorder) filter features grid below
- Category/feature CRUD via modals; built-in emoji picker for icons
- Drag-and-drop reordering for features within category
- Global "Save Features" persists all changes; data stored in hidden containers then serialized to options

### Frontend
- `wizard.js`: tiles organized by category; click or drag to add to drop zone; pricing computed client-side using localized options
- Sticky header adjustment: JS reads computed header height to offset drop zone
- Interaction tracking sends events via AJAX

### Migrations
- `fix_missing_ids()`: ensures category/feature IDs exist; adds missing `order` fields
- `maybe_migrate_packages()`: converts legacy `packages` array into features under "Page Packages" category (compulsory)

### Important Notes
- No separate "Page Packages" table; use a compulsory category instead
- Database option key: `wp_configurator_options` (do not remove old `packages` until migration confirmed)
- Do not put PHP inside `.js` files; use `wp_localize_script` or REST/AJAX endpoints
- Always regenerate ZIP and bump version after updates

## Current Version
- **3.2.9** (GPLv3 licensed, with donation support)

## Common Gotchas
- Adding a feature requires a category selected and enabled checkbox.
- Category IDs must be unique; auto-generated but editable for existing categories.
- When deleting a category, features with that `category_id` become orphaned (hidden until reassigned).
- Order fields are auto-updated on DnD but can also be manually edited.

## Testing Checklist
- Admin: add/edit/delete categories; drag tabs; verify features filter per tab; ensure Save persists.
- Admin: reorder features, change category, edit descriptions; verify order updates.
- Frontend: check tile display, billing badges, totals (one-time vs monthly equivalent), sticky header spacing.
- Migrations: fresh install vs. upgrade from old data.
