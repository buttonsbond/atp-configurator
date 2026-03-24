// Tab switching
console.log('✅ admin.js script loaded (top level)');

jQuery(function($) {
	console.log('✅ jQuery ready fired');
	try {
		$('.nav-tab-wrapper .nav-tab').on('click', function() {
			var tabId = $(this).data('tab');
			console.log('🖱️ Tab clicked:', tabId);
			$('.nav-tab-wrapper .nav-tab').removeClass('nav-tab-active');
			$(this).addClass('nav-tab-active');
			$('.wp-configurator-tab-content').removeClass('active');
			$('#' + tabId).addClass('active');

			// Save active tab to localStorage
			var state = loadAdminState();
			state.activeTab = tabId;
			saveAdminState(state);
		});
	} catch(e) {
		console.error('❌ Error setting up tab switching:', e);
	}
});

// Admin functionality
jQuery(document).ready(function($) {
	// Defensive: ensure wpConfiguratorAdmin exists
	if (typeof wpConfiguratorAdmin === 'undefined') {
		console.error('❌ wpConfiguratorAdmin is not defined. Admin functionality will not work.');
		wpConfiguratorAdmin = {};
	}
	console.log('🔧 wpConfiguratorAdmin object:', wpConfiguratorAdmin);
	console.log('📂 Categories:', wpConfiguratorAdmin.categories);
	console.log('📦 Features:', wpConfiguratorAdmin.features);

	var categoryIndex = wpConfiguratorAdmin.categoryIndex || 0;
	var featureIndex = wpConfiguratorAdmin.featureIndex || 0;
	var categories = Array.isArray(wpConfiguratorAdmin.categories) ? wpConfiguratorAdmin.categories : [];
	var features = Array.isArray(wpConfiguratorAdmin.features) ? wpConfiguratorAdmin.features : [];
	var activeCategoryId = $('.category-tab.active').data('category') || (categories.length ? categories[0].id : '');
	var exportNonce = wpConfiguratorAdmin.exportNonce || '';

	console.log('✅ Initialized with', categories.length, 'categories and', features.length, 'features');

	// Set original_id for each category to track ID changes
	categories.forEach(function(cat) {
		cat.original_id = cat.id;
	});

	// Initialize feature indices and order defaults
	features.forEach(function(feat, i) {
		if (feat.index === undefined) feat.index = i;
		if (feat.order === undefined) feat.order = i + 1;
		if (feat.sku === undefined) feat.sku = '';
	});

	// ===== NEW ENHANCEMENTS: State & Undo =====
	// Track selected feature indices for bulk operations
	var selectedFeatures = new Set();
	var lastAction = null;
	var undoStack = [];

	// Keyboard shortcuts
	$(document).on('keydown', function(e) {
		// Ctrl/Cmd + N: Add new category (when focus not in input)
		if ((e.ctrlKey || e.metaKey) && e.key === 'n') {
			var tag = document.activeElement.tagName;
			if (tag !== 'INPUT' && tag !== 'TEXTAREA' && tag !== 'SELECT') {
				e.preventDefault();
				$('#add-category').click();
			}
		}
		// Ctrl/Cmd + F: Focus search
		if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
			var tag = document.activeElement.tagName;
			if (tag !== 'INPUT' && tag !== 'TEXTAREA' && tag !== 'SELECT') {
				e.preventDefault();
				$('#search-features').focus();
			}
		}
		// Escape: Clear filters or deselect all
		if (e.key === 'Escape') {
			$('#search-features').val('');
			$('#filter-billing').val('');
			$('#filter-enabled').prop('checked', true);
		}
	});
	// ===========================================


	// Initial render: Build category tabs and feature grid
	renderCategoryTabs();
	// Determine active category after render (in case initial active was from PHP template)
	var $activeTab = $('#category-tabs-container .category-tab.active');
	if ($activeTab.length) {
		activeCategoryId = $activeTab.data('categoryId');
	} else if (categories.length) {
		activeCategoryId = categories[0].id;
		$('#category-tabs-container .category-tab').first().addClass('active');
	}
	renderFeaturesGrid(activeCategoryId);

	// ===========================================

	// Add Category button - open modal
	$('#add-category').on('click', function() {
		$('#category-edit-modal h3').text('Add Category');
		$('#edit-category-index').val('new');
		$('#edit-category-id').val('');
		$('#edit-category-name').val('');
		$('#edit-category-icon').val('');
		$('#edit-category-color').val('#6366f1');
		updateColorPreview();
		$('#edit-category-compulsory').prop('checked', false);
		$('#edit-category-info').val('');
		$('#category-edit-modal').addClass('adding-mode is-visible');
	});

	// Helper: create a placeholder for tab drag-and-drop
	var $categoryPlaceholder = $('<div class="category-tab-placeholder" style="display:flex;align-items:center;gap:6px;padding:8px 16px;font-size:14px;background:#f6f7f7;border:1px dashed #2271b1;border-radius:4px;height:36px;box-sizing:border-box;"></div>');
	$categoryPlaceholder.on('dragover', function(e) { e.preventDefault(); });
	var $draggedTab = null;
	var editingCategoryIndex = null;
	var editWasActive = false;

	// Cancel modal button
	$('#cancel-category-edit').on('click', function() {
		$('#category-edit-modal').removeClass('is-visible adding-mode');
		editingCategoryIndex = null;
	});

	// Save modal button (add/edit)
	$('#save-category-edit').on('click', function() {
		var id = $('#edit-category-id').val().trim();
		var name = $('#edit-category-name').val().trim();
		var icon = $('#edit-category-icon').val().trim();
		var color = $('#edit-category-color').val().trim();
		var compulsory = $('#edit-category-compulsory').is(':checked') ? 1 : 0;
		var info = $('#edit-category-info').val().trim();

		if (!name) {
			alert('Category name is required.');
			return;
		}

		// Auto-generate ID if empty
		if (!id) {
			id = name.toLowerCase().replace(/\s+/g, '-').replace(/[^a-z0-9-]/g, '');
		}

		// Ensure ID uniqueness
		var baseId = id;
		var counter = 1;
		for (var i = 0; i < categories.length; i++) {
			if (editingCategoryIndex !== null && i === editingCategoryIndex) continue;
			if (categories[i].id === id) {
				id = baseId + '-' + counter;
				counter++;
				i = -1;
			}
		}

		var isAdding = (editingCategoryIndex === null);
		var oldId = null;
		var editIndex = editingCategoryIndex;

		if (isAdding) {
			categories.push({
				id: id,
				original_id: id,
				name: name,
				icon: icon,
				color: color,
				compulsory: compulsory,
				info: info,
				order: categories.length + 1
			});
		} else {
			oldId = categories[editingCategoryIndex].id;
			var originalId = categories[editingCategoryIndex].original_id || categories[editingCategoryIndex].id;
			categories[editingCategoryIndex] = {
				id: id,
				original_id: originalId,
				name: name,
				icon: icon,
				color: color,
				compulsory: compulsory,
				info: info,
				order: categories[editingCategoryIndex].order
			};
		}

		// Normalize order
		categories.forEach(function(cat, idx) {
			cat.order = idx + 1;
		});

		renderCategoryTabs();
		updateCategoriesHiddenInputs();

		var $container = $('#category-tabs-container');

		if (isAdding) {
			$container.find('.category-tab[data-category-id="' + id + '"]').addClass('active').siblings().removeClass('active');
			activeCategoryId = id;
		} else if (editWasActive) {
			$container.find('.category-tab').removeClass('active');
			$container.find('.category-tab[data-category-id="' + id + '"]').addClass('active');
			activeCategoryId = id;
		}
		renderFeaturesGrid(activeCategoryId);

		showToast(isAdding ? 'Category added.' : 'Category saved.');

		$('#category-edit-modal').hide().removeClass('adding-mode');
		editingCategoryIndex = null;
		editWasActive = false;

		setTimeout(function() {
			$('#submit').trigger('click');
		}, 1000);
	});

	// Auto-generate category ID from name
	$('#edit-category-name').on('input', function() {
		if ($('#category-edit-modal').hasClass('adding-mode')) {
			var name = $(this).val().trim();
			var generated = name.toLowerCase().replace(/\s+/g, '-').replace(/[^a-z0-9-]/g, '');
			$('#edit-category-id').val(generated);
		}
	});

	// Update color preview when color changes
	function updateColorPreview() {
		var color = $('#edit-category-color').val();
		$('#color-preview').css('background-color', color);
	}
	$('#edit-category-color').on('input', updateColorPreview);
	// Initialize preview when modal opens
	$('#category-edit-modal').on('mouseenter', 'form', function() {
		updateColorPreview();
	});

	// Close modal on outside click
	$('#category-edit-modal').on('click', function(e) {
		if ($(e.target).is('#category-edit-modal')) {
			$(this).removeClass('is-visible adding-mode');
		}
	});

	// Close modal on Escape
	$(document).on('keydown.categoryEdit', function(e) {
		if (e.key === 'Escape' && $('#category-edit-modal').hasClass('is-visible')) {
			$('#category-edit-modal').removeClass('is-visible adding-mode');
		}
	});

	// Category tab click - switch active category
	$('#category-tabs-container').on('click', '.category-tab', function(e) {
		// Ignore if clicking on action buttons inside tab
		if ($(e.target).closest('.tab-edit-btn, .tab-delete-btn, .tab-clone-btn').length) {
			return;
		}
		var $tab = $(this);
		var categoryId = $tab.data('categoryId');
		if (!categoryId) return;

		// Update active tab
		$tab.addClass('active').siblings().removeClass('active');
		activeCategoryId = categoryId;

		// Clear selection when switching categories
		selectedFeatures.clear();
		updateBulkActionsUI();

		renderFeaturesGrid(categoryId);

		// Save active category to localStorage
		var state = loadAdminState();
		state.activeCategoryTab = categoryId;
		saveAdminState(state);
	});

	// Edit button handler
	$('#category-tabs-container').on('click', '.tab-edit-btn', function(e) {
		e.stopPropagation();
		var $tab = $(this).closest('.category-tab');
		var index = $(this).data('index');
		editingCategoryIndex = index;
		editWasActive = $tab.hasClass('active');
		var cat = categories[index];
		$('#category-edit-modal h3').text('Edit Category');
		$('#edit-category-index').val(index);
		$('#edit-category-id').val(cat.id);
		$('#edit-category-name').val(cat.name);
		$('#edit-category-icon').val(cat.icon);
		$('#edit-category-color').val(cat.color || '#6366f1');
		$('#edit-category-compulsory').prop('checked', cat.compulsory == 1);
		$('#edit-category-info').val(cat.info || '');
		updateColorPreview(); // set initial preview
		$('#category-edit-modal').addClass('is-visible');
	});

	// Duplicate category (clone)
	$('#category-tabs-container').on('click', '.tab-clone-btn', function(e) {
		e.stopPropagation();
		var $tab = $(this).closest('.category-tab');
		var index = $(this).data('index');
		var original = categories[index];
		if (!original) return;

		// Create clone
		var clone = $.extend({}, original);
		clone.id = original.id + '-copy';
		clone.name = original.name + ' (Copy)';
		clone.original_id = original.original_id || original.id;
		clone.order = categories.length + 1;
		if (!clone.color) clone.color = '#6366f1';

		// Add the cloned category
		categories.push(clone);

		// Also clone all features from this category
		var clonedFeatures = features.filter(function(f) { return f.category_id === original.id; }).map(function(feat, i) {
			var cloneFeat = $.extend({}, feat);
			cloneFeat.id = feat.id + '-copy';
			cloneFeat.name = feat.name + ' (Copy)';
			cloneFeat.category_id = clone.id; // reassign to new category
			cloneFeat.enabled = 0;
			// Preserve original order; index will be re-indexed globally
			return cloneFeat;
		});
		features.push.apply(features, clonedFeatures);

		// Re-index all features (only index, preserve order)
		features.forEach(function(feat, i) {
			feat.index = i;
		});
		featureIndex = features.length;

		renderCategoryTabs();
		updateCategoriesHiddenInputs();
		updateFeaturesHiddenInputs();
		$('#category-tabs-container .category-tab[data-category-id="' + clone.id + '"]').addClass('active').siblings().removeClass('active');
		activeCategoryId = clone.id;
		renderFeaturesGrid(activeCategoryId);
		showToast('Category and ' + clonedFeatures.length + ' feature(s) duplicated.', 'success');
	});

	// ===== NEW ENHANCEMENTS: Feature Selection & Bulk Actions =====

	// Feature tile click: open edit modal (unless clicking handle, toggle, or checkbox)
	$('#features-grid').on('click', '.feature-tile', function(e) {
		var $tile = $(this);
		var $target = $(e.target);

		// Ignore clicks on checkbox, toggle, handle, or inside modal buttons
		if ($target.closest('.tile-checkbox, .tile-toggle, .tile-handle, button').length) {
			return;
		}

		var index = $tile.data('index');
		openFeatureEditModal(index);
	});

	// Feature checkbox direct toggle
	$('#features-grid').on('change', '.tile-checkbox', function(e) {
		e.stopPropagation();
		var $tile = $(this).closest('.feature-tile');
		var index = $tile.data('index');
		if (this.checked) {
			selectedFeatures.add(index);
			$tile.addClass('selected');
		} else {
			selectedFeatures.delete(index);
			$tile.removeClass('selected');
		}
		updateBulkActionsUI();
	});

	// Quick enable/disable toggle
	$('#features-grid').on('click', '.tile-toggle', function(e) {
		e.stopPropagation();
		var $tile = $(this).closest('.feature-tile');
		var index = $tile.data('index');
		var feat = features[index];
		if (!feat) return;

		feat.enabled = feat.enabled ? 0 : 1;
		$tile.toggleClass('disabled', !feat.enabled);
		$(this).toggleClass('enabled', feat.enabled).toggleClass('disabled', !feat.enabled);
		$tile.find('.tile-badge').text(feat.enabled ? '' : 'disabled');
		updateFeaturesHiddenInputs();
		showToast('Feature ' + (feat.enabled ? 'enabled' : 'disabled') + '.', 'success');
	});

	// Select All checkbox
	$('#select-all-features').on('change', function() {
		var isChecked = $(this).is(':checked');
		if (isChecked) {
			$('#features-grid .feature-tile').each(function() {
				var index = $(this).data('index');
				selectedFeatures.add(index);
				$(this).addClass('selected').find('.tile-checkbox').prop('checked', true);
			});
		} else {
			selectedFeatures.clear();
			$('#features-grid .feature-tile').removeClass('selected').find('.tile-checkbox').prop('checked', false);
		}
		updateBulkActionsUI();
	});

	// Bulk Enable
	$('#bulk-enable').on('click', function() {
		if (selectedFeatures.size === 0) return;
		selectedFeatures.forEach(function(index) {
			features[index].enabled = 1;
		});
		renderFeaturesGrid(activeCategoryId);
		updateFeaturesHiddenInputs();
		showToast(selectedFeatures.size + ' feature(s) enabled.', 'success');
	});

	// Bulk Disable
	$('#bulk-disable').on('click', function() {
		if (selectedFeatures.size === 0) return;
		selectedFeatures.forEach(function(index) {
			features[index].enabled = 0;
		});
		renderFeaturesGrid(activeCategoryId);
		updateFeaturesHiddenInputs();
		showToast(selectedFeatures.size + ' feature(s) disabled.', 'success');
	});

	// Bulk Delete
	$('#bulk-delete').on('click', function() {
		var count = selectedFeatures.size;
		if (count === 0) return;
		if (!confirm('Delete ' + count + ' selected feature(s)? This cannot be undone.')) return;

		// Save state for undo
		var deletedFeatures = selectedFeatures.map(function(index) { return features[index]; }).toArray();
		lastAction = { type: 'bulk_delete', deleted: deletedFeatures };
		undoStack.push(lastAction);

		// Remove selected features (in reverse order to preserve indices)
		var indicesToDelete = Array.from(selectedFeatures).sort(function(a, b) { return b - a; });
		indicesToDelete.forEach(function(index) {
			features.splice(index, 1);
		});

		// Re-index features (only index, preserve order)
		features.forEach(function(feat, i) {
			feat.index = i;
		});
		featureIndex = features.length;

		selectedFeatures.clear();
		renderFeaturesGrid(activeCategoryId);
		renderCategoryTabs();
		updateFeaturesHiddenInputs();
		updateCategoriesHiddenInputs();
		showToast(count + ' feature(s) deleted.', 'success');
		updateUndoButton();
	});

	// Bulk Change Category
	$('#bulk-change-category').on('change', function() {
		var newCatId = $(this).val();
		if (!newCatId || selectedFeatures.size === 0) {
			$(this).val('');
			return;
		}
		selectedFeatures.forEach(function(index) {
			features[index].category_id = newCatId;
		});
		$(this).val('');
		renderFeaturesGrid(activeCategoryId);
		renderCategoryTabs(); // Update feature counts on tabs
		updateFeaturesHiddenInputs();
		showToast(selectedFeatures.size + ' feature(s) moved to new category.', 'success');
	});

	// Duplicate Selected Feature (single selection only)
	$('#duplicate-feature').on('click', function() {
		if (selectedFeatures.size !== 1) {
			alert('Please select exactly one feature to duplicate.');
			return;
		}
		var index = Array.from(selectedFeatures)[0];
		var original = features[index];
		var duplicate = $.extend({}, original);
		duplicate.id = original.id + '-copy';
		duplicate.name = original.name + ' (Copy)';
		duplicate.sku = original.sku ? original.sku + '-COPY' : '';
		duplicate.enabled = 0; // Start disabled
		duplicate.index = featureIndex++; // assign unique index
		features.push(duplicate);
		// featureIndex = features.length; // not needed; already incremented
		renderFeaturesGrid(activeCategoryId);
		renderCategoryTabs();
		updateFeaturesHiddenInputs();
		selectedFeatures.clear();
		updateBulkActionsUI();
		showToast('Feature duplicated.', 'success');
	});

	// Cancel bulk selection
	$('#cancel-bulk').on('click', function() {
		selectedFeatures.clear();
		$('#features-grid .feature-tile').removeClass('selected').find('.tile-checkbox').prop('checked', false);
		updateBulkActionsUI();
	});

	// Update Bulk Actions UI (show/hide toolbar, update selected count)
	function updateBulkActionsUI() {
		var count = selectedFeatures.size;
		$('#selected-count').text(count + ' selected');
		$('#bulk-actions-toolbar').toggle(count > 0);
		$('#bulk-change-category').prop('disabled', count === 0);
		$('#duplicate-feature').toggle(count === 1);
		if (count === 0) {
			$('#select-all-features').prop('checked', false);
		}
	}

	function updateUndoButton() {
		var $toast = $('#undo-toast');
		if (undoStack.length > 0) {
			$('#undo-message').text(lastAction ? lastAction.type + ' action can be undone' : 'Action can be undone');
			$toast.fadeIn(200);
		} else {
			$toast.fadeOut(200);
		}
	}

	// Undo functionality
	$('#undo-action').on('click', function() {
		if (undoStack.length === 0) return;
		var action = undoStack.pop();
		if (action.type === 'bulk_delete') {
			// Restore deleted features
			action.deleted.forEach(function(feat) {
				features.push(feat);
			});
			// Re-index (sort by order to maintain order, then set indices)
			features = features.sort(function(a, b) { return (a.order || 0) - (b.order || 0); });
			features.forEach(function(feat, i) {
				feat.index = i;
			});
			featureIndex = features.length;
			renderFeaturesGrid(activeCategoryId);
			renderCategoryTabs();
			updateFeaturesHiddenInputs();
			updateCategoriesHiddenInputs();
			showToast('Deleted features restored.', 'success');
			updateUndoButton();
		} else {
			updateUndoButton();
		}
	});

	updateUndoButton(); // Initial state
	// ===========================================

	// Delete button handler
	$('#category-tabs-container').on('click', '.tab-delete-btn', function(e) {
		e.stopPropagation();
		var index = $(this).data('index');
		var deletedCategory = categories[index];
		categories.splice(index, 1);
		var categoryIdToRemove = deletedCategory.id;
		features = features.filter(function(feat) {
			return feat.category_id !== categoryIdToRemove;
		});
		features.forEach(function(feat, i) {
			feat.index = i;
		});
		featureIndex = features.length;
		categories.forEach(function(cat, idx) {
			cat.order = idx + 1;
		});
		renderCategoryTabs();
		updateCategoriesHiddenInputs();
		updateFeaturesHiddenInputs();
		var $container = $('#category-tabs-container');
		var $active = $container.find('.category-tab.active');
		if ($active.length) {
			activeCategoryId = $active.data('category');
		} else {
			activeCategoryId = '';
		}
		renderFeaturesGrid(activeCategoryId);
		showToast('Category and its features deleted.', 'success');

		setTimeout(function() {
			$('form[action="options.php"]').submit();
		}, 300);
	});

	// Features: Tile Grid Management
	function getBillingAbbreviation(billingType) {
		var abbreviations = {
			'monthly': '/mo',
			'quarterly': '/qtr',
			'annual': '/yr',
			'one-off': ''
		};
		return abbreviations[billingType] || billingType;
	}

	function populateFeatureCategorySelect($select) {
		var html = '<option value="">Select Category</option>';
		categories.forEach(function(cat) {
			html += '<option value="' + cat.id + '">' + cat.name + '</option>';
		});
		$select.html(html);
	}

	function populateIncompatibilitySelect(currentFeat) {
		var $select = $('#edit-feature-incompatible');
		var html = '';
		var currentId = currentFeat.id;
		var groups = {};
		categories.forEach(function(cat) {
			groups[cat.id] = { name: cat.name, features: [] };
		});
		groups['uncategorized'] = { name: 'Uncategorized', features: [] };

		features.forEach(function(feat) {
			if (!feat.id || feat.id === currentId) return;
			var catId = feat.category_id || 'uncategorized';
			if (!groups[catId]) {
				groups[catId] = { name: catId, features: [] };
			}
			groups[catId].features.push(feat);
		});

		categories.forEach(function(cat) {
			var group = groups[cat.id];
			if (group && group.features.length > 0) {
				html += '<optgroup label="' + cat.name + '">';
				group.features.forEach(function(feat) {
					var selected = (currentFeat.incompatible_with || []).includes(feat.id) ? ' selected' : '';
					html += '<option value="' + feat.id + '"' + selected + '>' + feat.name + ' (' + feat.id + ')</option>';
				});
				html += '</optgroup>';
				groups[cat.id] = null;
			}
		});

		Object.keys(groups).forEach(function(catId) {
			var group = groups[catId];
			if (group && group.features.length > 0) {
				html += '<optgroup label="' + group.name + '">';
				group.features.forEach(function(feat) {
					var selected = (currentFeat.incompatible_with || []).includes(feat.id) ? ' selected' : '';
					html += '<option value="' + feat.id + '"' + selected + '>' + feat.name + ' (' + feat.id + ')</option>';
				});
				html += '</optgroup>';
			}
		});

		$select.html(html);
	}

	function renderFeaturesGrid(categoryId) {
		var $grid = $('#features-grid');
		$grid.empty();

		// Show category info if present (full width above grid)
		var category = categories.find(function(c) { return c.id === categoryId; });
		if (category && category.info) {
			var $info = $('<div class="category-info-text admin-category-info"></div>').text(category.info);
			$grid.append($info);
		}

		var filtered = features.filter(function(f) {
			return categoryId && f.category_id === categoryId;
		});
		filtered.sort(function(a, b) {
			return (a.order || 0) - (b.order || 0) || a.name.localeCompare(b.name);
		});
		filtered.forEach(function(feat, idx) {
			var billingAbbr = getBillingAbbreviation(feat.billing_type);
			var isSelected = selectedFeatures.has(feat.index);
			// Find category color
			var catColor = category && category.color ? category.color : '';
			var iconClass = catColor ? 'tile-icon has-category-color' : 'tile-icon';
			var iconStyle = catColor ? 'style="--category-color: ' + catColor + ';"' : '';
			var $tile = $('<div class="feature-tile' + (feat.enabled ? '' : ' disabled') + (isSelected ? ' selected' : '') + '" data-index="' + feat.index + '" data-billing="' + feat.billing_type + '">' +
				'<input type="checkbox" class="tile-checkbox"' + (isSelected ? ' checked' : '') + ' title="Select for bulk operations">' +
				'<div class="tile-toggle' + (feat.enabled ? ' enabled' : ' disabled') + '" title="' + (feat.enabled ? 'Disable' : 'Enable') + '"></div>' +
				'<div class="' + iconClass + '" ' + iconStyle + '>' + (feat.icon || '📦') + '</div>' +
				'<div class="tile-title">' + feat.name + '</div>' +
				'<div class="tile-price">€' + parseFloat(feat.price).toFixed(2) + (billingAbbr ? ' <small>' + billingAbbr + '</small>' : '') + '</div>' +
				'<div class="tile-desc">' + (feat.description || '') + '</div>' +
				(feat.sku ? '<div class="tile-sku">(' + feat.sku + ')</div>' : '') +
				'<div class="tile-handle" title="Drag to reorder">⠿</div>' +
				'</div>');
			$grid.append($tile);
		});
		bindFeatureTileDnD();
		updateBulkActionsUI();
	}

	function updateFeaturesHiddenInputs() {
		var $container = $('#features-data-container');
		$container.empty();
		if (!Array.isArray(features)) {
			console.warn('updateFeaturesHiddenInputs: features is not an array', features);
			return;
		}
		features.forEach(function(feat, idx) {
			var id = (feat.id || '').toString();
			var catId = (feat.category_id || '').toString();
			var name = (feat.name || '').toString();
			var icon = (feat.icon || '').toString();
			var desc = (feat.description || '').toString();
			var price = parseFloat(feat.price) || 0;
			var billing = (feat.billing_type || 'one-off').toString();
			var enabled = feat.enabled ? 1 : 0;
			var order = parseInt(feat.order, 10) || (idx + 1);
			var sku = (feat.sku || '').toString();
			var incompatible = Array.isArray(feat.incompatible_with) ? feat.incompatible_with : [];

			if (!id && window.console) {
				console.warn('Feature missing ID at index ' + idx + ':', feat);
			}

			var html = '<input type="hidden" name="wp_configurator_options[features][' + idx + '][id]" value="' + id.replace(/"/g, '&quot;') + '">' +
				'<input type="hidden" name="wp_configurator_options[features][' + idx + '][category_id]" value="' + catId.replace(/"/g, '&quot;') + '">' +
				'<input type="hidden" name="wp_configurator_options[features][' + idx + '][name]" value="' + name.replace(/"/g, '&quot;') + '">' +
				'<input type="hidden" name="wp_configurator_options[features][' + idx + '][description]" value="' + desc.replace(/"/g, '&quot;') + '">' +
				'<input type="hidden" name="wp_configurator_options[features][' + idx + '][icon]" value="' + icon.replace(/"/g, '&quot;') + '">' +
				'<input type="hidden" name="wp_configurator_options[features][' + idx + '][price]" value="' + price + '">' +
				'<input type="hidden" name="wp_configurator_options[features][' + idx + '][billing_type]" value="' + billing.replace(/"/g, '&quot;') + '">' +
				'<input type="hidden" name="wp_configurator_options[features][' + idx + '][enabled]" value="' + enabled + '">' +
				'<input type="hidden" name="wp_configurator_options[features][' + idx + '][order]" value="' + order + '">' +
				'<input type="hidden" name="wp_configurator_options[features][' + idx + '][sku]" value="' + sku.replace(/"/g, '&quot;') + '">';

			incompatible.forEach(function(confId) {
				html += '<input type="hidden" name="wp_configurator_options[features][' + idx + '][incompatible_with][]" value="' + confId.replace(/"/g, '&quot;') + '">';
			});

			$container.append(html);
		});
	}

	function openFeatureEditModal(featureIndex) {
		var feat = features[featureIndex];
		if (!feat) return;
		var $catSelect = $('#edit-feature-category');
		populateFeatureCategorySelect($catSelect);
		$catSelect.val(feat.category_id);

		$('#edit-feature-index').val(featureIndex);
		$('#edit-feature-name').val(feat.name);
		$('#edit-feature-icon').val(feat.icon || '');
		$('#edit-feature-price').val(feat.price);
		$('#edit-feature-billing').val(feat.billing_type || 'one-off');
		$('#edit-feature-enabled').prop('checked', !!feat.enabled);
		$('#edit-feature-sku').val(feat.sku || '');

		var desc = feat.description || '';
		if (typeof tinymce !== 'undefined' && tinymce.get('edit-feature-description')) {
			tinymce.get('edit-feature-description').setContent(desc);
		} else {
			$('#edit-feature-description').val(desc);
		}

		populateIncompatibilitySelect(feat);
		$('#feature-edit-modal').addClass('is-visible');
	}

	$('#save-feature-edit').on('click', function() {
		var index = parseInt($('#edit-feature-index').val(), 10);
		if (isNaN(index)) return;

		var category_id = $('#edit-feature-category').val();
		var name = $('#edit-feature-name').val().trim();
		if (!name) {
			alert('Feature name is required.');
			return;
		}
		var icon = $('#edit-feature-icon').val().trim();
		var price = parseFloat($('#edit-feature-price').val()) || 0;
		var billing_type = $('#edit-feature-billing').val();
		var enabled = $('#edit-feature-enabled').is(':checked') ? 1 : 0;
		var sku = $('#edit-feature-sku').val().trim();
		var description;
		if (typeof tinymce !== 'undefined' && tinymce.get('edit-feature-description')) {
			description = tinymce.get('edit-feature-description').getContent();
		} else {
			description = $('#edit-feature-description').val();
		}

		var incompatible_with = [];
		$('#edit-feature-incompatible option:selected').each(function() {
			incompatible_with.push($(this).val());
		});

		var currentId = features[index].id;
		var newIncompatible = incompatible_with.filter(function(id) {
			return id && id !== currentId;
		});
		var oldIncompatible = features[index].incompatible_with || [];

		features[index] = {
			id: currentId,
			index: index,
			category_id: category_id,
			name: name,
			icon: icon,
			price: price,
			billing_type: billing_type,
			enabled: enabled,
			description: description,
			sku: sku,
			order: features[index].order,
			incompatible_with: newIncompatible
		};

		features.forEach(function(feat) {
			if (feat.id && newIncompatible.includes(feat.id)) {
				if (!feat.incompatible_with) feat.incompatible_with = [];
				if (!feat.incompatible_with.includes(currentId)) {
					feat.incompatible_with.push(currentId);
					feat.incompatible_with = feat.incompatible_with.filter(function(v, i, self) {
						return self.indexOf(v) === i;
					});
				}
			} else if (feat.id && oldIncompatible.includes(feat.id) && !newIncompatible.includes(feat.id)) {
				if (feat.incompatible_with) {
					feat.incompatible_with = feat.incompatible_with.filter(function(id) {
						return id !== currentId;
					});
				}
			}
		});

		updateFeaturesHiddenInputs();
		renderFeaturesGrid(activeCategoryId);
		showToast('Feature saved.');
		$('#feature-edit-modal').removeClass('is-visible');

		setTimeout(function() {
			$('#submit').trigger('click');
		}, 1000);
	});

	$('#delete-feature-edit').on('click', function() {
		var index = parseInt($('#edit-feature-index').val(), 10);
		if (isNaN(index)) return;
		var deletedId = features[index].id;
		features.splice(index, 1);
		features = features.map(function(feat, i) {
			feat.index = i;
			return feat;
		});
		featureIndex = features.length;
		features.forEach(function(feat) {
			if (feat.incompatible_with) {
				feat.incompatible_with = feat.incompatible_with.filter(function(id) {
					return id !== deletedId;
				});
			}
		});
		updateFeaturesHiddenInputs();
		$('#feature-edit-modal').removeClass('is-visible');
		renderFeaturesGrid(activeCategoryId);
		showToast('Feature deleted.');

		setTimeout(function() {
			$('form[action="options.php"]').submit();
		}, 300);
	});

	$('#cancel-feature-edit').on('click', function() {
		$('#feature-edit-modal').removeClass('is-visible');
	});

	$('#feature-edit-modal').on('click', function(e) {
		if ($(e.target).is('#feature-edit-modal')) $(this).removeClass('is-visible');
	});
	$(document).on('keydown.featureEdit', function(e) {
		if (e.key === 'Escape' && $('#feature-edit-modal').hasClass('is-visible')) $('#feature-edit-modal').removeClass('is-visible');
	});

	// Drag and drop for feature tiles
	function bindFeatureTileDnD() {
		var $tiles = $('.feature-tile');
		var $draggedTile = null;
		var $placeholder = $('<div class="feature-tile-placeholder" style="height:80px;background:#e8f4fd;border:2px dashed #2271b1;margin:5px;border-radius:4px;"></div>');

		$tiles.off('mousedown.dnd').on('mousedown', function(e) {
			if ($(e.target).closest('.tile-handle').length === 0) return;
			e.preventDefault();

			var $tile = $(this);
			$draggedTile = $tile;
			var tileHeight = $tile.outerHeight();

			$tile.after($placeholder);
			$tile.addClass('dragging').css({
				position: 'fixed',
				top: $tile.offset().top,
				left: $tile.offset().left,
				width: $tile.outerWidth(),
				'z-index': 9999,
				opacity: 0.8
			});

			$(document).on('mousemove.featureDnd', function(e) {
				var mouseY = e.pageY;
				var $targetTile = null;
				$('.feature-tile').each(function() {
					if (this === $draggedTile[0]) return;
					var offset = $(this).offset();
					var height = $(this).outerHeight();
					if (mouseY >= offset.top && mouseY < offset.top + height) {
						$targetTile = $(this);
						return false;
					}
				});

				if ($targetTile && $targetTile.length) {
					var before = mouseY < ($targetTile.offset().top + $targetTile.outerHeight() / 2);
					if (before) $placeholder.insertBefore($targetTile);
					else $placeholder.insertAfter($targetTile);
				}

				$draggedTile.css('top', (mouseY - tileHeight/2) + 'px');
			});

			$(document).on('mouseup.featureDnd', function() {
				$(document).off('.featureDnd');
				if ($draggedTile && $placeholder.parent().length) {
					$placeholder.replaceWith($draggedTile);
				}
				$draggedTile.removeClass('dragging').css({
					position: '',
					top: '',
					left: '',
					width: '',
					'z-index': '',
					opacity: ''
				});
				var $visibleTiles = $('#features-grid .feature-tile');
				$visibleTiles.each(function(i) {
					var idx = parseInt($(this).data('index'), 10);
					if (!isNaN(idx) && features[idx]) {
						features[idx].order = i + 1;
					}
				});
				updateFeaturesHiddenInputs();
				$draggedTile = null;
			});
		});
	}

	$('#add-feature').off('click').on('click', function() {
		var catId = activeCategoryId;
		if (!catId) {
			alert('Please select a category tab first.');
			return;
		}
		var newFeat = {
			index: featureIndex,
			category_id: catId,
			name: '',
			icon: '',
			price: 0,
			billing_type: 'one-off',
			enabled: 1,
			description: '',
			order: features.length + 1,
			incompatible_with: []
		};
		features.push(newFeat);
		featureIndex++;
		openFeatureEditModal(newFeat.index);
	});

	renderFeaturesGrid(activeCategoryId);
	updateFeaturesHiddenInputs();

	$('form[action="options.php"]').on('submit', function() {
		// Save current UI state before form submission to ensure it persists after page reload
		var state = loadAdminState();
		// Capture header collapsed state (collapsed = not expanded)
		var $header = $('#wp-configurator-header-toggle');
		state.headerCollapsed = $header.hasClass('collapsed');
		// Capture active nav tab
		var $activeTab = $('.nav-tab-wrapper .nav-tab.nav-tab-active');
		if ($activeTab.length) {
			state.activeTab = $activeTab.data('tab');
		}
		// Capture active category tab
		var $activeCatTab = $('.category-tab.active');
		if ($activeCatTab.length) {
			state.activeCategoryTab = $activeCatTab.data('categoryId');
		}
		saveAdminState(state);

		updateFeaturesHiddenInputs();
		updateCategoriesHiddenInputs();
	});

	$('#category-tabs-container').on('mousedown', '.tab-edit-btn, .tab-delete-btn', function(e) {
		e.stopPropagation();
	});

	// Tab drag and drop
	$('#category-tabs-container').on('dragstart', '.category-tab', function(e) {
		var $tab = $(this);
		if ($(e.target).closest('.tab-edit-btn, .tab-delete-btn').length) {
			e.preventDefault();
			return;
		}
		$draggedTab = $tab;
		e.originalEvent.dataTransfer.effectAllowed = 'move';
		setTimeout(function() {
			$tab.hide();
			$categoryPlaceholder.css({
				'width': $tab.outerWidth(),
				'height': $tab.outerHeight()
			});
		}, 0);
	});

	$('#category-tabs-container').on('dragover', '.category-tab', function(e) {
		e.preventDefault();
		if (!$draggedTab) return;
		var $target = $(this);
		if ($target.is($draggedTab)) return;
		var offset = e.originalEvent.offsetX;
		var width = $target.outerWidth();
		var before = offset < width / 2;
		if (before) {
			$target.before($categoryPlaceholder);
		} else {
			$target.after($categoryPlaceholder);
		}
	});

	$('#category-tabs-container').on('drop', '.category-tab, .category-tab-placeholder', function(e) {
		e.preventDefault();
		if ($draggedTab && $categoryPlaceholder.parent().length) {
			$categoryPlaceholder.replaceWith($draggedTab);
			$draggedTab.show();
			var newOrder = [];
			$('#category-tabs-container .category-tab').each(function() {
				var catId = $(this).data('categoryId');
				var cat = categories.find(function(c) { return c.id === catId; });
				if (cat) newOrder.push(cat);
			});
			categories = newOrder;
			categories.forEach(function(cat, idx) {
				cat.order = idx + 1;
			});
			updateCategoriesHiddenInputs();
			renderCategoryTabs();
		}
		if ($draggedTab) {
			$draggedTab.show().removeClass('dragging');
			$draggedTab = null;
		}
		if ($categoryPlaceholder && $categoryPlaceholder.parent().length) {
			$categoryPlaceholder.remove();
		}
	});

	function renderCategoryTabs() {
		var $container = $('#category-tabs-container');
		var activeCategoryId = $container.find('.category-tab.active').data('categoryId');
		$container.empty();
		var firstTab = null;
		categories.forEach(function(cat, index) {
			var isActive = (cat.id === activeCategoryId);
			// Count features in this category
			var featureCount = features.filter(function(f) { return f.category_id === cat.id; }).length;
			var color = cat.color || '';
			var $tab = $('<button type="button" class="category-tab' + (isActive ? ' active' : '') + '" data-category="' + cat.id + '" data-category-id="' + cat.id + '" data-category-index="' + index + '" draggable="true"' + (color ? ' data-color="' + color + '"' : '') + '>' +
				'<span class="tab-icon">' + cat.icon + '</span>' +
				'<span class="tab-name">' + cat.name + '</span>' +
				'<span class="tab-count" title="' + featureCount + ' feature' + (featureCount !== 1 ? 's' : '') + '">' + featureCount + '</span>' +
				(cat.compulsory ? '<span class="tab-badge compulsory-badge" title="Compulsory">★</span>' : '') +
				'<button type="button" class="tab-clone-btn" title="Duplicate Category" data-index="' + index + '" draggable="false">⧉</button>' +
				'<button type="button" class="tab-edit-btn" title="Edit Category" data-index="' + index + '" draggable="false">✏️</button>' +
				'<button type="button" class="tab-delete-btn" title="Delete Category" data-index="' + index + '" draggable="false">🗑️</button>' +
				'</button>');
			if (color) {
				$tab.css('--category-color', color);
			}
			$container.append($tab);
			if (!firstTab) firstTab = $tab;
		});
		if (!$container.find('.category-tab.active').length && firstTab) {
			firstTab.addClass('active');
		}
	}

	function updateCategoriesHiddenInputs() {
		var $container = $('#categories-data-container');
		$container.empty();
		categories.forEach(function(cat, index) {
			var html = '<input type="hidden" name="wp_configurator_options[categories][' + index + '][id]" value="' + cat.id.replace(/"/g, '&quot;') + '">' +
				'<input type="hidden" name="wp_configurator_options[categories][' + index + '][original_id]" value="' + (cat.original_id || cat.id).replace(/"/g, '&quot;') + '">' +
				'<input type="hidden" name="wp_configurator_options[categories][' + index + '][name]" value="' + cat.name.replace(/"/g, '&quot;') + '">' +
				'<input type="hidden" name="wp_configurator_options[categories][' + index + '][icon]" value="' + cat.icon.replace(/"/g, '&quot;') + '">' +
				'<input type="hidden" name="wp_configurator_options[categories][' + index + '][color]" value="' + (cat.color || '').replace(/"/g, '&quot;') + '">' +
				'<input type="hidden" name="wp_configurator_options[categories][' + index + '][compulsory]" value="' + cat.compulsory + '">' +
				'<input type="hidden" name="wp_configurator_options[categories][' + index + '][info]" value="' + (cat.info || '').replace(/"/g, '&quot;') + '">' +
				'<input type="hidden" name="wp_configurator_options[categories][' + index + '][order]" value="' + (cat.order || (index+1)) + '">';
			$container.append(html);
		});
	}

	$('#export-settings').on('click', function() {
		var exportData = {
			action: 'export_settings',
			nonce: exportNonce,
			options: {
				categories: categories,
				features: features,
				settings: wpConfiguratorAdmin.settings
			}
		};

		var form = $('<form>', {
			'method': 'POST',
			'action': ajaxurl
		});

		$.each(exportData, function(key, value) {
			if (typeof value === 'object') {
				value = JSON.stringify(value);
			}
			$('<input>', {
				'type': 'hidden',
				'name': key,
				'value': value
			}).appendTo(form);
		});

		form.appendTo('body').submit();
	});

	// Import
	var parsedImportData = null;

	$('#import-settings-btn').on('click', function() {
		$('#import-file').val('');
		$('#import-preview, #import-options').hide();
		$('#submit-import').prop('disabled', true);
		parsedImportData = null;
		$('#import-settings-modal').addClass('is-visible').find('.modal-content').scrollTop(0);
	});

	$('#cancel-import, #import-settings-modal .close-modal').on('click', function() {
		$('#import-settings-modal').removeClass('is-visible');
	});

	$('#import-file').on('change', function(e) {
		var file = e.target.files[0];
		if (!file) return;

		var reader = new FileReader();
		reader.onload = function(e) {
			try {
				var data = JSON.parse(e.target.result);
				parsedImportData = data;

				if (!data.categories && !data.features && !data.settings) {
					alert('Invalid import file: missing required data sections.');
					return;
				}

				$('#preview-version').text(data.version || 'N/A');
				$('#preview-date').text(data.exported || 'N/A');
				$('#preview-categories').text((data.categories ? data.categories.length : 0) + ' categories');
				$('#preview-features').text((data.features ? data.features.length : 0) + ' features');
				$('#preview-settings').text(data.settings ? 'Yes' : 'No');

				$('#import-preview').show();
				$('#import-options').show();
				$('#submit-import').prop('disabled', false);
			} catch (err) {
				alert('Error parsing import file: ' + err.message);
			}
		};
		reader.readAsText(file);
	});

	$('#import-settings-form').on('submit', function(e) {
		e.preventDefault();
		if (!parsedImportData) {
			alert('No data to import. Please select a file first.');
			return;
		}

		var importCategories = $('input[name="import_categories"]').is(':checked');
		var importFeatures = $('input[name="import_features"]').is(':checked');
		var importSettings = $('input[name="import_settings"]').is(':checked');

		if (!importCategories && !importFeatures && !importSettings) {
			alert('Please select at least one type of data to import.');
			return;
		}

		if (!confirm('Are you sure you want to import? This will overwrite existing data according to your selection.')) {
			return;
		}

		var formData = new FormData();
		formData.append('action', 'import_settings');
		formData.append('nonce', exportNonce);
		formData.append('import_data', JSON.stringify(parsedImportData));
		formData.append('import_categories', importCategories ? '1' : '0');
		formData.append('import_features', importFeatures ? '1' : '0');
		formData.append('import_settings', importSettings ? '1' : '0');

		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: formData,
			processData: false,
			contentType: false,
			success: function(response) {
				if (response.success) {
					alert('Import successful! ' + response.data.message);
					location.reload();
				} else {
					alert('Import failed: ' + response.data.message);
				}
			},
			error: function() {
				alert('An error occurred during import. Please try again.');
			}
		});
	});
});

