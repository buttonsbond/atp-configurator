# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working on this repository.

## Session Management
- **Always** read `TODO.md` at the start of every session to understand the current state.
- **Update** `TODO.md` after completing a sub-task (mark `[x]`) or if project state changes.
- **Update** 'README.md' before making any commits and releases.

## Context Management Rules
- **Conciseness**: Be concise and direct. Provide only the necessary information, code changes, or summaries. Avoid conversational filler.
- **Tool Output**: Do not repeat entire file contents when providing a fix. Only show the changed lines (diff) or the relevant function.
- **History**: When you have completed a task or reached a natural checkpoint, summarize the key decisions and discard the verbose back-and-forth.
- **Noisy Tools**: If a tool (like `git status` or a linter) returns a lot of text, summarize the essential output rather than pasting the whole log.

## Token Saving Rules
- **Lazy Loading**: Only read files needed for the current task.
- **Brief Responses**: Concise explanations; avoid resending large files.

## Auto-Compact Instructions
When compacting, always preserve:
- Current project structure
- Active task goals
- All file modifications with exact paths
- Git status/Branch context.
- Discard: Original file contents, detailed tool execution logs, and conversational text.

## Response Guidelines
- Use short, descriptive commit messages.
- Prefer code diffs over full file re-writes.
- If an operation is successful, just say "Done".

## Plugin Development

### Development Workflow
- Edit files directly in `wp-configurator-wizard/`.
- **Git Workflow**: Do NOT automatically commit or push changes to GitHub unless explicitly instructed by the user. Development can proceed without committing.
- **When Ready for Release/Commit**:
  1. Ask user to Test changes thoroughly on WordPress site - they'll need a new zip file for this
  2. Bump version in two places:
     - Plugin header (`wp-configurator-wizard.php`, line 5): `* Version: X.Y.Z`
     - Constant `VERSION` (around line 22 in same file)
  3. Package ZIP: `zip -r wp-configurator-wizard.zip wp-configurator-wizard -x "*.git*"`
  4. Commit: `git add -A && git commit -m "Description"`
  5. Push: `git push origin main`
  6. Tag: `git tag -a vX.Y.Z -m "Version X.Y.Z" && git push origin vX.Y.Z`
- **Testing**: Use shortcode `[wp_configurator_wizard]` on a WordPress page; check admin UI at Dashboard → ATP Configurator.
- Clear browser cache after CSS/JS changes; check console for errors.
- Always try and keep code modular for easier future maintenance

### Useful Commands
- PHP CLI available for linting and testing:
  - `php -l wp-configurator-wizard.php` (check syntax)
  - `php -l includes/class-*.php` (lint individual classes)
  - `php -d display_errors=1 wp-configurator-wizard.php` (debug plugin bootstrap)
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
└── (no README.md here - documentation at repository root)
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
- **3.5.2** (stable - GPLv3 licensed)

## Development Workflow Notes
- **Current State**: Version 3.5.2 released and stable.
- **Next**: Continue development for v3.5.3 following the standard workflow.
- **Workflow**: All changes are made locally; debugging happens on the test site; documentation updated incrementally.
- **When Ready**: Follow the "When Ready for Release/Commit" steps below (version bump already done in code, just test and package).

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
