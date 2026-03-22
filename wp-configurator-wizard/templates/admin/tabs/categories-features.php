<?php
/**
 * Categories & Features tab template
 *
 * Expected variables:
 * - $options: array of plugin options (categories, features, settings)
 */
?>
<!-- Tab: Categories & Features (default active) -->
<div id="categories-features" class="wp-configurator-tab-content active">
	<div class="wp-configurator-settings-section">


		<p class="description" style="margin-bottom: 20px;"><?php esc_html_e( 'Manage categories as tabs. Click a tab to view its features. Use the buttons on each tab to edit or delete the category. Drag tabs to reorder.', 'wp-configurator' ); ?>

		<!-- Category Tabs with Controls -->
		<div class="category-tabs-wrapper" style="margin-bottom: 20px; display: flex; align-items: center; flex-wrap: wrap; gap: 8px;">
			<!-- Add Category Button -->
			<button type="button" id="add-category" class="button button-secondary"><?php esc_html_e( 'Add Category', 'wp-configurator' ); ?></button>

			<!-- Category Tabs Container -->
			<div class="category-tabs" id="category-tabs-container">
				<?php if ( ! empty( $options['categories'] ) ) :
					// Sort categories by order
					usort( $options['categories'], function( $a, $b ) {
						return ($a['order'] ?? 0) <=> ($b['order'] ?? 0);
					});
					foreach ( $options['categories'] as $index => $cat ) :
				?>
					<button type="button" class="category-tab <?php echo $index === 0 ? 'active' : ''; ?>" data-category="<?php echo esc_attr( $cat['id'] ); ?>" data-category-id="<?php echo esc_attr( $cat['id'] ); ?>" data-category-index="<?php echo $index; ?>" draggable="true">
						<span class="tab-icon"><?php echo esc_html( $cat['icon'] ); ?></span>
						<span class="tab-name"><?php echo esc_html( $cat['name'] ); ?></span>
						<span class="tab-count" title="Number of features">0</span>
						<?php if ( ! empty( $cat['compulsory'] ) ) : ?>
							<span class="tab-badge compulsory-badge" title="Compulsory">★</span>
						<?php endif; ?>
						<button type="button" class="tab-clone-btn" title="Duplicate Category" data-index="<?php echo $index; ?>" draggable="false">⧉</button>
						<button type="button" class="tab-edit-btn" title="Edit Category" data-index="<?php echo $index; ?>" draggable="false">✏️</button>
						<button type="button" class="tab-delete-btn" title="Delete Category" data-index="<?php echo $index; ?>" draggable="false">🗑️</button>
					</button>
				<?php
					endforeach;
				endif; ?>
			</div>
		</div>

		<!-- Hidden container for category data (will be populated by JS) -->
		<div id="categories-data-container" style="display:none;">
			<?php if ( ! empty( $options['categories'] ) ) :
				foreach ( $options['categories'] as $index => $cat ) :
			?>
					<input type="hidden" name="wp_configurator_options[categories][<?php echo $index; ?>][id]" value="<?php echo esc_attr( $cat['id'] ); ?>">
					<input type="hidden" name="wp_configurator_options[categories][<?php echo $index; ?>][original_id]" value="<?php echo esc_attr( $cat['id'] ); ?>">
					<input type="hidden" name="wp_configurator_options[categories][<?php echo $index; ?>][name]" value="<?php echo esc_attr( $cat['name'] ); ?>">
					<input type="hidden" name="wp_configurator_options[categories][<?php echo $index; ?>][icon]" value="<?php echo esc_attr( $cat['icon'] ); ?>">
					<input type="hidden" name="wp_configurator_options[categories][<?php echo $index; ?>][color]" value="<?php echo esc_attr( $cat['color'] ?? '' ); ?>">
					<input type="hidden" name="wp_configurator_options[categories][<?php echo $index; ?>][compulsory]" value="<?php echo $cat['compulsory'] ?? 0; ?>">
					<input type="hidden" name="wp_configurator_options[categories][<?php echo $index; ?>][info]" value="<?php echo esc_attr( $cat['info'] ?? '' ); ?>">
					<input type="hidden" name="wp_configurator_options[categories][<?php echo $index; ?>][order]" value="<?php echo esc_attr( $cat['order'] ?? $index ); ?>">
			<?php
				endforeach;
			endif; ?>
		</div>

		<!-- Bulk Actions Toolbar -->
		<div id="bulk-actions-toolbar" class="bulk-actions-toolbar" style="display: none; margin-bottom: 16px; padding: 12px 16px; background: #e8f4fd; border: 1px solid #2271b1; border-radius: 6px; align-items: center; gap: 12px;">
			<label style="display: flex; align-items: center; gap: 6px; font-weight: 600; color: #2271b1;">
				<input type="checkbox" id="select-all-features"> Select All
			</label>
			<span style="color: #666; font-size: 13px;">|</span>
			<button type="button" id="bulk-enable" class="button button-small">Enable Selected</button>
			<button type="button" id="bulk-disable" class="button button-small">Disable Selected</button>
			<button type="button" id="bulk-delete" class="button button-small" style="color: #dc3232;">Delete Selected</button>
			<select id="bulk-change-category" style="padding: 6px 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 13px; min-width: 150px;">
				<option value="">Move to Category...</option>
				<?php foreach ( $options['categories'] as $cat ) : ?>
					<option value="<?php echo esc_attr( $cat['id'] ); ?>"><?php echo esc_html( $cat['name'] ); ?></option>
				<?php endforeach; ?>
			</select>
			<span id="selected-count" style="font-size: 13px; color: #666; margin-left: auto;">0 selected</span>
			<button type="button" id="cancel-bulk" class="button" style="font-size: 13px; padding: 4px 10px;">Cancel</button>
		</div>

		<h3><?php esc_html_e( 'Features', 'wp-configurator' ); ?></h3>
		<p class="description"><?php esc_html_e( 'Manage all configuration items as features. Features are grouped by category. Use checkboxes for bulk operations, toggle to enable/disable quickly.', 'wp-configurator' ); ?></p>

		<div id="features-grid" class="features-grid"></div>
		<!-- Hidden container for feature data (populated by JS) -->
		<div id="features-data-container" style="display:none;"></div>
		<p>
			<button type="button" id="add-feature" class="button button-secondary"><?php esc_html_e( 'Add Feature', 'wp-configurator' ); ?></button>
			<button type="button" id="duplicate-feature" class="button button-small" style="display:none;"><?php esc_html_e( 'Duplicate Selected', 'wp-configurator' ); ?></button>
		</p>

		<!-- Undo Toast -->
		<div id="undo-toast" class="wp-configurator-toast" style="display: none;">
			<span id="undo-message"></span>
			<button type="button" id="undo-action" style="margin-left: 10px; background: none; border: none; color: #2271b1; cursor: pointer; font-weight: 600; text-decoration: underline;">Undo</button>
		</div>
	</div>
</div>
