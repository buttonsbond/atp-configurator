# Admin.js Modularization - Phased Refactoring Plan

**Goal**: Split the 1599-line `admin.js` into manageable, focused modules while maintaining 100% functionality.

**Current State**: Single `admin.js` contains all admin JavaScript - tab navigation, category/feature management, emoji picker, import/export, settings, state persistence. Hard to maintain.

**Target**: Logical separation into ~7 focused files, each 50-200 lines, with clear dependencies.

**Strategy**: **Phased extraction** - one self-contained piece at a time, test after each step. If something breaks, we know exactly which change caused it.

---

## 📋 Principles
- ✅ Each step is atomic and reversible
- ✅ Test thoroughly before proceeding
- ✅ Use proper WordPress script dependencies (not global window hacks)
- ✅ Keep `categories`/`features`/`activeCategoryId` in main `admin.js` closure (they're tightly coupled)
- ✅ Only extract modules that don't need to mutate core state

---

## **PHASE 1: Foundation - Utilities & State**

### Step 1.1: Create `admin-common.js` for Shared Utilities
**Why**: `saveAdminState`, `loadAdminState`, and `showToast` are used by multiple modules. Extract them first.

**Files to create/modify**:
- ✨ Create: `assets/js/admin/admin-common.js`
- ✏️ Modify: *none yet*

**Actions**:
1. In `admin-common.js`, copy these functions (currently lines 7-54 + 937-963 in admin.js):
   - `saveAdminState(state)`
   - `loadAdminState()`
   - `showToast(message, type)`
2. Wrap them in an IIFE that exposes globally:
   ```javascript
   (function() {
       var STORAGE_KEY = 'wp_configurator_admin_state';

       function saveAdminState(state) { ... }
       function loadAdminState() { ... }
       function showToast(message, type) { ... }

       // Expose globally
       window.saveAdminState = saveAdminState;
       window.loadAdminState = loadAdminState;
       window.showToast = showToast;

       console.log('✅ admin-common.js loaded');
   })();
   ```
3. Remove the old `showToast` function from `admin.js` (line 937)
4. Remove `saveAdminState` and `loadAdminState` from `admin.js` (lines 7-23)

**Test**:
- [ ] Reload admin page, open console
- [ ] `typeof saveAdminState` → 'function'
- [ ] `typeof loadAdminState` → 'function'
- [ ] `typeof showToast` → 'function'
- [ ] Call `showToast('test')` → toast appears
- [ ] No console errors

**If fails**: Revert `admin.js` changes, debug `admin-common.js`

---

### Step 1.2: Update Asset Manager to Enqueue `admin-common.js`
**Why**: We need `admin-common.js` to load before any module that uses its utilities.

**Files to modify**:
- ✏️ Modify: `includes/class-asset-manager.php`

**Actions**:
1. Add new script enqueue in `enqueue_admin_assets()` method (before the main admin.js):
   ```php
   wp_enqueue_script(
       'wp-configurator-admin-common',
       $admin_js_path . 'admin-common.js',
       array('jquery'),
       $this->version,
       true
   );
   ```
2. Keep existing main `admin.js` enqueue as is for now (will add dependencies later)

**Test**:
- [ ] View page source, confirm `<script src="...admin-common.js">` appears
- [ ] It loads BEFORE `admin.js`
- [ ] Console: `✅ admin-common.js loaded` appears
- [ ] No 404 errors

**If fails**: Check path, clear caches

---

## **PHASE 2: Extract Isolated Modules** (Low Risk)

These modules are **self-contained** - they either:
- Only read data (no mutation)
- Attach event handlers to their own DOM elements
- Don't call functions defined in other modules

---

### Step 2.1: Extract Emoji Picker → `admin-emoji.js`
**Why**: Emoji picker code (~150 lines) is isolated - only deals with popup UI and input population. No dependency on `categories`/`features`.

**Current location in admin.js**: Lines 254-400 (approx)

**Files**:
- ✨ Create: `assets/js/admin/admin-emoji.js`
- ✏️ Modify: `includes/class-asset-manager.php` (enqueue new file)

**Actions**:
1. Copy the entire emoji picker section from `admin.js`:
   - Click handler for `.open-emoji-picker`
   - `categorizeAllEmojis()` function
   - Emoji tab switching
   - Emoji click selection handler
   - Any emoji-related helper functions
2. Paste into `admin-emoji.js`, wrap in:
   ```javascript
   jQuery(document).ready(function($) {
       // all emoji code here
   });
   ```
3. Remove that entire section from `admin.js` (replace with comment: `// Emoji picker - see admin-emoji.js`)
4. In `class-asset-manager.php`, enqueue:
   ```php
   wp_enqueue_script(
       'wp-configurator-admin-emoji',
       $admin_js_path . 'admin-emoji.js',
       array('wp-configurator-admin-common'), // depends on common for showToast? maybe not
       $this->version,
       true
   );
   ```

**Test**:
- [ ] Reload admin page
- [ ] Open any modal with emoji picker (Category edit, Feature edit)
- [ ] Click emoji picker button → popup appears with categorized tabs
- [ ] Click different emoji category tab → emojis filter
- [ ] Click an emoji → input field populates, popup closes
- [ ] No console errors

**If fails**: Check event delegation (`.open-emoji-picker`), ensure emoji modal HTML exists

---

### Step 2.2: Extract Miscellaneous Settings → `admin-settings.js`
**Why**: Settings tab code (~150 lines) is already isolated. Contains range sliders, IP detection, accordion toggle, collapsible headers.

**Current location**: Lines 1446-1599 (the final `jQuery(document).ready(function($) {` block)

**Files**:
- ✨ Create: `assets/js/admin/admin-settings.js`
- ✏️ Modify: `includes/class-asset-manager.php`

**Actions**:
1. Copy everything from line 1446 to end of file (1599)
2. Paste into `admin-settings.js`, wrap in `jQuery(document).ready(...)`
3. Remove from `admin.js` (replace with comment: `// Miscellaneous Settings - see admin-settings.js`)
4. Enqueue in `class-asset-manager.php`:
   ```php
   wp_enqueue_script(
       'wp-configurator-admin-settings',
       $admin_js_path . 'admin-settings.js',
       array('wp-configurator-admin-common'),
       $this->version,
       true
   );
   ```

**Test**:
- [ ] Reload admin, go to Miscellaneous Settings tab
- [ ] Range sliders: dragging updates the numeric display
- [ ] "Detect My IP" button: fetches IP (or shows error), field populates
- [ ] "Collapsible Categories" checkbox: toggles accordion setting enable/disable
- [ ] Donors section header: click → collapses/expands
- [ ] Interactions section header: click → collapses/expands
- [ ] State persists after reload (collapse states)
- [ ] Refresh button reloads page
- [ ] No console errors

**If fails**: Check that `saveAdminState`/`loadAdminState` are globally available

---

### Step 2.3: Extract Tab Navigation → `admin-tabs.js`
**Why**: Tab switching (~30 lines) is completely independent. Also include the `restoreState()` function that's duplicated.

**Current location**: Lines 25-44 (first `jQuery(function($)` block) PLUS the `restoreState()` IIFE that's currently in the misc settings block (lines 1543-1585).

**Files**:
- ✨ Create: `assets/js/admin/admin-tabs.js`
- ✏️ Modify: `class-asset-manager.php`

**Actions**:
1. In `admin-tabs.js`, create:
   ```javascript
   jQuery(function($) {
       // Tab switching (lines 25-44 from admin.js)
       $('.nav-tab-wrapper .nav-tab').on('click', function() {
           // ... existing code
       });
   });

   // restoreState IIFE (lines 1543-1585 from admin.js)
   (function restoreState() {
       var state = loadAdminState();
       // ... restore logic
   })();
   ```
2. In `admin.js`:
   - Delete lines 25-44 (first ready block)
   - Delete lines 1543-1585 (the restoreState IIFE in misc block) - but we already removed misc block in Step 2.2, so these lines may already be gone. If not, delete them.
3. Enqueue `admin-tabs.js`:
   ```php
   wp_enqueue_script(
       'wp-configurator-admin-tabs',
       $admin_js_path . 'admin-tabs.js',
       array('wp-configurator-admin-common'),
       $this->version,
       true
   );
   ```

**Test**:
- [ ] Reload admin page
- [ ] Click different main tabs (Categories, Miscellaneous, Quote Requests, Stats, System Status) → content switches
- [ ] Active tab is remembered after reload
- [ ] Header collapse/expand still works (we haven't moved that yet - it stays in admin.js)
- [ ] No console errors

**If fails**: Check that `loadAdminState`, `saveAdminState` are globally available

---

## **PHASE 3: Prepare Global State Sharing** (Medium Risk)

**Problem**: Import/Export and potentially other modules need to read `categories` and `features`. Currently these are **closure variables** in `admin.js`, inaccessible to other files.

**Solution**: Expose them via `window.WPConfiguratorAdmin` object that all modules can read.

---

### Step 3.1: Create Global State Container in `admin-common.js`
**Goal**: Define `window.WPConfiguratorAdmin` as a shared namespace.

**Files**:
- ✏️ Modify: `assets/js/admin/admin-common.js`

**Actions**:
1. At bottom of `admin-common.js` (outside any function), add:
   ```javascript
   // Global state container for admin modules
   window.WPConfiguratorAdmin = window.WPConfiguratorAdmin || {};
   console.log('✅ WPConfiguratorAdmin global container initialized');
   ```
2. Nothing else in `admin-common.js` needs to change

**Test**:
- [ ] Reload admin, open console
- [ ] `window.WPConfiguratorAdmin` should be an object `{}`
- [ ] No errors

---

### Step 3.2: Populate Global State from `admin.js`
**Goal**: At the end of `admin.js` initialization, assign `categories`, `features`, `activeCategoryId` to the global container.

**Files**:
- ✏️ Modify: `assets/js/admin/admin.js`

**Actions**:
1. In `admin.js`, find the main `jQuery(document).ready(function($) {` block (the big one that starts around line 47)
2. Scroll to the very end, just before the closing `});` of that block.
3. Add these lines before the end:
   ```javascript
   // Expose data globally for other modules (Import/Export, etc.)
   window.WPConfiguratorAdmin.categories = categories;
   window.WPConfiguratorAdmin.features = features;
   window.WPConfiguratorAdmin.activeCategoryId = activeCategoryId;
   window.WPConfiguratorAdmin.exportNonce = exportNonce;
   console.log('🌐 Global state exposed:', categories.length, 'categories,', features.length, 'features');
   ```
4. Ensure that at this point in the code, `categories`, `features`, `activeCategoryId`, and `exportNonce` are all defined variables in scope (they should be).

**Test**:
- [ ] Reload admin, open console
- [ ] `window.WPConfiguratorAdmin.categories` should be an array with your categories
- [ ] `window.WPConfiguratorAdmin.features` should be an array with your features
- [ ] `window.WPConfiguratorAdmin.activeCategoryId` should be a string
- [ ] Log message appears: "🌐 Global state exposed: X categories, Y features"
- [ ] No console errors

---

## **PHASE 4: Extract Import/Export** (Medium Risk)

Now that we have global state, we can extract Import/Export which needs to read `categories` and `features`.

---

### Step 4.1: Extract Import/Export → `admin-import-export.js`
**Why**: Import/Export needs to access `categories`/`features` to bundle them in export and parse imports.

**Current location**: Lines 1320-1444 in `admin.js`

**Files**:
- ✨ Create: `assets/js/admin/admin-import-export.js`
- ✏️ Modify: `includes/class-asset-manager.php`

**Actions**:
1. Copy lines 1320-1444 from `admin.js`
2. In `admin-import-export.js`, wrap in `jQuery(document).ready(function($) { ... });`
3. **Important**: Change all references to `categories`, `features`, `wpConfiguratorAdmin`, `exportNonce` to use the global:
   - `categories` → `window.WPConfiguratorAdmin.categories`
   - `features` → `window.WPConfiguratorAdmin.features`
   - `wpConfiguratorAdmin.settings` → `window.WPConfiguratorAdmin.settings`
   - `exportNonce` → `window.WPConfiguratorAdmin.exportNonce`
4. Remove the original section from `admin.js` (lines 1320-1444) - replace with comment
5. Enqueue in `class-asset-manager.php`:
   ```php
   wp_enqueue_script(
       'wp-configurator-admin-import-export',
       $admin_js_path . 'admin-import-export.js',
       array('wp-configurator-admin-common', 'wp-configurator-admin-tabs'), // needs both
       $this->version,
       true
   );
   ```

**Test**:
- [ ] Reload admin page
- [ ] Click "Export Settings" → downloads JSON file with categories, features, settings
- [ ] Click "Import Settings" → modal opens
- [ ] Select a JSON file → preview shows counts
- [ ] Click "Import" → data updates, page reloads with new data
- [ ] No console errors

**If fails**: Check that global state is available (`window.WPConfiguratorAdmin` exists), verify nonce name

---

## **PHASE 5: Main Admin.js Cleanup**

At this point, `admin.js` should have:
- Core category/feature management (CRUD, drag-drop, grid rendering)
- Bulky emoji picker removed (~150 lines)
- Misc settings removed (~150 lines)
- Import/export removed (~125 lines)
- Tab navigation removed (~30 lines)
- Utilities removed (~50 lines)
- **Expected size**: ~800-1000 lines (down from 1599)

---

### Step 5.1: Organize `admin.js` into Internal Sections
**Goal**: Add clear section comments and group related functions for readability.

**Files**:
- ✏️ Modify: `assets/js/admin/admin.js`

**Actions**:
1. Add section headers with comments:
   ```javascript
   // ============================================
   // Admin Core - Category & Feature Management
   // ============================================
   ```
2. Group functions by module:
   - Category Tabs Rendering & Events
   - Feature Grid Rendering & Events
   - Modal Management (Category Modal, Feature Modal)
   - Drag and Drop (Category tabs, Feature tiles)
   - Bulk Actions & Selection
   - Undo/Redo
   - Search & Filter
3. Ensure no orphaned code remains from extracted modules
4. Remove any duplicate `initCategoryTabs()` or similar if both old and new exist

**Test**:
- [ ] All category/feature functionality works as before
- [ ] No duplicate handlers (e.g., clicking tab triggers once, not twice)
- [ ] Console: no "already defined" warnings
- [ ] No functionality lost

---

### Step 5.2: Final Asset Manager Cleanup
**Goal**: Update `admin.js` enqueue to depend on all extracted modules.

**Files**:
- ✏️ Modify: `includes/class-asset-manager.php`

**Actions**:
1. Find the `wp_enqueue_script('wp-configurator-admin', ...)` call
2. Update dependencies array to include all new modules:
   ```php
   array(
       'jquery',
       'wp-configurator-admin-common',
       'wp-configurator-admin-tabs',
       'wp-configurator-admin-settings',
       'wp-configurator-admin-emoji',
       'wp-configurator-admin-import-export'
   )
   ```
3. Remove any old `wp_add_inline_script` that set `window.categories` etc. (line 231-236) - we now set this in `admin.js` itself (Step 3.2)
4. The `wp_localize_script` for `wpConfiguratorAdmin` can stay - it provides the initial data. But our global state will mirror it.

**Test**:
- [ ] View page source, check script load order: common → tabs → settings → emoji → import-export → admin (main)
- [ ] All scripts load (200 OK)
- [ ] All functionality works (tabs, categories, features, settings, emoji, import/export)
- [ ] Console: no errors or missing function warnings

---

## **PHASE 6: Verification & Polish**

---

### Step 6.1: Comprehensive Testing Checklist

**Go through each admin feature systematically**:

#### Main Tab Navigation
- [ ] Click each main tab (Categories, Miscellaneous, Quote Requests, Stats, System Status)
- [ ] Verify tab switches smoothly
- [ ] Reload page → same tab is still active

#### Categories & Features Tab
- [ ] Category tabs display with correct names, icons, colors
- [ ] Feature counts on tabs are correct (not 0)
- [ ] Click category tab → features grid shows that category's features
- [ ] **Add Category**: Modal opens, fill fields (name, icon, color, compulsory), save → appears in tab list
- [ ] **Edit Category**: Click edit icon, change name/color/compulsory, save → updates
- [ ] **Delete Category**: Click delete, confirm → removed (can't delete last category)
- [ ] **Drag Category**: Drag tab to reorder → order updates, toast appears
- [ ] **Add Feature**: Click "Add Feature", modal opens, fill fields, save → appears in grid
- [ ] **Edit Feature**: Click tile, modal opens with current values, edit, save → updates grid
- [ ] **Delete Feature**: Click delete in modal, confirm → removed
- [ ] **Drag Feature**: Drag tile to reorder within same category → order updates
- [ ] **Enable/Disable Toggle**: Click toggle button → tile gets disabled style (gray), toggle shows ✓/○
- [ ] **Bulk Selection**: Click checkbox on multiple tiles → toolbar appears with count
- [ ] **Bulk Actions**: With tiles selected, test Enable All, Disable All, Delete Selected, Move to Category
- [ ] **Search**: Type in search box → filters tiles by name
- [ ] **Filter Enabled/Disabled**: Dropdown shows all/enabled/disabled only
- [ ] **Undo**: After an action, click Undo → restores previous state, shows toast
- [ ] **Modal Close**: Click cancel, backdrop, or X → modal closes without saving

#### Miscellaneous Settings Tab
- [ ] All range sliders show current value as you drag
- [ ] "Detect My IP" button: fetches IP or shows error, field populates
- [ ] "Collapsible Categories" checkbox: toggles, accordion setting enables/disables
- [ ] Each collapsible section (Email, Frontend, Display, Tracking, Advanced) toggles open/closed
- [ ] Sections start collapsed → click to expand
- [ ] Reload → collapsed state persists
- [ ] Live preview: Change "Frontend Title", see preview update in real-time
- [ ] Toggle "Enable Live Preview" on/off works
- [ ] Save Settings → "Settings saved successfully" toast appears
- [ ] Settings persist after reload

#### Emoji Picker (in any modal)
- [ ] Click emoji button → picker opens below button
- [ ] Category tabs (All, Smileys, Objects, etc.) filter emojis
- [ ] Click emoji → input field populates, picker closes
- [ ] Click outside picker → closes

#### Import/Export
- [ ] Click "Export Settings" → downloads JSON file
- [ ] Click "Import Settings" → modal opens
- [ ] Choose JSON file → preview shows version, date, counts
- [ ] Select what to import (categories, features, settings)
- [ ] Click Import → success message, page reloads with new data

#### State Persistence
- [ ] Active main tab remembered after reload
- [ ] Active category tab remembered after reload
- [ ] Header collapsed/expanded state remembered
- [ ] Donors section collapsed state remembered
- [ ] Interactions section collapsed state remembered

#### Console & Network
- [ ] No red JavaScript errors in console
- [ ] No 404s for script files (all assets load)
- [ ] No PHP warnings/notices (check Network tab for admin-ajax.php responses if any)

---

### Step 6.2: Create Development ZIP
**Goal**: Package the refactored code for testing.

**Actions**:
1. Update version in `wp-configurator-wizard.php`:
   - Header: `* Version: 3.4.16-dev`
   - Constant: `const VERSION = '3.4.16-dev';`
2. Create ZIP: `zip -r wp-configurator-wizard-v3.4.16-dev.zip wp-configurator-wizard -x "*.git*"`
3. Verify ZIP includes all new JS files in `assets/js/admin/`

---

### Step 6.3: Update Project Documentation
**Files**:
- ✏️ Modify: `TODO.md` (add note about modularization)
- ✏️ Modify: `REFACTORINGTODO.md` (mark this admin.js work as completed or create new section)

**Actions**:
1. In `TODO.md`, under "Completed" or recent changes, add:
   ```
   - **v3.4.16-dev**: Admin JavaScript modularization - split admin.js into focused modules:
     - admin-common.js (utilities, state persistence, toasts)
     - admin-tabs.js (main navigation, state restoration)
     - admin-settings.js (miscellaneous settings)
     - admin-emoji.js (emoji picker)
     - admin-import-export.js (settings import/export)
     - Core category/feature management remains in admin.js but is better organized
   ```
2. Optionally add a note to `REFACTORINGTODO.md` about this being done

---

## **ROLLBACK PLAN**

**If any step fails**:
1. Revert the changed files to their previous state (from git)
2. Investigate the issue in a test environment
3. Either fix or skip that extraction for now
4. Continue with remaining steps if safe

**Known risks**:
- Global state timing: `window.WPConfiguratorAdmin` must be set before other modules read it. If not, we may need to add a simple event or check-and-retry.
- Duplicate event handlers: If both old and new code remain, handlers fire twice. Carefully delete all old code when extracting.
- Missing dependencies: If a module needs something from another, adjust enqueue order.

---

## **SUCCESS CRITERIA**

- ✅ `admin.js` reduced from 1599 lines to ~800-1000 lines
- ✅ 5+ new modular files created (`admin-common.js`, `admin-tabs.js`, `admin-settings.js`, `admin-emoji.js`, `admin-import-export.js`)
- ✅ All admin functionality works identically to before
- ✅ Zero JavaScript errors in console
- ✅ Code is easier to understand and maintain
- ✅ Each file has single, clear responsibility
- ✅ Ready for future extraction of remaining pieces (search, bulk actions)

---

## **ESTIMATED TIME**
- Phase 1: 20 minutes
- Phase 2: 45 minutes
- Phase 3: 15 minutes
- Phase 4: 20 minutes
- Phase 5: 10 minutes
- Phase 6: 20 minutes
- **Total**: ~2 hours (with testing)

---

**Start with Step 1.1. After completing each step, mark it done and report back before proceeding to the next. This ensures we catch any issues early.**
