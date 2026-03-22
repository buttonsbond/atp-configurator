<div class="wp-configurator-wizard">
    <div class="header-section">
        <h1><?php echo esc_html( $options['settings']['frontend_title'] ?? 'Website Configuration Wizard' ); ?></h1>
        <p><?php echo wp_kses_post( $options['settings']['frontend_subtitle'] ?? 'Drag & drop features to build your custom package' ); ?></p>
    </div>

    <div class="configurator-container">
        <?php
        // Check if there are any enabled features at all
        $has_enabled_features = false;
        foreach ($options['features'] as $feat) {
            if (!empty($feat['enabled'])) {
                $has_enabled_features = true;
                break;
            }
        }
        ?>
        <?php if (!$has_enabled_features): ?>
        <div class="no-features-message" style="text-align: center; padding: 40px 20px; background: #fff; border: 1px solid #ddd; border-radius: 4px; margin: 20px 0;">
            <h2 style="margin-top: 0; color: #d63638;"><?php esc_html_e( 'Plugin Not Yet Configured', 'wp-configurator' ); ?></h2>
            <p><?php esc_html_e( 'No features have been configured yet. Please ask your site administrator to set up the wizard in the WordPress admin panel.', 'wp-configurator' ); ?></p>
        </div>
        <?php endif; ?>

        <!-- Left Panel - Available Features -->
        <div class="tiles-panel">
            <div class="tiles-panel-header">
                <h3 class="section-title">📦 <?php esc_html_e( 'Select Features', 'wp-configurator' ); ?></h3>
                <?php if ( ! empty( $options['settings']['collapsible_categories'] ) ) : ?>
                    <button type="button" id="toggle-all-categories" class="button button-small">
                        <?php esc_html_e( 'Collapse All', 'wp-configurator' ); ?>
                    </button>
                <?php endif; ?>
            </div>

            <?php
            // Show notice for compulsory categories
            $compulsory_cats = array_filter( $options['categories'], function( $cat ) {
                return ! empty( $cat['compulsory'] );
            } );
            if ( ! empty( $compulsory_cats ) ) :
                $compulsory_names = wp_list_pluck( $compulsory_cats, 'name' );
            ?>
            <div class="alert alert-info" style="background: #e8f4fd; border-left: 4px solid #2271b1; padding: 12px; margin-bottom: 24px;">
                <p>
                    <?php
                    printf(
                        esc_html__( 'Please select at least one option from the required section: %s. Other features are optional.', 'wp-configurator' ),
                        '<strong>' . esc_html( implode( ', ', $compulsory_names ) ) . '</strong>'
                    );
                    ?>
                </p>
            </div>
            <?php endif; ?>

            <?php
            // Sort categories by order
            usort( $options['categories'], function( $a, $b ) {
                return $a['order'] <=> $b['order'];
            });

            foreach ( $options['categories'] as $category ) :
                // Get features for this category
                $category_features = array_filter( $options['features'], function( $feat ) use ( $category ) {
                    return $feat['category_id'] === $category['id'] && $feat['enabled'];
                } );

                // Sort features by order (then by name as fallback)
                usort( $category_features, function( $a, $b ) {
                    $orderA = $a['order'] ?? 0;
                    $orderB = $b['order'] ?? 0;
                    if ($orderA == $orderB) {
                        return strcmp( $a['name'], $b['name'] );
                    }
                    return $orderA - $orderB;
                } );

                if ( empty( $category_features ) ) {
                    continue;
                }

                // Determine if this category has collapsible UI enabled
                $is_collapsible = ! empty( $options['settings']['collapsible_categories'] );
            ?>
                <?php if ( $is_collapsible ) : ?>
                    <div class="category-section collapsible" data-category-id="<?php echo esc_attr( $category['id'] ); ?>">
                        <div class="category-header">
                            <div class="category-info">
                                <span class="category-icon"><?php echo esc_html( $category['icon'] ); ?></span>
                                <span class="category-name"><?php echo esc_html( $category['name'] ); ?></span>
                            </div>
                            <?php if ( ! empty( $category['compulsory'] ) ) : ?>
                                <span class="compulsory-badge" data-category-id="<?php echo esc_attr( $category['id'] ); ?>" title="<?php esc_attr_e( 'Required', 'wp-configurator' ); ?>">⚠️</span>
                            <?php endif; ?>
                            <button type="button" class="category-toggle" aria-expanded="true" aria-label="<?php esc_attr_e( 'Toggle category', 'wp-configurator' ); ?>">
                                <span class="toggle-icon">▼</span>
                            </button>
                        </div>
                        <div class="tiles-grid" data-category="<?php echo esc_attr( $category['id'] ); ?>">
                            <?php if ( ! empty( $category['info'] ) ) : ?>
                                <div class="category-info-text" style="grid-column: 1 / -1;">
                                    <?php echo nl2br( esc_html( $category['info'] ) ); ?>
                                </div>
                            <?php endif; ?>
                <?php else : ?>
                    <h4 class="section-title">
                        <?php echo esc_html( $category['icon'] . ' ' . $category['name'] ); ?>
                        <?php if ( ! empty( $category['compulsory'] ) ) : ?>
                            <span class="compulsory-badge collapsible-title-badge" data-category-id="<?php echo esc_attr( $category['id'] ); ?>" style="font-size: 0.75em; color: #d63638; margin-left: 6px;">(<?php esc_html_e( 'Required', 'wp-configurator' ); ?>)</span>
                        <?php endif; ?>
                    </h4>
                    <?php if ( ! empty( $category['info'] ) ) : ?>
                        <div class="category-info-text">
                            <?php echo nl2br( esc_html( $category['info'] ) ); ?>
                        </div>
                    <?php endif; ?>
                    <div class="tiles-grid" data-category="<?php echo esc_attr( $category['id'] ); ?>">
                <?php endif; ?>
                    <?php foreach ( $category_features as $feature ) : ?>
                        <div class="tile" draggable="true"
                             data-id="<?php echo esc_attr( $feature['id'] ); ?>"
                             data-name="<?php echo esc_attr( $feature['name'] ); ?>"
                             data-price="<?php echo esc_attr( $feature['price'] ); ?>"
                             data-billing="<?php echo esc_attr( $feature['billing_type'] ?? 'one-off' ); ?>"
                             data-icon="<?php echo esc_attr( $feature['icon'] ); ?>"
                             data-sku="<?php echo esc_attr( $feature['sku'] ?? '' ); ?>">
                            <div class="tile-icon<?php echo !empty($category['color']) ? ' has-category-color' : ''; ?>"<?php echo !empty($category['color']) ? ' style="--category-color: ' . esc_attr($category['color']) . ';"' : ''; ?>><?php echo esc_html( $feature['icon'] ); ?></div>
                            <div class="tile-title"><?php echo esc_html( $feature['name'] ); ?></div>
                            <div class="tile-price">
                                +€<?php echo esc_html( number_format( $feature['price'], 0 ) ); ?>
                                <?php if ( $feature['billing_type'] !== 'one-off' ) : ?>
                                    <span class="tile-billing-badge">
                                        <?php
                                        switch ( $feature['billing_type'] ) {
                                            case 'monthly':
                                                echo '/mo';
                                                break;
                                            case 'quarterly':
                                                echo '/qtr';
                                                break;
                                            case 'annual':
                                                echo '/yr';
                                                break;
                                        }
                                        ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="tile-description"><?php echo wp_kses_post( $feature['description'] ); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php if ( $is_collapsible ) : ?>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>

        <!-- Right Panel - Drop Zone -->
        <div class="drop-zone-panel" id="drop-zone">
            <h3 class="section-title">🛒 <?php esc_html_e( 'Your Package', 'wp-configurator' ); ?></h3>

            <div id="empty-state" class="empty-state">
                <div class="empty-state-icon">📦</div>
                <p><?php esc_html_e( 'Select a page package above, then add features to build your custom package', 'wp-configurator' ); ?></p>
            </div>

            <div id="selected-items" class="selected-items" style="display: none;"></div>

            <div class="summary-section">
                <div class="summary-row">
                    <span><?php esc_html_e( 'One-time Charges', 'wp-configurator' ); ?>:</span>
                    <span id="base-price">€0.00</span>
                </div>
                <div class="summary-row">
                    <span><?php esc_html_e( 'Monthly Ongoing', 'wp-configurator' ); ?>:</span>
                    <span id="monthly-ongoing">€0.00</span>
                </div>
                <div class="summary-row">
                    <span><?php esc_html_e( 'Quarterly Ongoing', 'wp-configurator' ); ?>:</span>
                    <span id="quarterly-ongoing">€0.00</span>
                </div>
                <div class="summary-row">
                    <span><?php esc_html_e( 'Annual Ongoing', 'wp-configurator' ); ?>:</span>
                    <span id="annual-ongoing">€0.00</span>
                </div>
                <div class="summary-row highlight">
                    <span><?php esc_html_e( 'Total', 'wp-configurator' ); ?>:</span>
                    <span id="grand-total">€0.00</span>
                </div>
                <div class="price-note"><?php echo wp_kses_post( $options['settings']['dropzone_footer_text'] ?? 'Final price may vary based on specific requirements' ); ?></div>
            </div>

            <!-- Convert to Quote Button -->
            <div class="convert-to-quote-section" style="margin-top: 20px; text-align: center;">
                <button type="button" id="convert-to-quote-btn" class="button button-primary button-large" style="width: 100%; padding: 12px 24px; font-size: 1.1em;">
                    <?php echo esc_html( $options['settings']['quote_button_text'] ?? 'Convert to Quote' ); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Contact Details Modal -->
