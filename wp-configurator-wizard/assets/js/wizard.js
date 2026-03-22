jQuery(document).ready(function($) {
    var currency = '€';
    var decimal = 2;
    var selectedItems = [];
    var removeLabel = 'Remove item'; // Default, can be localized via wp_localize_script

    // Generate or retrieve persistent session ID
    var sessionId = localStorage.getItem('wp_configurator_session_id');
    if (!sessionId) {
        sessionId = 'sess_' + Math.random().toString(36).substr(2, 9) + '_' + Date.now();
        localStorage.setItem('wp_configurator_session_id', sessionId);
    }

    // Flag to track if checkout was completed
    var checkoutCompleted = false;

    // Track interaction function
    function trackInteraction(eventType, featureId, categoryId, metadata) {
        if (typeof wpConfigurator === 'undefined' || !wpConfigurator.ajax_url) {
            return; // Can't track without AJAX URL
        }

        // Add cache-busting parameter to prevent Varnish/Wordfence caching
        var cacheBuster = Date.now();
        var ajaxUrl = wpConfigurator.ajax_url + '?_=' + cacheBuster;

        $.post(ajaxUrl, {
            action: 'track_interaction',
            nonce: wpConfigurator.nonce,
            event_type: eventType,
            feature_id: featureId || '',
            category_id: categoryId || '',
            session_id: sessionId,
            metadata: metadata || {}
        }, function(response) {
            // Silent fail - tracking is best effort
            if (!response || !response.success) {
                console.warn('Interaction tracking failed:', response ? response.data : 'no response');
            }
        }).fail(function(xhr, status, error) {
            console.warn('Interaction tracking error:', error);
        });
    }

    // Track wizard view on page load
    trackInteraction('wizard_view');

    // Get compulsory categories from options
    var compulsoryCategories = [];
    if (wpConfigurator && wpConfigurator.options && wpConfigurator.options.categories) {
        compulsoryCategories = wpConfigurator.options.categories
            .filter(function(cat) { return cat.compulsory; })
            .map(function(cat) { return cat.id; });
    }

    // Check if all compulsory categories have at least one selected item
    function areRequiredCategoriesSatisfied() {
        if (compulsoryCategories.length === 0) return true;

        var satisfiedCategories = {};
        selectedItems.forEach(function(item) {
            if (item.category_id) {
                satisfiedCategories[item.category_id] = true;
            }
        });

        return compulsoryCategories.every(function(catId) {
            return satisfiedCategories[catId];
        });
    }

    // Update Convert to Quote button state
    function updateQuoteButtonState() {
        var $btn = $('#convert-to-quote-btn');
        var satisfied = areRequiredCategoriesSatisfied();
        $btn.prop('disabled', !satisfied);

        if (!satisfied) {
            // Find which required categories are missing
            var missing = compulsoryCategories.filter(function(catId) {
                return !selectedItems.some(function(item) { return item.category_id === catId; });
            });
            var missingNames = missing.map(function(id) {
                var cat = wpConfigurator.options.categories.find(function(c) { return c.id === id; });
                return cat ? cat.name : id;
            }).join(', ');
            $btn.attr('title', 'Please select at least one item from: ' + missingNames);
        } else {
            $btn.removeAttr('title');
        }
    }

    // Update total price display
    function updatePriceDisplay() {
        // Get compulsory category IDs from options
        var compulsoryCategories = [];
        if (wpConfigurator && wpConfigurator.options && wpConfigurator.options.categories) {
            compulsoryCategories = wpConfigurator.options.categories
                .filter(function(cat) { return cat.compulsory; })
                .map(function(cat) { return cat.id; });
        }

        // Calculate totals
        var oneTimeTotal = 0;
        var recurringTotal = 0;
        var monthlyOngoing = 0;   // sum of monthly items (raw)
        var quarterlyOngoing = 0; // sum of quarterly items (raw)
        var annualOngoing = 0;    // sum of annual items (raw)

        selectedItems.forEach(function(item) {
            var itemPrice = parseFloat(item.price) || 0;

            // Categorize by billing type
            if (item.billing_type === 'one-off') {
                oneTimeTotal += itemPrice;
            } else {
                recurringTotal += itemPrice;
                // Sum raw amounts by billing type
                switch(item.billing_type) {
                    case 'monthly':
                        monthlyOngoing += itemPrice;
                        break;
                    case 'quarterly':
                        quarterlyOngoing += itemPrice;
                        break;
                    case 'annual':
                        annualOngoing += itemPrice;
                        break;
                }
            }
        });

        // Update display
        $('#base-price').text(currency + oneTimeTotal.toFixed(decimal));
        $('#monthly-ongoing').text(currency + monthlyOngoing.toFixed(decimal));
        $('#quarterly-ongoing').text(currency + quarterlyOngoing.toFixed(decimal));
        $('#annual-ongoing').text(currency + annualOngoing.toFixed(decimal));
        $('#grand-total').text(currency + (oneTimeTotal + recurringTotal).toFixed(decimal));

        // Show/hide empty state
        if (selectedItems.length === 0) {
            $('#empty-state').show();
            $('#selected-items').hide();
        } else {
            $('#empty-state').hide();
            $('#selected-items').show();
        }
    }

    // Add item to selected list
    function addItem(tile) {
        var itemId = $(tile).data('id');
        var itemName = $(tile).data('name');
        var itemPrice = parseFloat($(tile).data('price'));
        var itemIcon = $(tile).data('icon');
        var itemBilling = $(tile).data('billing') || 'one-off'; // Get billing type from data attribute
        var itemSku = $(tile).data('sku') || ''; // Get SKU from data attribute
        // Get category from parent container (tiles-grid has data-category attribute)
        var itemCategory = $(tile).closest('.tiles-grid').data('category') || '';

        // Check if already exists
        if (selectedItems.some(function(item) { return item.id === itemId; })) {
            return;
        }

        // Get incompatibility data for this feature from wpConfigurator.options
        var featureData = null;
        if (wpConfigurator && wpConfigurator.options && wpConfigurator.options.features) {
            featureData = wpConfigurator.options.features.find(function(f) { return f.id === itemId; });
        }
        var incompatibleIds = featureData && featureData.incompatible_with ? featureData.incompatible_with : [];

        // Check for conflicts with already selected items
        var conflictingItems = selectedItems.filter(function(item) {
            return incompatibleIds.includes(item.id);
        });

        if (conflictingItems.length > 0) {
            var conflictNames = conflictingItems.map(function(item) { return item.name; }).join(', ');
            var message = 'This feature is incompatible with: ' + conflictNames + '.\n\nDo you want to remove the conflicting item(s) and continue?';
            if (!confirm(message)) {
                return; // User cancelled
            }
            // Remove conflicting items first
            conflictingItems.forEach(function(conflict) {
                removeItem(conflict.id, false); // false = don't re-render yet
            });
        }

        selectedItems.push({
            id: itemId,
            name: itemName,
            price: itemPrice,
            icon: itemIcon,
            category_id: itemCategory,
            billing_type: itemBilling,
            sku: itemSku
        });

        renderSelectedItems();
        updatePriceDisplay();
        updateQuoteButtonState();
        updateTileAvailability(); // Disable incompatible tiles
        updateCompulsoryBadges(); // Update badge states

        // Visual feedback
        $(tile).addClass('selected').prop('draggable', false);

        // Track feature added
        trackInteraction('feature_added', itemId, itemCategory, {
            name: itemName,
            price: itemPrice,
            billing_type: itemBilling
        });
    }

    // Remove item from selected list
    function removeItem(itemId, shouldRender) {
        shouldRender = (shouldRender === undefined) ? true : shouldRender;

        // Capture category before removal for tracking
        var removedItem = selectedItems.find(function(item) { return item.id === itemId; });
        var categoryId = removedItem ? removedItem.category_id : '';

        selectedItems = selectedItems.filter(function(item) {
            return item.id !== itemId;
        });

        // Re-enable tile drapability
        $('.tile[data-id="' + itemId + '"]').removeClass('selected').prop('draggable', true);

        if (shouldRender) {
            renderSelectedItems();
            updatePriceDisplay();
            updateQuoteButtonState();
            updateTileAvailability(); // Re-evaluate which tiles should be disabled
            updateCompulsoryBadges();
        }

        // Track feature removed
        trackInteraction('feature_removed', itemId, categoryId, {});
    }

    // Render selected items
    function renderSelectedItems() {
        var container = $('#selected-items');
        container.empty();

        selectedItems.forEach(function(item, index) {
            var billingLabel = '';
            if (item.billing_type !== 'one-off') {
                var period = '';
                switch(item.billing_type) {
                    case 'monthly': period = '/mo'; break;
                    case 'quarterly': period = '/qtr'; break;
                    case 'annual': period = '/yr'; break;
                }
                billingLabel = ' <span class="billing-period">' + period + '</span>';
            }

            var itemHtml = '<div class="selected-item" data-id="' + item.id + '" data-billing="' + item.billing_type + '">' +
                '<div class="selected-item-icon">' + item.icon + '</div>' +
                '<div class="selected-item-info">' +
                    '<div class="selected-item-title">' + item.name + '</div>' +
                    '<div class="selected-item-price">+' + currency + item.price.toFixed(0) + billingLabel + '</div>' +
                '</div>' +
                '<button class="remove-item" type="button" data-id="' + item.id + '" aria-label="' + removeLabel + '">✕</button>' +
            '</div>';
            container.append(itemHtml);
        });

        // Bind remove buttons
        container.find('.remove-item').on('click', function() {
            removeItem($(this).data('id'));
        });
    }

    // Drag and Drop handlers
    var draggedElement = null;

    // Tile to drop-zone (add to package)
    $('.tile').on('dragstart', function(e) {
        draggedElement = this;
        setTimeout(function() {
            $(draggedElement).addClass('dragging');
        }, 0);
        e.originalEvent.dataTransfer.setData('text/plain', $(draggedElement).data('id'));
        e.originalEvent.dataTransfer.effectAllowed = 'copy';
    });

    $('.tile').on('dragend', function() {
        $(this).removeClass('dragging');
        draggedElement = null;
    });

    $('#drop-zone').on('dragover', function(e) {
        e.preventDefault();
        e.originalEvent.dataTransfer.dropEffect = 'copy';
        $(this).addClass('drag-over');
    });

    $('#drop-zone').on('dragleave', function(e) {
        $(this).removeClass('drag-over');
    });

    $('#drop-zone').on('drop', function(e) {
        e.preventDefault();
        $(this).removeClass('drag-over');

        var droppedId = e.originalEvent.dataTransfer.getData('text/plain');
        var droppedTile = $('.tile[data-id="' + droppedId + '"]');

        if (droppedTile.length) {
            addItem(droppedTile);
        }
    });

    // Reorder selected items within drop zone
    var $draggedSelectedItem = null;
    var $selectedPlaceholder = null;

    $(document).on('dragstart', '.selected-item', function(e) {
        $draggedSelectedItem = $(this);
        setTimeout(function() {
            $draggedSelectedItem.addClass('dragging');
        }, 0);
        e.originalEvent.dataTransfer.setData('text/plain', $draggedSelectedItem.data('id'));
        e.originalEvent.dataTransfer.effectAllowed = 'move';

        // Create a more obvious placeholder with label
        $selectedPlaceholder = $('<div class="selected-item-placeholder"><span class="placeholder-label">Drop item here</span></div>');
        $draggedSelectedItem.after($selectedPlaceholder);
    });

    $(document).on('dragend', '.selected-item', function(e) {
        if ($draggedSelectedItem && $selectedPlaceholder && $selectedPlaceholder.parent().length) {
            $selectedPlaceholder.replaceWith($draggedSelectedItem);
        }
        $draggedSelectedItem.removeClass('dragging');
        $draggedSelectedItem = null;
        $selectedPlaceholder = null;
    });

    $('#selected-items').on('dragover', '.selected-item', function(e) {
        e.preventDefault();
        if (!$draggedSelectedItem || $draggedSelectedItem[0] === this) return;
        e.originalEvent.dataTransfer.dropEffect = 'move';

        var $target = $(this);
        var offset = e.originalEvent.offsetY;
        var height = $target.outerHeight();
        var before = offset < height / 2;

        if (before) {
            $selectedPlaceholder.insertBefore($target);
        } else {
            $selectedPlaceholder.insertAfter($target);
        }
    });

    $('#selected-items').on('drop', '.selected-item', function(e) {
        e.preventDefault();
        var droppedId = e.originalEvent.dataTransfer.getData('text/plain');
        var $droppedItem = $('.selected-item[data-id="' + droppedId + '"]');

        if ($draggedSelectedItem && $droppedItem.length && $draggedSelectedItem[0] !== $droppedItem[0]) {
            // Reorder selectedItems array to match new DOM order
            var newOrder = [];
            $('#selected-items .selected-item').each(function() {
                var id = $(this).data('id');
                var item = selectedItems.find(function(i) { return i.id === id; });
                if (item) newOrder.push(item);
            });
            selectedItems = newOrder;
            updatePriceDisplay();
            // Note: reorder doesn't change category satisfaction, but call for consistency
            updateQuoteButtonState();
        }
    });

    // Click to add (alternative to drag/drop)
    $('.tile').on('click', function() {
        // Prevent adding if tile is incompatible-disabled
        if ($(this).hasClass('incompatible-disabled')) {
            return;
        }
        addItem($(this));
    });

    // Remove item on double-click from selected
    $(document).on('dblclick', '.selected-item', function() {
        var itemId = $(this).data('id');
        removeItem(itemId);
    });

    // Initialize
    updatePriceDisplay();
    updateQuoteButtonState();
    updateTileAvailability();
    updateCompulsoryBadges();

    // Touch support for mobile devices
    $('.tile').on('touchstart', function(e) {
        var touch = e.originalEvent.touches[0];
        $(this).data('touchX', touch.pageX);
        $(this).data('touchY', touch.pageY);
    });

    // Convert to Quote button handler - show contact modal
    $('#convert-to-quote-btn').on('click', function() {
        if (selectedItems.length === 0) {
            alert('Please select at least one feature before requesting a quote.');
            return;
        }
        // Reset checkout completion flag for new session
        checkoutCompleted = false;
        trackInteraction('convert_click', '', '', { item_count: selectedItems.length });
        $('#contact-modal').css('display', 'flex');
        // Track checkout start
        trackInteraction('checkout_start', '', '', { item_count: selectedItems.length });
    });

    // Close modal handlers
    $('#contact-modal .close-modal, #cancel-contact').on('click', function() {
        $('#contact-modal').hide();
        // Track abandonment if checkout not completed
        if (!checkoutCompleted) {
            trackInteraction('checkout_abandoned', '', '', { item_count: selectedItems.length });
        }
    });

    // Close modal on overlay click
    $('#contact-modal').on('click', function(e) {
        if (e.target === this) {
            $(this).hide();
            // Track abandonment if checkout not completed
            if (!checkoutCompleted) {
                trackInteraction('checkout_abandoned', '', '', { item_count: selectedItems.length });
            }
        }
    });

    // Contact form submission
    $('#contact-form').on('submit', function(e) {
        e.preventDefault();

        // Gather form data
        var contactData = {
            name: $('#contact-name').val().trim(),
            business: $('#contact-business').val().trim(),
            email: $('#contact-email').val().trim(),
            phone: $('#contact-phone').val().trim(),
            selected_items: selectedItems,
            totals: {
                one_time: parseFloat($('#base-price').text().replace('€', '')) || 0,
                monthly_ongoing: parseFloat($('#monthly-ongoing').text().replace('€', '')) || 0,
                quarterly_ongoing: parseFloat($('#quarterly-ongoing').text().replace('€', '')) || 0,
                annual_ongoing: parseFloat($('#annual-ongoing').text().replace('€', '')) || 0,
                grand_total: parseFloat($('#grand-total').text().replace('€', '')) || 0
            },
            timestamp: new Date().toISOString()
        };

        // Validate required fields
        if (!contactData.name || !contactData.email) {
            alert('Please fill in all required fields.');
            return;
        }

        // Email basic validation
        var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(contactData.email)) {
            alert('Please enter a valid email address.');
            return;
        }

        console.log('Submitting quote request:', contactData);

        // Send to webhook
        sendToWebhook(contactData);
    });

    // Send to webhook via our server-side handler (stores locally and forwards if configured)
    function sendToWebhook(data) {
        // Show loading state
        $('#submit-contact').prop('disabled', true).text('Sending...');

        $.ajax({
            url: wpConfigurator.ajax_url,
            type: 'POST',
            data: {
                action: 'submit_quote_request',
                nonce: wpConfigurator.nonce,
                name: data.name,
                business: data.business,
                email: data.email,
                phone: data.phone,
                selected_items: data.selected_items,
                totals: data.totals
            },
            success: function(response) {
                if (response.success) {
                    console.log('Quote request saved (ID: ' + response.data.request_id + ')');
                    // Mark checkout as completed to prevent abandonment tracking
                    checkoutCompleted = true;
                    // Track quote submission
                    trackInteraction('quote_submitted', '', '', {
                        item_count: data.selected_items.length,
                        total: data.totals.grand_total
                    });
                    showSuccessModal();
                } else {
                    alert('Error: ' + (response.data.message || 'Unknown error'));
                    $('#submit-contact').prop('disabled', false).text('Submit Request');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', error);
                alert('There was a problem submitting your request. Please try again.');
                $('#submit-contact').prop('disabled', false).text('Submit Request');
            }
        });
    }

    // Show success popup in modal
    function showSuccessModal() {
        $('#contact-modal .contact-modal').html('<div style="text-align: center; padding: 40px 0;"><h4 style="color: #10b981; margin-bottom: 12px;">Thank You!</h4><p>Your quote request has been submitted. A formal quotation will be sent to your email shortly.</p><button type="button" class="button button-primary" id="close-success" style="margin-top: 16px;">Close</button></div>');
    }

    // Close success popup
    $(document).on('click', '#close-success', function() {
        $('#contact-modal').hide();
        // Reset modal content for next time
        location.reload(); // Simple reset for now
    });

    // Collapsible categories functionality
    if (wpConfigurator && wpConfigurator.options && wpConfigurator.options.settings && wpConfigurator.options.settings.collapsible_categories) {
        (function() {
            var $categorySections = $('.category-section.collapsible');
            var $toggleAllBtn = $('#toggle-all-categories');
            var accordionMode = wpConfigurator.options.settings.accordion_mode ? true : false;

            if (!$toggleAllBtn.length) {
                return;
            }

            // Initialize: first category expanded, others collapsed
            $categorySections.each(function(index) {
                var $section = $(this);
                if (index === 0) {
                    $section.removeClass('collapsed');
                    $section.find('.category-toggle').attr('aria-expanded', 'true');
                } else {
                    $section.addClass('collapsed');
                    $section.find('.category-toggle').attr('aria-expanded', 'false');
                }
            });
            updateToggleAllButton();

            // Toggle individual category on header click
            $categorySections.on('click', '.category-header', function(e) {
                var $section = $(this).closest('.category-section');
                var isCurrentlyCollapsed = $section.hasClass('collapsed');
                var categoryId = $section.data('category-id') || '';

                // If accordion mode, collapse all other sections first
                if (accordionMode) {
                    $categorySections.addClass('collapsed');
                    $categorySections.find('.category-toggle').attr('aria-expanded', 'false');
                    // Then expand this one (if it was collapsed)
                    if (isCurrentlyCollapsed) {
                        $section.removeClass('collapsed');
                        $section.find('.category-toggle').attr('aria-expanded', 'true');
                    }
                } else {
                    // Normal multi-open mode: toggle this section
                    $section.toggleClass('collapsed');
                    var $toggle = $section.find('.category-toggle');
                    var isExpanded = !$section.hasClass('collapsed');
                    $toggle.attr('aria-expanded', isExpanded);
                }
                updateToggleAllButton();
            });

            // Toggle all categories
            $toggleAllBtn.on('click', function() {
                var allCollapsed = $categorySections.filter('.collapsed').length === $categorySections.length;
                if (allCollapsed) {
                    $categorySections.removeClass('collapsed');
                    $categorySections.find('.category-toggle').attr('aria-expanded', 'true');
                    $toggleAllBtn.text(wpConfigurator.i18n.collapse_all);
                } else {
                    $categorySections.addClass('collapsed');
                    $categorySections.find('.category-toggle').attr('aria-expanded', 'false');
                    $toggleAllBtn.text(wpConfigurator.i18n.expand_all);
                }
            });

            function updateToggleAllButton() {
                var allCollapsed = $categorySections.filter('.collapsed').length === $categorySections.length;
                if (allCollapsed) {
                    $toggleAllBtn.text(wpConfigurator.i18n.expand_all);
                } else {
                    $toggleAllBtn.text(wpConfigurator.i18n.collapse_all);
                }
            }
        })();
    }

    // Update tile availability based on incompatibilities with selected items
    function updateTileAvailability() {
        // Build a map of feature ID -> incompatible_with array for quick lookup
        var incompatibilityMap = {};
        if (wpConfigurator && wpConfigurator.options && wpConfigurator.options.features) {
            wpConfigurator.options.features.forEach(function(feat) {
                if (feat.id && feat.incompatible_with) {
                    incompatibilityMap[feat.id] = feat.incompatible_with;
                }
            });
        }

        // Get IDs of currently selected items
        var selectedIds = selectedItems.map(function(item) { return item.id; });

        // Reset all tiles first: re-enable everything that's not selected
        $('.tile').each(function() {
            var $tile = $(this);
            var tileId = $tile.data('id');
            // Skip if already selected (they should remain non-draggable but keep selected class)
            if (selectedIds.includes(tileId)) {
                return;
            }
            // Check if this tile is incompatible with any selected item
            var isIncompatible = false;
            if (tileId && incompatibilityMap[tileId]) {
                isIncompatible = incompatibilityMap[tileId].some(function(conflictId) {
                    return selectedIds.includes(conflictId);
                });
            }
            if (isIncompatible) {
                $tile.addClass('incompatible-disabled').prop('draggable', false);
            } else {
                $tile.removeClass('incompatible-disabled').prop('draggable', true);
            }
        });
    }

    // Update compulsory category badges (⚠️ → ✅ when category satisfied)
    function updateCompulsoryBadges() {
        // Get all compulsory categories from options
        var compulsoryCategories = [];
        if (wpConfigurator && wpConfigurator.options && wpConfigurator.options.categories) {
            compulsoryCategories = wpConfigurator.options.categories
                .filter(function(cat) { return cat.compulsory; })
                .map(function(cat) { return cat.id; });
        }

        // Build map of category_id => count of selected items
        var categoryCounts = {};
        selectedItems.forEach(function(item) {
            if (item.category_id) {
                categoryCounts[item.category_id] = (categoryCounts[item.category_id] || 0) + 1;
            }
        });

        // Update badges (both collapsible and non-collapsible)
        $('.compulsory-badge').each(function() {
            var $badge = $(this);
            var catId = $badge.data('category-id');
            if (!catId) return;

            var isSatisfied = compulsoryCategories.includes(catId) && categoryCounts[catId] > 0;
            if (isSatisfied) {
                $badge.text('✅').attr('title', 'Satisfied');
            } else {
                $badge.text('⚠️').attr('title', 'Required');
            }
        });
    }

    // Fix for sticky header - dynamically adjust
    $(window).on('scroll resize', function() {
        var headerHeight = $('header').outerHeight() || 0;
        var adminBarHeight = $('#wpadminbar').outerHeight() || 0;
        var totalOffset = headerHeight + adminBarHeight + 20;

        $('.price-display, .drop-zone-panel').css('top', totalOffset + 'px');
    }).trigger('scroll');
});