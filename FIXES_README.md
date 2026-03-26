# Fixes Applied - v3.5.3-dev

## Issues Addressed

### 1. Missing Feature After Category Edit
**Problem:** When editing a category (changing its ID or name), features belonging to that category would disappear from the category on the frontend. They were being orphaned and reassigned to the first category.

**Root Cause:** The `original_id` field (used to track category ID changes) was being stripped from the data before the sanitization process. The `repair_data_integrity` function had logic to handle ID changes via `original_id`, but it was never populated. Additionally, category ID changes weren't being propagated to features.

**Files Modified:**
- `includes/class-settings-manager.php`

**Changes:**

1. **Preserve `original_id` in categories:**
   - `sanitize_categories()` now includes `'original_id' => sanitize_text_field($cat['original_id'] ?? '')` in the returned array
   - This ensures the original ID is preserved through the sanitization process

2. **Enhanced `repair_data_integrity()`:**
   - Added `$id_changes` mapping: Scans all categories to build a map of `original_id => final id` for any category whose ID changed (either by user edit or via duplicate resolution)
   - Applied this mapping to features **before** orphan checks: After deduplicating feature IDs, features with `category_id` matching an old category ID are updated to the new ID
   - Cleanup: Unset `original_id` from all categories before returning (so it's not stored in the database)

3. **Simplified `validate_options()`:**
   - Removed the early ID propagation logic (lines 75-104 in original) that attempted to handle ID changes before sanitization
   - All ID remapping now happens in `repair_data_integrity()`, which is cleaner and handles all edge cases including duplicate resolution

**Result:** Features now correctly stay in their category even when the category ID is edited.

---

### 2. Invalid HTML Nesting in Category Tabs
**Problem:** The action buttons (duplicate, edit, delete) were rendered as separate elements outside the tab, appearing disconnected from the tab.

**Root Cause:** The template `templates/admin/tabs/categories-features.php` used nested `<button>` elements (buttons inside a button), which is invalid HTML. Browsers auto-correct this by closing the parent button before the nested buttons, causing them to render separately.

**Files Modified:**
- `assets/js/admin.js` (renderCategoryTabs function and event handlers)
- `assets/css/admin.css` (updated selectors and added wrapper styles)

**Changes:**

1. **Restructured HTML generation in `renderCategoryTabs()`:**
   - Old: Single `<button class="category-tab">` containing icon, name, count, badge, **and three nested action buttons**
   - New: `<div class="category-tab-item">` wrapper containing:
     - `<button class="category-tab">` (main tab button with icon, name, count, badge)
     - Three sibling action buttons: `<button class="tab-clone-btn">`, `<button class="tab-edit-btn">`, `<button class="tab-delete-btn">`

2. **Updated CSS selectors:**
   - Changed `.category-tab .tab-edit-btn, .category-tab .tab-delete-btn, .category-tab .tab-clone-btn` to `.category-tab-item .tab-edit-btn, .category-tab-item .tab-delete-btn, .category-tab-item .tab-clone-btn`
   - Added style for wrapper: `.category-tab-item { display: inline-flex; align-items: center; }`

3. **Updated event handlers:**
   - Edit button: `var $tab = $(this).siblings('.category-tab');` (was `.closest('.category-tab')`)
   - Duplicate button: Same change
   - Delete button: Same change, also preserves `$tab` reference for `editWasActive` logic

4. **Updated drag-and-drop logic:**
   - Drag start: Stores the wrapper element (`$draggedTab = $wrapper`) instead of just the tab button
   - Dragover: Uses `.closest('.category-tab-item')` to get wrapper, measures wrapper dimensions
   - Drop: Changed selector from `.category-tab, .category-tab-placeholder` to `.category-tab-item, .category-tab-placeholder`

**Result:** Action buttons now appear inline with the tab, properly grouped, and drag-and-drop still works correctly.

---

## Testing Checklist

After uploading the updated plugin:

1. **Clear browser cache** (hard refresh: Cmd+Shift+R or Ctrl+F5)

2. **Test Category Tab Display:**
   - Go to Dashboard → ATP Configurator → Categories & Features
   - Category tabs should show: [icon] [name] [count] [★ if compulsory] with duplicate/edit/delete buttons visually attached to the right of the tab
   - Buttons should not appear separated or outside the tab

3. **Test Category Edit & Feature Association:**
   - Create a new category (e.g., "Test Category") if needed
   - Create a new feature in that category
   - Edit the category (change name and/or ID)
   - Save Changes
   - On the frontend, verify the feature still appears under that category

4. **Test Drag-and-Drop Reordering:**
   - Drag category tabs to reorder them
   - Verify order persists after saving

5. **Test Other Functionality:**
   - Add/edit/delete features
   - All modals should work
   - No console errors (check browser dev tools)

---

## Technical Notes

- Version in code should be bumped to **3.5.3** before release (in `wp-configurator-wizard.php` header line 5 and `VERSION` constant around line 22)
- Database schema unchanged
- No data migrations required
- `original_id` field is stripped before saving, so it won't bloat the database
- The repair function will automatically fix any orphaned features from previous versions on next save

---

## Files Changed (Summary)

```
wp-configurator-wizard/includes/class-settings-manager.php
  - Modified sanitize_categories() to preserve original_id
  - Modified repair_data_integrity() to add id_changes mapping and apply it to features
  - Modified validate_options() to remove early ID propagation
  - Added cleanup of original_id before return

wp-configurator-wizard/assets/js/admin.js
  - Modified renderCategoryTabs() to use wrapper structure
  - Updated click handlers for tab-edit-btn, tab-clone-btn, tab-delete-btn
  - Updated drag-and-drop handlers to work with wrappers

wp-configurator-wizard/assets/css/admin.css
  - Updated selectors for action buttons
  - Added .category-tab-item wrapper style
```

---

**Date:** 2025-03-26
**Author:** Claude (Anthropic)
**Context:** v3.5.3 development