<div id="contact-modal" class="contact-modal-overlay" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 10000; align-items: center; justify-content: center;">
    <div class="contact-modal" style="background: #fff; border-radius: 12px; padding: 24px; max-width: 500px; width: 90%; max-height: 90vh; overflow-y: auto; box-shadow: 0 12px 40px rgba(0,0,0,0.25); position: relative;">
        <button type="button" class="close-modal" style="position: absolute; top: 12px; right: 12px; background: none; border: none; font-size: 24px; cursor: pointer; color: #666; line-height: 1;">&times;</button>
        <h3 style="margin-top: 0; margin-bottom: 20px; font-size: 1.25em; color: #1d2327;">Request a Quote</h3>
        <form id="contact-form">
            <div class="form-field" style="margin-bottom: 16px;">
                <label for="contact-name" style="display: block; font-weight: 600; margin-bottom: 6px; color: #2c3338;">Full Name <span style="color: #d63638;">*</span></label>
                <input type="text" id="contact-name" name="name" required style="width: 100%; padding: 10px 14px; border: 1px solid #ccd0d4; border-radius: 6px; font-size: 14px; box-sizing: border-box;" placeholder="John Doe">
            </div>
            <div class="form-field" style="margin-bottom: 16px;">
                <label for="contact-business" style="display: block; font-weight: 600; margin-bottom: 6px; color: #2c3338;">Business Name</label>
                <input type="text" id="contact-business" name="business" style="width: 100%; padding: 10px 14px; border: 1px solid #ccd0d4; border-radius: 6px; font-size: 14px; box-sizing: border-box;" placeholder="Acme Inc.">
            </div>
            <div class="form-field" style="margin-bottom: 16px;">
                <label for="contact-email" style="display: block; font-weight: 600; margin-bottom: 6px; color: #2c3338;">Email Address <span style="color: #d63638;">*</span></label>
                <input type="email" id="contact-email" name="email" required style="width: 100%; padding: 10px 14px; border: 1px solid #ccd0d4; border-radius: 6px; font-size: 14px; box-sizing: border-box;" placeholder="john@example.com">
            </div>
            <div class="form-field" style="margin-bottom: 20px;">
                <label for="contact-phone" style="display: block; font-weight: 600; margin-bottom: 6px; color: #2c3338;">Phone Number</label>
                <input type="tel" id="contact-phone" name="phone" style="width: 100%; padding: 10px 14px; border: 1px solid #ccd0d4; border-radius: 6px; font-size: 14px; box-sizing: border-box;" placeholder="+1 (555) 123-4567">
            </div>
            <div class="form-actions" style="display: flex; gap: 10px; justify-content: flex-end;">
                <button type="button" class="button" id="cancel-contact" style="background: #f0f0f0; border-color: #ccc; color: #333;">Cancel</button>
                <button type="submit" class="button button-primary" id="submit-contact">Submit Request</button>
            </div>
        </form>
    </div>
</div>