// Emoji Picker Module
// Handles the visual emoji selector for category and feature icons
console.log('✅ admin-emoji.js loaded');

jQuery(document).ready(function($) {
	// Emoji Picker for category/feature icon
	$(document).on('click', '.open-emoji-picker', function(e) {
		e.stopPropagation();
		var $popup = $('#emoji-picker-popup');
		var $button = $(this);
		var rect = this.getBoundingClientRect();

		// Position below button
		$popup.css({
			position: 'fixed',
			top: rect.bottom + 5,
			left: rect.left,
			zIndex: 100000
		}).show();

		// Reset to "All" category on open
		$('.emoji-tab[data-category="all"]').addClass('active').siblings().removeClass('active');
		$popup.find('.emoji-option').show();

		// Auto-categorize any uncategorized emojis (in case they were added via edit or missing)
		categorizeAllEmojis();

		// Find the associated input field (sibling within same wrapper)
		var $targetInput = $button.closest('.icon-input-wrapper').find('input[type="text"]');
		if (!$targetInput.length) {
			// Fallback: try to find any input in the parent form-row
			$targetInput = $button.closest('.form-row').find('input[type="text"]');
		}
		$popup.data('target-input', $targetInput);
	});

	// Function to categorize all emojis based on Unicode ranges
	function categorizeAllEmojis() {
		var $popup = $('#emoji-picker-popup');
		var $uncategorized = $popup.find('.emoji-option:not([data-category])');

		if ($uncategorized.length) {
			$uncategorized.each(function() {
				var $emoji = $(this);
				var emojiChar = $emoji.data('emoji');
				if (!emojiChar) return;

				// Get Unicode code point
				var code = emojiChar.codePointAt(0);
				var category = 'objects'; // default

				// Unicode-based categorization
				if (code >= 0x1F600 && code <= 0x1F64F) {
					// Emoticons (😀-🙊)
					category = 'smileys';
				} else if (code >= 0x1F300 && code <= 0x1F5FF) {
					// Misc Symbols & Pictographs
					category = 'objects';
				} else if (code >= 0x1F680 && code <= 0x1F6FF) {
					// Transport & Map
					category = 'objects';
				} else if (code >= 0x1F900 && code <= 0x1F9FF) {
					// Supplemental Symbols & Pictographs
					category = 'objects';
				} else if (code >= 0x1F1E0 && code <= 0x1F1FF) {
					// Flags
					category = 'flags';
				} else if ((code >= 0x1F170 && code <= 0x1F251) || (code >= 0x1F0A0 && code <= 0x1F0FF)) {
					// Enclosed characters / Playing cards
					category = 'symbols';
				} else if (code >= 0x2700 && code <= 0x27BF) {
					// Dingbats
					category = 'symbols';
				} else if (code >= 0x2B50 && code <= 0x2B55) {
					// Misc symbols like ⭐
					category = 'stars';
				}

				$emoji.attr('data-category', category);
			});
		}
	}

	// Emoji selection
	$('#emoji-picker-popup').on('click', '.emoji-option', function() {
		var emoji = $(this).data('emoji');
		var $targetInput = $('#emoji-picker-popup').data('target-input');
		if ($targetInput && $targetInput.length) {
			$targetInput.val(emoji).focus();
		}
		$('#emoji-picker-popup').hide();
	});

	// Close emoji picker when clicking outside
	$(document).on('click', function(e) {
		if (!$(e.target).closest('#emoji-picker-popup, .open-emoji-picker').length) {
			$('#emoji-picker-popup').hide();
		}
	});

	// Emoji Category Tabs - Filter emojis by category
	$(document).on('click', '.emoji-tab', function(e) {
		e.preventDefault();
		e.stopPropagation();
		var category = $(this).data('category');

		// Update active tab
		$('.emoji-tab').removeClass('active');
		$(this).addClass('active');

		// Filter emojis
		var $allEmojis = $('.emoji-option');
		if (category === 'all') {
			$allEmojis.show();
		} else {
			$allEmojis.hide();
			$allEmojis.filter('[data-category="' + category + '"]').show();
		}
	});

	// Auto-categorize uncategorized emojis on first open
	$('#emoji-picker-popup').one('show', function() {
		var $popup = $(this);
		var $uncategorized = $popup.find('.emoji-option:not([data-category])');

		if ($uncategorized.length) {
			$uncategorized.each(function() {
				var $emoji = $(this);
				var emojiChar = $emoji.data('emoji');
				if (!emojiChar) return;

				// Get Unicode code point
				var code = emojiChar.codePointAt(0);
				var category = 'objects'; // default

				// Simple Unicode-based categorization
				if (code >= 0x1F600 && code <= 0x1F64F) {
					// Emoticons (😀-🙊)
					category = 'smileys';
				} else if (code >= 0x1F300 && code <= 0x1F5FF) {
					// Misc Symbols & Pictographs (🌍-🗿) - many objects/weather
					category = 'objects';
				} else if (code >= 0x1F680 && code <= 0x1F6FF) {
					// Transport & Map (🚀-🛳️)
					category = 'objects';
				} else if (code >= 0x1F900 && code <= 0x1F9FF) {
					// Supplemental Symbols & Pictographs (🤖-🫑) - mostly objects/gestures
					category = 'objects';
				} else if (code >= 0x1F1E0 && code <= 0x1F1FF) {
					// Flags (🏴‍☠️ etc)
					category = 'flags';
				} else if (code >= 0x1F170 && code <= 0x1F251) {
					// Enclosed characters (🔴-⛳) - could be symbols
					category = 'symbols';
				} else if (code >= 0x1F0A0 && code <= 0x1F0FF) {
					// Playing cards (🃏-🂠)
					category = 'objects';
				} else if (code >= 0x2700 && code <= 0x27BF) {
					// Dingbats (✀-⏿)
					category = 'symbols';
				} else if (code >= 0x2B50 && code <= 0x2B55) {
					// Misc symbols like ⭐
					category = 'stars';
				}

				$emoji.attr('data-category', category);
			});
		}
	});
});
