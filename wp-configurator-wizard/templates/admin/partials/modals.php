		<!-- Import Settings Modal (moved outside main form) -->
		<div id="import-settings-modal" class="category-edit-modal">
			<div class="modal-content" style="max-width: 600px;">
				<h3><?php esc_html_e( 'Import Settings', 'wp-configurator' ); ?></h3>
				<p class="description" style="margin-bottom: 20px;"><?php esc_html_e( 'Upload a previously exported JSON file to import categories, features, and settings. You can choose which data to import.', 'wp-configurator' ); ?></p>

				<form id="import-settings-form" enctype="multipart/form-data">
					<div class="form-row" style="margin-bottom: 20px;">
						<label for="import-file" style="display: block; font-weight: 600; margin-bottom: 8px;"><?php esc_html_e( 'Select Export File', 'wp-configurator' ); ?>:</label>
						<input type="file" id="import-file" name="import_file" accept=".json" required style="max-width: 400px;">
						<p class="description" style="margin-top: 6px; color: #666;"><?php esc_html_e( 'Choose a .json file exported from this plugin.', 'wp-configurator' ); ?></p>
					</div>

					<div id="import-preview" class="import-preview" style="display: none; margin-bottom: 20px; padding: 16px; background: #f9f9f9; border-radius: 8px; border: 1px solid #ddd;">
						<h4 style="margin-top: 0; margin-bottom: 12px;"><?php esc_html_e( 'File Preview', 'wp-configurator' ); ?></h4>
						<p><strong><?php esc_html_e( 'Plugin Version:', 'wp-configurator' ); ?></strong> <span id="preview-version">-</span></p>
						<p><strong><?php esc_html_e( 'Exported:', 'wp-configurator' ); ?></strong> <span id="preview-date">-</span></p>
						<p><strong><?php esc_html_e( 'Categories:', 'wp-configurator' ); ?></strong> <span id="preview-categories">-</span></p>
						<p><strong><?php esc_html_e( 'Features:', 'wp-configurator' ); ?></strong> <span id="preview-features">-</span></p>
						<p><strong><?php esc_html_e( 'Settings:', 'wp-configurator' ); ?></strong> <span id="preview-settings">-</span></p>
					</div>

					<div id="import-options" class="import-options" style="display: none; margin-bottom: 20px;">
						<h4 style="margin-bottom: 12px;"><?php esc_html_e( 'Select what to import:', 'wp-configurator' ); ?></h4>
						<label style="display: block; margin-bottom: 8px;">
							<input type="checkbox" name="import_categories" value="1" checked> <?php esc_html_e( 'Categories', 'wp-configurator' ); ?>
						</label>
						<label style="display: block; margin-bottom: 8px;">
							<input type="checkbox" name="import_features" value="1" checked> <?php esc_html_e( 'Features', 'wp-configurator' ); ?>
						</label>
						<label style="display: block; margin-bottom: 8px;">
							<input type="checkbox" name="import_settings" value="1" checked> <?php esc_html_e( 'Settings', 'wp-configurator' ); ?>
						</label>
						<p class="description" style="color: #666; font-size: 13px;"><?php esc_html_e( 'Note: Importing will overwrite existing data. Categories and features will be replaced entirely. Importing settings will update only the selected settings fields.', 'wp-configurator' ); ?></p>
					</div>

					<div class="modal-actions" style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
						<button type="button" class="button" id="cancel-import"><?php esc_html_e( 'Cancel', 'wp-configurator' ); ?></button>
						<button type="submit" class="button button-primary" id="submit-import" disabled><?php esc_html_e( 'Import', 'wp-configurator' ); ?></button>
					</div>
				</form>
			</div>
		</div>

		<!-- Category Edit Modal (Wider layout) -->
		<div id="category-edit-modal" class="category-edit-modal wide-modal">
			<div class="modal-content" style="max-width: 700px;">
				<h3><?php esc_html_e( 'Edit Category', 'wp-configurator' ); ?></h3>
				<input type="hidden" id="edit-category-index">

				<div class="form-grid">
					<div class="form-row">
						<label for="edit-category-id"><?php esc_html_e( 'Category ID', 'wp-configurator' ); ?></label>
						<input type="text" id="edit-category-id" placeholder="e.g., page-packages">
						<p class="description"><?php esc_html_e( 'Auto-generated from name if left empty.', 'wp-configurator' ); ?></p>
					</div>

					<div class="form-row">
						<label for="edit-category-name"><?php esc_html_e( 'Category Name', 'wp-configurator' ); ?></label>
						<input type="text" id="edit-category-name" placeholder="e.g., Page Packages">
					</div>

					<div class="form-row">
						<label for="edit-category-icon"><?php esc_html_e( 'Icon', 'wp-configurator' ); ?></label>
						<div class="icon-input-wrapper">
							<input type="text" id="edit-category-icon" placeholder="📄" class="regular-text">
							<button type="button" id="open-emoji-picker" class="button open-emoji-picker" title="Select Emoji">😀</button>
						</div>
						<p class="description"><?php esc_html_e( 'Type or paste an emoji, or click the button to pick.', 'wp-configurator' ); ?></p>
					</div>

					<div class="form-row">
						<label for="edit-category-color"><?php esc_html_e( 'Accent Color', 'wp-configurator' ); ?></label>
						<div class="color-input-wrapper">
							<input type="color" id="edit-category-color" value="#6366f1">
							<span class="color-preview" id="color-preview"></span>
						</div>
						<p class="description"><?php esc_html_e( 'Color for tab accent and icon backgrounds.', 'wp-configurator' ); ?></p>
					</div>

					<div class="form-row">
						<label class="checkbox-label">
							<input type="checkbox" id="edit-category-compulsory">
							<?php esc_html_e( 'This category is required (compulsory)', 'wp-configurator' ); ?>
						</label>
					</div>

					<div class="form-row">
						<label for="edit-category-info"><?php esc_html_e( 'Category Information', 'wp-configurator' ); ?></label>
						<textarea id="edit-category-info" rows="3" placeholder="<?php esc_attr_e( 'Optional help text shown to users above the features in this category...', 'wp-configurator' ); ?>"></textarea>
						<p class="description"><?php esc_html_e( 'Optional: Informational text displayed at the top of this category section on the frontend. Supports line breaks.', 'wp-configurator' ); ?></p>
					</div>
				</div>

				<div class="modal-actions">
					<button type="button" class="button" id="cancel-category-edit"><?php esc_html_e( 'Cancel', 'wp-configurator' ); ?></button>
					<button type="button" class="button button-primary" id="save-category-edit"><?php esc_html_e( 'Save Category', 'wp-configurator' ); ?></button>
				</div>
			</div>
		</div>

		<!-- Emoji Picker Popup -->
		<div id="emoji-picker-popup" class="emoji-picker-popup" style="display: none;">
			<!-- Category Tabs -->
			<div class="emoji-category-tabs">
				<button type="button" class="emoji-tab active" data-category="all">All</button>
				<button type="button" class="emoji-tab" data-category="smileys">😀 Smileys</button>
				<button type="button" class="emoji-tab" data-category="gestures">👋 Gestures</button>
				<button type="button" class="emoji-tab" data-category="hearts">❤️ Hearts</button>
				<button type="button" class="emoji-tab" data-category="stars">⭐ Stars</button>
				<button type="button" class="emoji-tab" data-category="weather">🌤️ Weather</button>
				<button type="button" class="emoji-tab" data-category="animals">🐱 Animals</button>
				<button type="button" class="emoji-tab" data-category="objects">💡 Objects</button>
				<button type="button" class="emoji-tab" data-category="symbols">⚠️ Symbols</button>
				<button type="button" class="emoji-tab" data-category="flags">🏳️ Flags</button>
			</div>
			<div class="emoji-grid">
				<button type="button" class="emoji-option" data-emoji="😀" data-category="smileys">😀</button>
				<button type="button" class="emoji-option" data-emoji="😃" data-category="smileys">😃</button>
				<button type="button" class="emoji-option" data-emoji="😄" data-category="smileys">😄</button>
				<button type="button" class="emoji-option" data-emoji="😁" data-category="smileys">😁</button>
				<button type="button" class="emoji-option" data-emoji="😆" data-category="smileys">😆</button>
				<button type="button" class="emoji-option" data-emoji="😅" data-category="smileys">😅</button>
				<button type="button" class="emoji-option" data-emoji="😂" data-category="smileys">😂</button>
				<button type="button" class="emoji-option" data-emoji="🤣" data-category="smileys">🤣</button>
				<button type="button" class="emoji-option" data-emoji="😊" data-category="smileys">😊</button>
				<button type="button" class="emoji-option" data-emoji="😇" data-category="smileys">😇</button>
				<button type="button" class="emoji-option" data-emoji="🙂" data-category="smileys">🙂</button>
				<button type="button" class="emoji-option" data-emoji="😉" data-category="smileys">😉</button>
				<button type="button" class="emoji-option" data-emoji="😌" data-category="smileys">😌</button>
				<button type="button" class="emoji-option" data-emoji="😍" data-category="smileys">😍</button>
				<button type="button" class="emoji-option" data-emoji="🥰" data-category="smileys">🥰</button>
				<button type="button" class="emoji-option" data-emoji="😘" data-category="smileys">😘</button>
				<button type="button" class="emoji-option" data-emoji="😗" data-category="smileys">😗</button>
				<button type="button" class="emoji-option" data-emoji="😙" data-category="smileys">😙</button>
				<button type="button" class="emoji-option" data-emoji="😚" data-category="smileys">😚</button>
				<button type="button" class="emoji-option" data-emoji="😋" data-category="smileys">😋</button>
				<button type="button" class="emoji-option" data-emoji="😛" data-category="smileys">😛</button>
				<button type="button" class="emoji-option" data-emoji="😜" data-category="smileys">😜</button>
				<button type="button" class="emoji-option" data-emoji="🤪" data-category="smileys">🤪</button>
				<button type="button" class="emoji-option" data-emoji="😝" data-category="smileys">😝</button>
				<button type="button" class="emoji-option" data-emoji="🤑" data-category="smileys">🤑</button>
				<button type="button" class="emoji-option" data-emoji="🤗" data-category="gestures">🤗</button>
				<button type="button" class="emoji-option" data-emoji="🤭" data-category="smileys">🤭</button>
				<button type="button" class="emoji-option" data-emoji="🤫" data-category="smileys">🤫</button>
				<button type="button" class="emoji-option" data-emoji="🧐" data-category="smileys">🧐</button>
				<button type="button" class="emoji-option" data-emoji="🤓" data-category="smileys">🤓</button>
				<button type="button" class="emoji-option" data-emoji="😎" data-category="smileys">😎</button>
				<button type="button" class="emoji-option" data-emoji="🤩" data-category="smileys">🤩</button>
				<button type="button" class="emoji-option" data-emoji="🥳" data-category="smileys">🥳</button>
				<button type="button" class="emoji-option" data-emoji="😏" data-category="smileys">😏</button>
				<button type="button" class="emoji-option" data-emoji="😒" data-category="smileys">😒</button>
				<button type="button" class="emoji-option" data-emoji="😞" data-category="smileys">😞</button>
				<button type="button" class="emoji-option" data-emoji="😔" data-category="smileys">😔</button>
				<button type="button" class="emoji-option" data-emoji="😟" data-category="smileys">😟</button>
				<button type="button" class="emoji-option" data-emoji="😕" data-category="smileys">😕</button>
				<button type="button" class="emoji-option" data-emoji="🙁" data-category="smileys">🙁</button>
				<button type="button" class="emoji-option" data-emoji="☹️" data-category="smileys">☹️</button>
				<button type="button" class="emoji-option" data-emoji="😣" data-category="smileys">😣</button>
				<button type="button" class="emoji-option" data-emoji="😖" data-category="smileys">😖</button>
				<button type="button" class="emoji-option" data-emoji="😫" data-category="smileys">😫</button>
				<button type="button" class="emoji-option" data-emoji="😩" data-category="smileys">😩</button>
				<button type="button" class="emoji-option" data-emoji="🥺" data-category="smileys">🥺</button>
				<button type="button" class="emoji-option" data-emoji="😢" data-category="smileys">😢</button>
				<button type="button" class="emoji-option" data-emoji="😭" data-category="smileys">😭</button>
				<button type="button" class="emoji-option" data-emoji="😤" data-category="smileys">😤</button>
				<button type="button" class="emoji-option" data-emoji="😠" data-category="smileys">😠</button>
				<button type="button" class="emoji-option" data-emoji="😡" data-category="smileys">😡</button>
				<button type="button" class="emoji-option" data-emoji="🤬" data-category="smileys">🤬</button>
				<button type="button" class="emoji-option" data-emoji="😈" data-category="smileys">😈</button>
				<button type="button" class="emoji-option" data-emoji="👿" data-category="smileys">👿</button>
				<button type="button" class="emoji-option" data-emoji="💀" data-category="smileys">💀</button>
				<button type="button" class="emoji-option" data-emoji="☠️" data-category="smileys">☠️</button>
				<button type="button" class="emoji-option" data-emoji="💩" data-category="smileys">💩</button>
				<button type="button" class="emoji-option" data-emoji="🤖" data-category="smileys">🤖</button>
				<button type="button" class="emoji-option" data-emoji="👻" data-category="smileys">👻</button>
				<button type="button" class="emoji-option" data-emoji="👽" data-category="smileys">👽</button>
				<button type="button" class="emoji-option" data-emoji="👾" data-category="smileys">👾</button>
				<button type="button" class="emoji-option" data-emoji="🤖" data-category="smileys">🤖</button>
				<button type="button" class="emoji-option" data-emoji="😺" data-category="animals">😺</button>
				<button type="button" class="emoji-option" data-emoji="😸" data-category="animals">😸</button>
				<button type="button" class="emoji-option" data-emoji="😹" data-category="animals">😹</button>
				<button type="button" class="emoji-option" data-emoji="😻" data-category="animals">😻</button>
				<button type="button" class="emoji-option" data-emoji="😼" data-category="animals">😼</button>
				<button type="button" class="emoji-option" data-emoji="😽" data-category="animals">😽</button>
				<button type="button" class="emoji-option" data-emoji="🙀" data-category="animals">🙀</button>
				<button type="button" class="emoji-option" data-emoji="😿" data-category="animals">😿</button>
				<button type="button" class="emoji-option" data-emoji="😾" data-category="animals">😾</button>
				<button type="button" class="emoji-option" data-emoji="🙈" data-category="animals">🙈</button>
				<button type="button" class="emoji-option" data-emoji="🙉" data-category="animals">🙉</button>
				<button type="button" class="emoji-option" data-emoji="🙊" data-category="animals">🙊</button>
				<button type="button" class="emoji-option" data-emoji="💌" data-category="hearts">💌</button>
				<button type="button" class="emoji-option" data-emoji="💘" data-category="hearts">💘</button>
				<button type="button" class="emoji-option" data-emoji="💝" data-category="hearts">💝</button>
				<button type="button" class="emoji-option" data-emoji="💖" data-category="hearts">💖</button>
				<button type="button" class="emoji-option" data-emoji="💗" data-category="hearts">💗</button>
				<button type="button" class="emoji-option" data-emoji="💓" data-category="hearts">💓</button>
				<button type="button" class="emoji-option" data-emoji="💞" data-category="hearts">💞</button>
				<button type="button" class="emoji-option" data-emoji="💕" data-category="hearts">💕</button>
				<button type="button" class="emoji-option" data-emoji="💟" data-category="hearts">💟</button>
				<button type="button" class="emoji-option" data-emoji="❣️" data-category="hearts">❣️</button>
				<button type="button" class="emoji-option" data-emoji="💔" data-category="hearts">💔</button>
				<button type="button" class="emoji-option" data-emoji="❤️" data-category="hearts">❤️</button>
				<button type="button" class="emoji-option" data-emoji="🧡" data-category="hearts">🧡</button>
				<button type="button" class="emoji-option" data-emoji="💛" data-category="hearts">💛</button>
				<button type="button" class="emoji-option" data-emoji="💚" data-category="hearts">💚</button>
				<button type="button" class="emoji-option" data-emoji="💙" data-category="hearts">💙</button>
				<button type="button" class="emoji-option" data-emoji="💜" data-category="hearts">💜</button>
				<button type="button" class="emoji-option" data-emoji="🤍" data-category="hearts">🤍</button>
				<button type="button" class="emoji-option" data-emoji="🤎" data-category="hearts">🤎</button>
				<button type="button" class="emoji-option" data-emoji="🖤" data-category="hearts">🖤</button>
				<button type="button" class="emoji-option" data-emoji="💯" data-category="smileys">💯</button>
				<button type="button" class="emoji-option" data-emoji="✨" data-category="stars">✨</button>
				<button type="button" class="emoji-option" data-emoji="🌟" data-category="stars">🌟</button>
				<button type="button" class="emoji-option" data-emoji="💫" data-category="stars">💫</button>
				<button type="button" class="emoji-option" data-emoji="⭐" data-category="stars">⭐</button>
				<button type="button" class="emoji-option" data-emoji="🌠" data-category="stars">🌠</button>
				<button type="button" class="emoji-option" data-emoji="☄️" data-category="stars">☄️</button>
				<button type="button" class="emoji-option" data-emoji="💥" data-category="stars">💥</button>
				<button type="button" class="emoji-option" data-emoji="💢" data-category="stars">💢</button>
				<button type="button" class="emoji-option" data-emoji="💦" data-category="stars">💦</button>
				<button type="button" class="emoji-option" data-emoji="💨" data-category="stars">💨</button>
				<button type="button" class="emoji-option" data-emoji="🕳️" data-category="symbols">🕳️</button>
				<button type="button" class="emoji-option" data-emoji="💣" data-category="objects">💣</button>
				<button type="button" class="emoji-option" data-emoji="💬" data-category="objects">💬</button>
				<button type="button" class="emoji-option" data-emoji="👁️‍🗨️" data-category="objects">👁️‍🗨️</button>
				<button type="button" class="emoji-option" data-emoji="🗨️" data-category="objects">🗨️</button>
				<button type="button" class="emoji-option" data-emoji="🗯️" data-category="objects">🗯️</button>
				<button type="button" class="emoji-option" data-emoji="💭" data-category="objects">💭</button>
				<button type="button" class="emoji-option" data-emoji="💤" data-category="objects">💤</button>
				<button type="button" class="emoji-option" data-emoji="🔮" data-category="objects">🔮</button>
				<button type="button" class="emoji-option" data-emoji="🎯" data-category="objects">🎯</button>
				<button type="button" class="emoji-option" data-emoji="🛡️" data-category="objects">🛡️</button>
				<button type="button" class="emoji-option" data-emoji="⚔️" data-category="objects">⚔️</button>
				<button type="button" class="emoji-option" data-emoji="💪" data-category="objects">💪</button>
				<button type="button" class="emoji-option" data-emoji="🦾" data-category="objects">🦾</button>
				<button type="button" class="emoji-option" data-emoji="🦿" data-category="objects">🦿</button>
				<button type="button" class="emoji-option" data-emoji="🚀" data-category="objects">🚀</button>
				<button type="button" class="emoji-option" data-emoji="🛸" data-category="objects">🛸</button>
				<button type="button" class="emoji-option" data-emoji="⚡" data-category="objects">⚡</button>
				<button type="button" class="emoji-option" data-emoji="🔥" data-category="weather">🔥</button>
				<button type="button" class="emoji-option" data-emoji="💧" data-category="weather">💧</button>
				<button type="button" class="emoji-option" data-emoji="🌊" data-category="weather">🌊</button>
				<button type="button" class="emoji-option" data-emoji="🌈" data-category="weather">🌈</button>
				<button type="button" class="emoji-option" data-emoji="🌞" data-category="weather">🌞</button>
				<button type="button" class="emoji-option" data-emoji="🌜" data-category="weather">🌜</button>
				<button type="button" class="emoji-option" data-emoji="🌙" data-category="weather">🌙</button>
				<button type="button" class="emoji-option" data-emoji="⭐" data-category="stars">⭐</button>
				<button type="button" class="emoji-option" data-emoji="☀️" data-category="weather">☀️</button>
				<button type="button" class="emoji-option" data-emoji="☁️" data-category="weather">☁️</button>
				<button type="button" class="emoji-option" data-emoji="⛈️" data-category="weather">⛈️</button>
				<button type="button" class="emoji-option" data-emoji="🌤️" data-category="weather">🌤️</button>
				<button type="button" class="emoji-option" data-emoji="🌦️" data-category="weather">🌦️</button>
				<button type="button" class="emoji-option" data-emoji="🌧️" data-category="weather">🌧️</button>
				<button type="button" class="emoji-option" data-emoji="🌨️" data-category="weather">🌨️</button>
				<button type="button" class="emoji-option" data-emoji="🌩️" data-category="weather">🌩️</button>
				<button type="button" class="emoji-option" data-emoji="🌪️" data-category="weather">🌪️</button>
				<button type="button" class="emoji-option" data-emoji="🌋" data-category="weather">🌋</button>
				<button type="button" class="emoji-option" data-emoji="🗻" data-category="weather">🗻</button>
				<button type="button" class="emoji-option" data-emoji="🏔️" data-category="weather">🏔️</button>
				<button type="button" class="emoji-option" data-emoji="⛰️" data-category="weather">⛰️</button>
				<button type="button" class="emoji-option" data-emoji="🏕️" data-category="objects">🏕️</button>
				<button type="button" class="emoji-option" data-emoji="🏖️" data-category="weather">🏖️</button>
				<button type="button" class="emoji-option" data-emoji="🏜️" data-category="weather">🏜️</button>
				<button type="button" class="emoji-option" data-emoji="🏝️" data-category="weather">🏝️</button>
				<button type="button" class="emoji-option" data-emoji="🏞️" data-category="weather">🏞️</button>
				<button type="button" class="emoji-option" data-emoji="🏟️" data-category="objects">🏟️</button>
				<button type="button" class="emoji-option" data-emoji="🏛️" data-category="objects">🏛️</button>
				<button type="button" class="emoji-option" data-emoji="🏗️" data-category="objects">🏗️</button>
				<button type="button" class="emoji-option" data-emoji="🏘️" data-category="objects">🏘️</button>
				<button type="button" class="emoji-option" data-emoji="🏙️" data-category="objects">🏙️</button>
				<button type="button" class="emoji-option" data-emoji="🌆" data-category="weather">🌆</button>
				<button type="button" class="emoji-option" data-emoji="🌃" data-category="weather">🌃</button>
				<button type="button" class="emoji-optional" data-emoji="🌄" data-category="weather">🌄</button>
				<button type="button" class="emoji-option" data-emoji="🌅" data-category="weather">🌅</button>
				<button type="button" class="emoji-option" data-emoji="🌌" data-category="weather">🌌</button>
				<button type="button" class="emoji-option" data-emoji="🌠" data-category="stars">🌠</button>
				<button type="button" class="emoji-option" data-emoji="🎪" data-category="objects">🎪</button>
				<button type="button" class="emoji-option" data-emoji="🎭" data-category="objects">🎭</button>
				<button type="button" class="emoji-option" data-emoji="🎨" data-category="objects">🎨</button>
				<button type="button" class="emoji-option" data-emoji="🎬" data-category="objects">🎬</button>
				<button type="button" class="emoji-option" data-emoji="🎤" data-category="objects">🎤</button>
				<button type="button" class="emoji-option" data-emoji="🎧" data-category="objects">🎧</button>
				<button type="button" class="emoji-option" data-emoji="🎼" data-category="objects">🎼</button>
				<button type="button" class="emoji-option" data-emoji="🎵" data-category="objects">🎵</button>
				<button type="button" class="emoji-option" data-emoji="🎶" data-category="objects">🎶</button>
				<button type="button" class="emoji-option" data-emoji="🎹" data-category="objects">🎹</button>
				<button type="button" class="emoji-option" data-emoji="🥁" data-category="objects">🥁</button>
				<button type="button" class="emoji-option" data-emoji="🎷" data-category="objects">🎷</button>
				<button type="button" class="emoji-option" data-emoji="🎺" data-category="objects">🎺</button>
				<button type="button" class="emoji-option" data-emoji="🪗" data-category="objects">🪗</button>
				<button type="button" class="emoji-option" data-emoji="🎸" data-category="objects">🎸</button>
				<button type="button" class="emoji-option" data-emoji="🪕" data-category="objects">🪕</button>
				<button type="button" class="emoji-option" data-emoji="💿" data-category="objects">💿</button>
				<button type="button" class="emoji-option" data-emoji="📀" data-category="objects">📀</button>
				<button type="button" class="emoji-option" data-emoji="📷" data-category="objects">📷</button>
				<button type="button" class="emoji-option" data-emoji="📹" data-category="objects">📹</button>
				<button type="button" class="emoji-option" data-emoji="🎥" data-category="objects">🎥</button>
				<button type="button" class="emoji-option" data-emoji="📽️" data-category="objects">📽️</button>
				<button type="button" class="emoji-option" data-emoji="🎞️" data-category="objects">🎞️</button>
				<button type="button" class="emoji-option" data-emoji="📞" data-category="objects">📞</button>
				<button type="button" class="emoji-option" data-emoji="☎️" data-category="objects">☎️</button>
				<button type="button" class="emoji-option" data-emoji="📠" data-category="objects">📠</button>
				<button type="button" class="emoji-option" data-emoji="📺" data-category="objects">📺</button>
				<button type="button" class="emoji-option" data-emoji="📻" data-category="objects">📻</button>
				<button type="button" class="emoji-option" data-emoji="📡" data-category="objects">📡</button>
				<button type="button" class="emoji-option" data-emoji="🔋" data-category="objects">🔋</button>
				<button type="button" class="emoji-option" data-emoji="🔌" data-category="objects">🔌</button>
				<button type="button" class="emoji-option" data-emoji="💡" data-category="objects">💡</button>
				<button type="button" class="emoji-option" data-emoji="🔦" data-category="objects">🔦</button>
				<button type="button" class="emoji-option" data-emoji="🕯️" data-category="objects">🕯️</button>
				<button type="button" class="emoji-option" data-emoji="🪔" data-category="objects">🪔</button>
				<button type="button" class="emoji-option" data-emoji="📚" data-category="objects">📚</button>
				<button type="button" class="emoji-option" data-emoji="📖" data-category="objects">📖</button>
				<button type="button" class="emoji-option" data-emoji="📕" data-category="objects">📕</button>
				<button type="button" class="emoji-option" data-emoji="📗" data-category="objects">📗</button>
				<button type="button" class="emoji-option" data-emoji="📘" data-category="objects">📘</button>
				<button type="button" class="emoji-option" data-emoji="📙" data-category="objects">📙</button>
				<button type="button" class="emoji-option" data-emoji="📓" data-category="objects">📓</button>
				<button type="button" class="emoji-option" data-emoji="📒" data-category="objects">📒</button>
				<button type="button" class="emoji-option" data-emoji="📃" data-category="objects">📃</button>
				<button type="button" class="emoji-option" data-emoji="📄" data-category="objects">📄</button>
				<button type="button" class="emoji-option" data-emoji="📰" data-category="objects">📰</button>
				<button type="button" class="emoji-option" data-emoji="🗞️" data-category="objects">🗞️</button>
				<button type="button" class="emoji-option" data-emoji="🔖" data-category="objects">🔖</button>
				<button type="button" class="emoji-option" data-emoji="🏷️" data-category="objects">🏷️</button>
				<button type="button" class="emoji-option" data-emoji="💰" data-category="objects">💰</button>
				<button type="button" class="emoji-option" data-emoji="💵" data-category="objects">💵</button>
				<button type="button" class="emoji-option" data-emoji="💴" data-category="objects">💴</button>
				<button type="button" class="emoji-option" data-emoji="💶" data-category="objects">💶</button>
				<button type="button" class="emoji-option" data-emoji="💷" data-category="objects">💷</button>
				<button type="button" class="emoji-option" data-emoji="🪙" data-category="objects">🪙</button>
				<button type="button" class="emoji-option" data-emoji="💳" data-category="objects">💳</button>
				<button type="button" class="emoji-option" data-emoji="💎" data-category="objects">💎</button>
				<button type="button" class="emoji-option" data-emoji="⚖️" data-category="objects">⚖️</button>
				<button type="button" class="emoji-option" data-emoji="🔧" data-category="objects">🔧</button>
				<button type="button" class="emoji-option" data-emoji="🔨" data-category="objects">🔨</button>
				<button type="button" class="emoji-option" data-emoji="⚒️" data-category="objects">⚒️</button>
				<button type="button" class="emoji-option" data-emoji="🛠️" data-category="objects">🛠️</button>
				<button type="button" class="emoji-option" data-emoji="⛏️" data-category="objects">⛏️</button>
				<button type="button" class="emoji-option" data-emoji="🔩" data-category="objects">🔩</button>
				<button type="button" class="emoji-option" data-emoji="⚙️" data-category="objects">⚙️</button>
				<button type="button" class="emoji-option" data-emoji="🔗" data-category="objects">🔗</button>
				<button type="button" class="emoji-option" data-emoji="⛓️" data-category="objects">⛓️</button>
				<button type="button" class="emoji-option" data-emoji="🧰" data-category="objects">🧰</button>
				<button type="button" class="emoji-option" data-emoji="🧲" data-category="objects">🧲</button>
				<button type="button" class="emoji-option" data-emoji="🔫" data-category="objects">🔫</button>
				<button type="button" class="emoji-option" data-emoji="💣" data-category="objects">💣</button>
				<button type="button" class="emoji-option" data-emoji="🧪" data-category="objects">🧪</button>
				<button type="button" class="emoji-option" data-emoji="🔬" data-category="objects">🔬</button>
				<button type="button" class="emoji-option" data-emoji="🔭" data-category="objects">🔭</button>
				<button type="button" class="emoji-option" data-emoji="📡" data-category="objects">📡</button>
				<button type="button" class="emoji-option" data-emoji="💉" data-category="objects">💉</button>
				<button type="button" class="emoji-option" data-emoji="💊" data-category="objects">💊</button>
				<button type="button" class="emoji-option" data-emoji="🩹" data-category="objects">🩹</button>
				<button type="button" class="emoji-option" data-emoji="🩺" data-category="objects">🩺</button>
				<button type="button" class="emoji-option" data-emoji="🚪" data-category="objects">🚪</button>
				<button type="button" class="emoji-option" data-emoji="🛗" data-category="objects">🛗</button>
				<button type="button" class="emoji-option" data-emoji="🪑" data-category="objects">🪑</button>
				<button type="button" class="emoji-option" data-emoji="🛋️" data-category="objects">🛋️</button>
				<button type="button" class="emoji-option" data-emoji="🛌" data-category="objects">🛌</button>
				<button type="button" class="emoji-option" data-emoji="🧸" data-category="objects">🧸</button>
				<button type="button" class="emoji-option" data-emoji="🖼️" data-category="objects">🖼️</button>
				<button type="button" class="emoji-option" data-emoji="🛍️" data-category="objects">🛍️</button>
				<button type="button" class="emoji-option" data-emoji="🛒" data-category="objects">🛒</button>
				<button type="button" class="emoji-option" data-emoji="🎁" data-category="objects">🎁</button>
				<button type="button" class="emoji-option" data-emoji="🎈" data-category="objects">🎈</button>
				<button type="button" class="emoji-option" data-emoji="🎏" data-category="objects">🎏</button>
				<button type="button" class="emoji-option" data-emoji="🎀" data-category="objects">🎀</button>
				<button type="button" class="emoji-option" data-emoji="🎊" data-category="objects">🎊</button>
				<button type="button" class="emoji-option" data-emoji="🎉" data-category="objects">🎉</button>
				<button type="button" class="emoji-option" data-emoji="🎎" data-category="objects">🎎</button>
				<button type="button" class="emoji-option" data-emoji="🏮" data-category="objects">🏮</button>
				<button type="button" class="emoji-option" data-emoji="🎐" data-category="objects">🎐</button>
				<button type="button" class="emoji-option" data-emoji="🎌" data-category="flags">🎌</button>
				<button type="button" class="emoji-option" data-emoji="🏳️" data-category="flags">🏳️</button>
				<button type="button" class="emoji-option" data-emoji="🏴" data-category="flags">🏴</button>
				<button type="button" class="emoji-option" data-emoji="🚩" data-category="flags">🚩</button>
				<button type="button" class="emoji-option" data-emoji="🎌" data-category="flags">🎌</button>
				<button type="button" class="emoji-option" data-emoji="🏁" data-category="flags">🏁</button>
				<button type="button" class="emoji-option" data-emoji="🏴‍☠️" data-category="flags">🏴‍☠️</button>
				<button type="button" class="emoji-option" data-emoji="🚣" data-category="objects">🚣</button>
				<button type="button" class="emoji-option" data-emoji="🧗" data-category="objects">🧗</button>
				<button type="button" class="emoji-option" data-emoji="🏇" data-category="objects">🏇</button>
				<button type="button" class="emoji-option" data-emoji="⛷️" data-category="objects">⛷️</button>
				<button type="button" class="emoji-option" data-emoji="🏂" data-category="objects">🏂</button>
				<button type="button" class="emoji-option" data-emoji="🏄" data-category="objects">🏄</button>
				<button type="button" class="emoji-option" data-emoji="🏊" data-category="objects">🏊</button>
				<button type="button" class="emoji-option" data-emoji="🚴" data-category="objects">🚴</button>
				<button type="button" class="emoji-option" data-emoji="🚵" data-category="objects">🚵</button>
				<button type="button" class="emoji-option" data-emoji="🎽" data-category="objects">🎽</button>
				<button type="button" class="emoji-option" data-emoji="🎿" data-category="objects">🎿</button>
				<button type="button" class="emoji-option" data-emoji="⛸️" data-category="objects">⛸️</button>
				<button type="button" class="emoji-option" data-emoji="🥌" data-category="objects">🥌</button>
				<button type="button" class="emoji-option" data-emoji="🎳" data-category="objects">🎳</button>
				<button type="button" class="emoji-option" data-emoji="🔔" data-category="objects">🔔</button>
				<button type="button" class="emoji-option" data-emoji="🔕" data-category="objects">🔕</button>
				<button type="button" class="emoji-option" data-emoji="🎼" data-category="objects">🎼</button>
				<button type="button" class="emoji-option" data-emoji="🎵" data-category="objects">🎵</button>
				<button type="button" class="emoji-option" data-emoji="🎶" data-category="objects">🎶</button>
				<button type="button" class="emoji-option" data-emoji="🎤" data-category="objects">🎤</button>
				<button type="button" class="emoji-option" data-emoji="🎧" data-category="objects">🎧</button>
				<button type="button" class="emoji-option" data-emoji="🎸" data-category="objects">🎸</button>
				<button type="button" class="emoji-option" data-emoji="🎹" data-category="objects">🎹</button>
				<button type="button" class="emoji-option" data-emoji="🎺" data-category="objects">🎺</button>
				<button type="button" class="emoji-option" data-emoji="🥁" data-category="objects">🥁</button>
				<button type="button" class="emoji-option" data-emoji="🪘" data-category="objects">🪘</button>
				<button type="button" class="emoji-option" data-emoji="📱" data-category="objects">📱</button>
				<button type="button" class="emoji-option" data-emoji="📲" data-category="objects">📲</button>
				<button type="button" class="emoji-option" data-emoji="☎️" data-category="objects">☎️</button>
				<button type="button" class="emoji-option" data-emoji="📞" data-category="objects">📞</button>
				<button type="button" class="emoji-option" data-emoji="📠" data-category="objects">📠</button>
				<button type="button" class="emoji-option" data-emoji="📡" data-category="objects">📡</button>
				<button type="button" class="emoji-option" data-emoji="🔋" data-category="objects">🔋</button>
				<button type="button" class="emoji-option" data-emoji="🔌" data-category="objects">🔌</button>
				<button type="button" class="emoji-option" data-emoji="💻" data-category="objects">💻</button>
				<button type="button" class="emoji-option" data-emoji="🖥️" data-category="objects">🖥️</button>
				<button type="button" class="emoji-option" data-emoji="🖨️" data-category="objects">🖨️</button>
				<button type="button" class="emoji-option" data-emoji="⌨️" data-category="objects">⌨️</button>
				<button type="button" class="emoji-option" data-emoji="🖱️" data-category="objects">🖱️</button>
				<button type="button" class="emoji-option" data-emoji="🖲️" data-category="objects">🖲️</button>
				<button type="button" class="emoji-option" data-emoji="💽" data-category="objects">💽</button>
				<button type="button" class="emoji-option" data-emoji="💾" data-category="objects">💾</button>
				<button type="button" class="emoji-option" data-emoji="💿" data-category="objects">💿</button>
				<button type="button" class="emoji-option" data-emoji="📀" data-category="objects">📀</button>
				<button type="button" class="emoji-option" data-emoji="📷" data-category="objects">📷</button>
				<button type="button" class="emoji-option" data-emoji="📹" data-category="objects">📹</button>
				<button type="button" class="emoji-option" data-emoji="🎥" data-category="objects">🎥</button>
				<button type="button" class="emoji-option" data-emoji="📽️" data-category="objects">📽️</button>
				<button type="button" class="emoji-option" data-emoji="🎞️" data-category="objects">🎞️</button>
				<button type="button" class="emoji-option" data-emoji="📞" data-category="objects">📞</button>
				<button type="button" class="emoji-option" data-emoji="☎️" data-category="objects">☎️</button>
				<button type="button" class="emoji-option" data-emoji="📠" data-category="objects">📠</button>
				<button type="button" class="emoji-option" data-emoji="📺" data-category="objects">📺</button>
				<button type="button" class="emoji-option" data-emoji="📻" data-category="objects">📻</button>
				<button type="button" class="emoji-option" data-emoji="📡" data-category="objects">📡</button>
				<button type="button" class="emoji-option" data-emoji="🔋" data-category="objects">🔋</button>
				<button type="button" class="emoji-option" data-emoji="🔌" data-category="objects">🔌</button>
				<button type="button" class="emoji-option" data-emoji="💡" data-category="objects">💡</button>
				<button type="button" class="emoji-option" data-emoji="🔦" data-category="objects">🔦</button>
				<button type="button" class="emoji-option" data-emoji="🕯️" data-category="objects">🕯️</button>
				<button type="button" class="emoji-option" data-emoji="🪔" data-category="objects">🪔</button>
				<button type="button" class="emoji-option" data-emoji="📚" data-category="objects">📚</button>
				<button type="button" class="emoji-option" data-emoji="📖" data-category="objects">📖</button>
				<button type="button" class="emoji-option" data-emoji="📕" data-category="objects">📕</button>
				<button type="button" class="emoji-option" data-emoji="📗" data-category="objects">📗</button>
				<button type="button" class="emoji-option" data-emoji="📘" data-category="objects">📘</button>
				<button type="button" class="emoji-option" data-emoji="📙" data-category="objects">📙</button>
				<button type="button" class="emoji-option" data-emoji="📓" data-category="objects">📓</button>
				<button type="button" class="emoji-option" data-emoji="📒" data-category="objects">📒</button>
				<button type="button" class="emoji-option" data-emoji="📃" data-category="objects">📃</button>
				<button type="button" class="emoji-option" data-emoji="📄" data-category="objects">📄</button>
				<button type="button" class="emoji-option" data-emoji="📰" data-category="objects">📰</button>
				<button type="button" class="emoji-option" data-emoji="🗞️" data-category="objects">🗞️</button>
				<button type="button" class="emoji-option" data-emoji="🔖" data-category="objects">🔖</button>
				<button type="button" class="emoji-option" data-emoji="🏷️" data-category="objects">🏷️</button>
				<button type="button" class="emoji-option" data-emoji="💰" data-category="objects">💰</button>
				<button type="button" class="emoji-option" data-emoji="💵" data-category="objects">💵</button>
				<button type="button" class="emoji-option" data-emoji="💴" data-category="objects">💴</button>
				<button type="button" class="emoji-option" data-emoji="💶" data-category="objects">💶</button>
				<button type="button" class="emoji-option" data-emoji="💷" data-category="objects">💷</button>
				<button type="button" class="emoji-option" data-emoji="🪙" data-category="objects">🪙</button>
				<button type="button" class="emoji-option" data-emoji="💳" data-category="objects">💳</button>
				<button type="button" class="emoji-option" data-emoji="💎" data-category="objects">💎</button>
				<button type="button" class="emoji-option" data-emoji="⚖️" data-category="objects">⚖️</button>
				<button type="button" class="emoji-option" data-emoji="🔧" data-category="objects">🔧</button>
				<button type="button" class="emoji-option" data-emoji="🔨" data-category="objects">🔨</button>
				<button type="button" class="emoji-option" data-emoji="⚒️" data-category="objects">⚒️</button>
				<button type="button" class="emoji-option" data-emoji="🛠️" data-category="objects">🛠️</button>
				<button type="button" class="emoji-option" data-emoji="⛏️" data-category="objects">⛏️</button>
				<button type="button" class="emoji-option" data-emoji="🔩" data-category="objects">🔩</button>
				<button type="button" class="emoji-option" data-emoji="⚙️" data-category="objects">⚙️</button>
				<button type="button" class="emoji-option" data-emoji="🔗" data-category="objects">🔗</button>
				<button type="button" class="emoji-option" data-emoji="⛓️" data-category="objects">⛓️</button>
				<button type="button" class="emoji-option" data-emoji="🧰" data-category="objects">🧰</button>
				<button type="button" class="emoji-option" data-emoji="🧲" data-category="objects">🧲</button>
				<button type="button" class="emoji-option" data-emoji="🔫" data-category="objects">🔫</button>
				<button type="button" class="emoji-option" data-emoji="💣" data-category="objects">💣</button>
				<button type="button" class="emoji-option" data-emoji="🧪" data-category="objects">🧪</button>
				<button type="button" class="emoji-option" data-emoji="🔬" data-category="objects">🔬</button>
				<button type="button" class="emoji-option" data-emoji="🔭" data-category="objects">🔭</button>
				<button type="button" class="emoji-option" data-emoji="📡" data-category="objects">📡</button>
				<button type="button" class="emoji-option" data-emoji="💉" data-category="objects">💉</button>
				<button type="button" class="emoji-option" data-emoji="💊" data-category="objects">💊</button>
				<button type="button" class="emoji-option" data-emoji="🩹" data-category="objects">🩹</button>
				<button type="button" class="emoji-option" data-emoji="🩺" data-category="objects">🩺</button>
				<button type="button" class="emoji-option" data-emoji="🚪" data-category="objects">🚪</button>
				<button type="button" class="emoji-option" data-emoji="🛗" data-category="objects">🛗</button>
				<button type="button" class="emoji-option" data-emoji="🪑" data-category="objects">🪑</button>
				<button type="button" class="emoji-option" data-emoji="🛋️" data-category="objects">🛋️</button>
				<button type="button" class="emoji-option" data-emoji="🛌" data-category="objects">🛌</button>
				<button type="button" class="emoji-option" data-emoji="🧸" data-category="objects">🧸</button>
				<button type="button" class="emoji-option" data-emoji="🖼️" data-category="objects">🖼️</button>
				<button type="button" class="emoji-option" data-emoji="🛍️" data-category="objects">🛍️</button>
				<button type="button" class="emoji-option" data-emoji="🛒" data-category="objects">🛒</button>
				<button type="button" class="emoji-option" data-emoji="🎁" data-category="objects">🎁</button>
				<button type="button" class="emoji-option" data-emoji="🎈" data-category="objects">🎈</button>
				<button type="button" class="emoji-option" data-emoji="🎏" data-category="objects">🎏</button>
				<button type="button" class="emoji-option" data-emoji="🎀" data-category="objects">🎀</button>
				<button type="button" class="emoji-option" data-emoji="🎊" data-category="objects">🎊</button>
				<button type="button" class="emoji-option" data-emoji="🎉" data-category="objects">🎉</button>
				<button type="button" class="emoji-option" data-emoji="🎎" data-category="objects">🎎</button>
				<button type="button" class="emoji-option" data-emoji="🏮" data-category="objects">🏮</button>
				<button type="button" class="emoji-option" data-emoji="🎐" data-category="objects">🎐</button>
				<button type="button" class="emoji-option" data-emoji="🎌" data-category="flags">🎌</button>
				<button type="button" class="emoji-option" data-emoji="🏳️" data-category="flags">🏳️</button>
				<button type="button" class="emoji-option" data-emoji="🏴" data-category="flags">🏴</button>
				<button type="button" class="emoji-option" data-emoji="🚩" data-category="flags">🚩</button>
				<button type="button" class="emoji-option" data-emoji="🎌" data-category="flags">🎌</button>
				<button type="button" class="emoji-option" data-emoji="🏁" data-category="flags">🏁</button>
				<button type="button" class="emoji-option" data-emoji="🏴‍☠️" data-category="flags">🏴‍☠️</button>
				<button type="button" class="emoji-option" data-emoji="🚣" data-category="objects">🚣</button>
				<button type="button" class="emoji-option" data-emoji="🧗" data-category="objects">🧗</button>
				<button type="button" class="emoji-option" data-emoji="🏇" data-category="objects">🏇</button>
				<button type="button" class="emoji-option" data-emoji="⛷️" data-category="objects">⛷️</button>
				<button type="button" class="emoji-option" data-emoji="🏂" data-category="objects">🏂</button>
				<button type="button" class="emoji-option" data-emoji="🏄" data-category="objects">🏄</button>
				<button type="button" class="emoji-option" data-emoji="🏊" data-category="objects">🏊</button>
				<button type="button" class="emoji-option" data-emoji="🚴" data-category="objects">🚴</button>
				<button type="button" class="emoji-option" data-emoji="🚵" data-category="objects">🚵</button>
				<button type="button" class="emoji-option" data-emoji="🎽" data-category="objects">🎽</button>
				<button type="button" class="emoji-option" data-emoji="🎿" data-category="objects">🎿</button>
				<button type="button" class="emoji-option" data-emoji="⛸️" data-category="objects">⛸️</button>
				<button type="button" class="emoji-option" data-emoji="🥌" data-category="objects">🥌</button>
				<button type="button" class="emoji-option" data-emoji="🎳" data-category="objects">🎳</button>
				<button type="button" class="emoji-option" data-emoji="🔔" data-category="objects">🔔</button>
				<button type="button" class="emoji-option" data-emoji="🔕" data-category="objects">🔕</button>
				<button type="button" class="emoji-option" data-emoji="🎼" data-category="objects">🎼</button>
				<button type="button" class="emoji-option" data-emoji="🎵" data-category="objects">🎵</button>
				<button type="button" class="emoji-option" data-emoji="🎶" data-category="objects">🎶</button>
				<button type="button" class="emoji-option" data-emoji="🎤" data-category="objects">🎤</button>
				<button type="button" class="emoji-option" data-emoji="🎧" data-category="objects">🎧</button>
				<button type="button" class="emoji-option" data-emoji="🎸" data-category="objects">🎸</button>
				<button type="button" class="emoji-option" data-emoji="🎹" data-category="objects">🎹</button>
				<button type="button" class="emoji-option" data-emoji="🎺" data-category="objects">🎺</button>
				<button type="button" class="emoji-option" data-emoji="🥁" data-category="objects">🥁</button>
				<button type="button" class="emoji-option" data-emoji="🪘" data-category="objects">🪘</button>

				<!-- UI Elements (Objects) -->
				<button type="button" class="emoji-option" data-emoji="📁" data-category="objects">📁</button>
				<button type="button" class="emoji-option" data-emoji="📂" data-category="objects">📂</button>
				<button type="button" class="emoji-option" data-emoji="📑" data-category="objects">📑</button>
				<button type="button" class="emoji-option" data-emoji="📋" data-category="objects">📋</button>
				<button type="button" class="emoji-option" data-emoji="📝" data-category="objects">📝</button>
				<button type="button" class="emoji-option" data-emoji="📌" data-category="objects">📌</button>
				<button type="button" class="emoji-option" data-emoji="📎" data-category="objects">📎</button>
				<button type="button" class="emoji-option" data-emoji="✉️" data-category="objects">✉️</button>
				<button type="button" class="emoji-option" data-emoji="📧" data-category="objects">📧</button>
				<button type="button" class="emoji-option" data-emoji="📤" data-category="objects">📤</button>
				<button type="button" class="emoji-option" data-emoji="📥" data-category="objects">📥</button>
				<button type="button" class="emoji-option" data-emoji="📨" data-category="objects">📨</button>
				<button type="button" class="emoji-option" data-emoji="📩" data-category="objects">📩</button>
				<button type="button" class="emoji-option" data-emoji="📫" data-category="objects">📫</button>
				<button type="button" class="emoji-option" data-emoji="📬" data-category="objects">📬</button>
				<button type="button" class="emoji-option" data-emoji="📭" data-category="objects">📭</button>
				<button type="button" class="emoji-option" data-emoji="📮" data-category="objects">📮</button>
				<button type="button" class="emoji-option" data-emoji="💼" data-category="objects">💼</button>
				<button type="button" class="emoji-option" data-emoji="📊" data-category="objects">📊</button>
				<button type="button" class="emoji-option" data-emoji="📈" data-category="objects">📈</button>
				<button type="button" class="emoji-option" data-emoji="📉" data-category="objects">📉</button>
				<button type="button" class="emoji-option" data-emoji="🗂️" data-category="objects">🗂️</button>
				<button type="button" class="emoji-option" data-emoji="🗃️" data-category="objects">🗃️</button>
				<button type="button" class="emoji-option" data-emoji="🗄️" data-category="objects">🗄️</button>
				<button type="button" class="emoji-option" data-emoji="🗑️" data-category="objects">🗑️</button>
				<button type="button" class="emoji-option" data-emoji="📏" data-category="objects">📏</button>
				<button type="button" class="emoji-option" data-emoji="📐" data-category="objects">📐</button>
				<button type="button" class="emoji-option" data-emoji="📅" data-category="objects">📅</button>
				<button type="button" class="emoji-option" data-emoji="📆" data-category="objects">📆</button>

				<!-- UI Symbols -->
				<button type="button" class="emoji-option" data-emoji="⬆️" data-category="symbols">⬆️</button>
				<button type="button" class="emoji-option" data-emoji="⬇️" data-category="symbols">⬇️</button>
				<button type="button" class="emoji-option" data-emoji="⬅️" data-category="symbols">⬅️</button>
				<button type="button" class="emoji-option" data-emoji="➡️" data-category="symbols">➡️</button>
				<button type="button" class="emoji-option" data-emoji="↕️" data-category="symbols">↕️</button>
				<button type="button" class="emoji-option" data-emoji="↔️" data-category="symbols">↔️</button>
				<button type="button" class="emoji-option" data-emoji="↩️" data-category="symbols">↩️</button>
				<button type="button" class="emoji-option" data-emoji="↪️" data-category="symbols">↪️</button>
				<button type="button" class="emoji-option" data-emoji="↗️" data-category="symbols">↗️</button>
				<button type="button" class="emoji-option" data-emoji="↘️" data-category="symbols">↘️</button>
				<button type="button" class="emoji-option" data-emoji="↙️" data-category="symbols">↙️</button>
				<button type="button" class="emoji-option" data-emoji="↖️" data-category="symbols">↖️</button>
				<button type="button" class="emoji-option" data-emoji="✅" data-category="symbols">✅</button>
				<button type="button" class="emoji-option" data-emoji="❌" data-category="symbols">❌</button>
				<button type="button" class="emoji-option" data-emoji="⚠️" data-category="symbols">⚠️</button>
				<button type="button" class="emoji-option" data-emoji="❗" data-category="symbols">❗</button>
				<button type="button" class="emoji-option" data-emoji="ℹ️" data-category="symbols">ℹ️</button>
				<button type="button" class="emoji-option" data-emoji="🔴" data-category="symbols">🔴</button>
				<button type="button" class="emoji-option" data-emoji="🟡" data-category="symbols">🟡</button>
				<button type="button" class="emoji-option" data-emoji="🟢" data-category="symbols">🟢</button>
				<button type="button" class="emoji-option" data-emoji="🔵" data-category="symbols">🔵</button>
				<button type="button" class="emoji-option" data-emoji="⚪" data-category="symbols">⚪</button>
				<button type="button" class="emoji-option" data-emoji="⚫" data-category="symbols">⚫</button>
				<button type="button" class="emoji-option" data-emoji="⬜" data-category="symbols">⬜</button>
				<button type="button" class="emoji-option" data-emoji="⬛" data-category="symbols">⬛</button>
				<button type="button" class="emoji-option" data-emoji="🟧" data-category="symbols">🟧</button>
				<button type="button" class="emoji-option" data-emoji="🟦" data-category="symbols">🟦</button>
				<button type="button" class="emoji-option" data-emoji="🟪" data-category="symbols">🟪</button>
				<button type="button" class="emoji-option" data-emoji="🟫" data-category="symbols">🟫</button>
				<button type="button" class="emoji-option" data-emoji="🔄" data-category="symbols">🔄</button>
				<button type="button" class="emoji-option" data-emoji="🏠" data-category="objects">🏠</button>
				<button type="button" class="emoji-option" data-emoji="⏰" data-category="objects">⏰</button>
				<button type="button" class="emoji-option" data-emoji="⏱️" data-category="objects">⏱️</button>
				<button type="button" class="emoji-option" data-emoji="⏲️" data-category="objects">⏲️</button>
				<button type="button" class="emoji-option" data-emoji="⏳" data-category="objects">⏳</button>
				<button type="button" class="emoji-option" data-emoji="✂️" data-category="symbols">✂️</button>
				<button type="button" class="emoji-option" data-emoji="🔒" data-category="symbols">🔒</button>
				<button type="button" class="emoji-option" data-emoji="🔓" data-category="symbols">🔓</button>
				<button type="button" class="emoji-option" data-emoji="🔏" data-category="symbols">🔏</button>
				<button type="button" class="emoji-option" data-emoji="🔐" data-category="symbols">🔐</button>
				<button type="button" class="emoji-option" data-emoji="🔑" data-category="symbols">🔑</button>
				<button type="button" class="emoji-option" data-emoji="🔍" data-category="symbols">🔍</button>
				<button type="button" class="emoji-option" data-emoji="🔎" data-category="symbols">🔎</button>

				<!-- European & Essential Flags -->
				<button type="button" class="emoji-option" data-emoji="🇪🇺" data-category="flags">🇪🇺</button>
				<button type="button" class="emoji-option" data-emoji="🇪🇸" data-category="flags">🇪🇸</button>
				<button type="button" class="emoji-option" data-emoji="🇫🇷" data-category="flags">🇫🇷</button>
				<button type="button" class="emoji-option" data-emoji="🇩🇪" data-category="flags">🇩🇪</button>
				<button type="button" class="emoji-option" data-emoji="🇮🇹" data-category="flags">🇮🇹</button>
				<button type="button" class="emoji-option" data-emoji="🇳🇱" data-category="flags">🇳🇱</button>
				<button type="button" class="emoji-option" data-emoji="🇧🇪" data-category="flags">🇧🇪</button>
				<button type="button" class="emoji-option" data-emoji="🇱🇺" data-category="flags">🇱🇺</button>
				<button type="button" class="emoji-option" data-emoji="🇵🇹" data-category="flags">🇵🇹</button>
				<button type="button" class="emoji-option" data-emoji="🇮🇪" data-category="flags">🇮🇪</button>
				<button type="button" class="emoji-option" data-emoji="🇦🇹" data-category="flags">🇦🇹</button>
				<button type="button" class="emoji-option" data-emoji="🇸🇪" data-category="flags">🇸🇪</button>
				<button type="button" class="emoji-option" data-emoji="🇩🇰" data-category="flags">🇩🇰</button>
				<button type="button" class="emoji-option" data-emoji="🇳🇴" data-category="flags">🇳🇴</button>
				<button type="button" class="emoji-option" data-emoji="🇫🇮" data-category="flags">🇫🇮</button>
				<button type="button" class="emoji-option" data-emoji="🇵🇱" data-category="flags">🇵🇱</button>
				<button type="button" class="emoji-option" data-emoji="🇨🇿" data-category="flags">🇨🇿</button>
				<button type="button" class="emoji-option" data-emoji="🇭🇺" data-category="flags">🇭🇺</button>
				<button type="button" class="emoji-option" data-emoji="🇷🇴" data-category="flags">🇷🇴</button>
				<button type="button" class="emoji-option" data-emoji="🇧🇬" data-category="flags">🇧🇬</button>
				<button type="button" class="emoji-option" data-emoji="🇬🇷" data-category="flags">🇬🇷</button>
				<button type="button" class="emoji-option" data-emoji="🇨🇾" data-category="flags">🇨🇾</button>
				<button type="button" class="emoji-option" data-emoji="🇲🇹" data-category="flags">🇲🇹</button>
				<button type="button" class="emoji-option" data-emoji="🇸🇮" data-category="flags">🇸🇮</button>
				<button type="button" class="emoji-option" data-emoji="🇸🇰" data-category="flags">🇸🇰</button>
				<button type="button" class="emoji-option" data-emoji="🇪🇪" data-category="flags">🇪🇪</button>
				<button type="button" class="emoji-option" data-emoji="🇱🇻" data-category="flags">🇱🇻</button>
				<button type="button" class="emoji-option" data-emoji="🇱🇹" data-category="flags">🇱🇹</button>
				<button type="button" class="emoji-option" data-emoji="🇨🇭" data-category="flags">🇨🇭</button>
				<button type="button" class="emoji-option" data-emoji="🇳🇿" data-category="flags">🇳🇿</button>
				<button type="button" class="emoji-option" data-emoji="🇨🇦" data-category="flags">🇨🇦</button>
				<button type="button" class="emoji-option" data-emoji="🇦🇺" data-category="flags">🇦🇺</button>
				<button type="button" class="emoji-option" data-emoji="🇺🇸" data-category="flags">🇺🇸</button>
				<button type="button" class="emoji-option" data-emoji="🇬🇧" data-category="flags">🇬🇧</button>
			</div>
		</div>

		<!-- Feature Edit Modal -->
		<!-- Feature Edit Modal (Wider layout) -->
		<div id="feature-edit-modal" class="category-edit-modal wide-modal">
			<div class="modal-content" style="max-width: 800px;">
				<h3><?php esc_html_e( 'Edit Feature', 'wp-configurator' ); ?></h3>
				<input type="hidden" id="edit-feature-index">

				<div class="form-grid">
					<div class="form-row">
						<label for="edit-feature-category"><?php esc_html_e( 'Category', 'wp-configurator' ); ?></label>
						<select id="edit-feature-category" class="regular-text"></select>
					</div>

					<div class="form-row">
						<label for="edit-feature-name"><?php esc_html_e( 'Feature Name', 'wp-configurator' ); ?></label>
						<input type="text" id="edit-feature-name" class="regular-text" placeholder="e.g., Contact Form">
					</div>

					<div class="form-row">
						<label for="edit-feature-icon"><?php esc_html_e( 'Icon', 'wp-configurator' ); ?></label>
						<div class="icon-input-wrapper">
							<input type="text" id="edit-feature-icon" class="regular-text" placeholder="📦">
							<button type="button" class="button open-emoji-picker" title="Select Emoji">😀</button>
						</div>
					</div>

					<div class="form-row">
						<label for="edit-feature-sku"><?php esc_html_e( 'SKU', 'wp-configurator' ); ?></label>
						<input type="text" id="edit-feature-sku" class="regular-text" placeholder="e.g., PROJ-001">
						<p class="description"><?php esc_html_e( 'Optional product/service code.', 'wp-configurator' ); ?></p>
					</div>

					<div class="form-row">
						<label for="edit-feature-price"><?php esc_html_e( 'Price (€)', 'wp-configurator' ); ?></label>
						<input type="number" id="edit-feature-price" class="small-text" step="0.01" min="0" placeholder="0.00">
					</div>

					<div class="form-row">
						<label for="edit-feature-billing"><?php esc_html_e( 'Billing Type', 'wp-configurator' ); ?></label>
						<select id="edit-feature-billing" class="regular-text">
							<option value="one-off">One-off</option>
							<option value="monthly">Monthly</option>
							<option value="quarterly">Quarterly</option>
							<option value="annual">Annual</option>
						</select>
					</div>

					<div class="form-row">
						<label class="checkbox-label">
							<input type="checkbox" id="edit-feature-enabled">
							<?php esc_html_e( 'Feature is enabled and visible to customers', 'wp-configurator' ); ?>
						</label>
					</div>
				</div>

				<div class="form-row full-width">
					<label for="edit-feature-description"><?php esc_html_e( 'Description', 'wp-configurator' ); ?></label>
					<?php
					wp_editor( '', 'edit-feature-description', array(
						'teeny' => true,
						'textarea_rows' => 4,
						'media_buttons' => false,
					) );
					?>
				</div>

				<div class="form-row full-width">
					<label for="edit-feature-incompatible"><?php esc_html_e( 'Incompatible with', 'wp-configurator' ); ?></label>
					<select id="edit-feature-incompatible" multiple size="6" style="width:100%; min-width:400px;">
						<!-- Options populated via JS -->
					</select>
					<p class="description"><?php esc_html_e( 'Hold Ctrl (Windows) or Cmd (Mac) to select multiple features that cannot be used together.', 'wp-configurator' ); ?></p>
				</div>

				<div class="modal-actions">
					<button type="button" class="button" id="cancel-feature-edit"><?php esc_html_e( 'Cancel', 'wp-configurator' ); ?></button>
					<button type="button" class="button button-delete" id="delete-feature-edit"><?php esc_html_e( 'Delete', 'wp-configurator' ); ?></button>
					<button type="button" class="button button-primary" id="save-feature-edit"><?php esc_html_e( 'Save Feature', 'wp-configurator' ); ?></button>
				</div>
			</div>
		</div>
