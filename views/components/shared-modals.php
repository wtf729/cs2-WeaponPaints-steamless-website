	<div class="modal fade sticker-picker-modal" id="stickerPickerModal" tabindex="-1" aria-hidden="true">
		<div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title"><?= h(t('choose_sticker')) ?></h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= h(t('close')) ?>"></button>
				</div>
				<div class="modal-body picker-modal-body" aria-busy="false" data-sticker-picker-body>
					<div class="picker-loading-state" role="status" aria-live="polite" aria-atomic="true" data-sticker-loading hidden>
						<span class="picker-loading-label"><?= h(t('loading')) ?></span>
						<span class="cs2-spinner cs2-spinner--lg" aria-hidden="true"></span>
					</div>
					<div class="picker-search-bar">
						<input type="search" class="form-control sticker-search" placeholder="<?= h(t('search_sticker')) ?>" autocomplete="off">
					</div>
					<div class="sticker-picker-grid picker-results-scroll" data-sticker-results data-search-more-hint="<?= h(t('sticker_search_more_hint')) ?>"></div>
				</div>
			</div>
		</div>
	</div>
	<div class="modal fade sticker-advanced-modal" id="stickerAdvancedModal" tabindex="-1" aria-hidden="true">
		<div class="modal-dialog modal-dialog-centered">
			<form method="post" class="modal-content" data-sticker-advanced-form>
				<?= csrfInput() ?>
				<input type="hidden" name="action" value="save_sticker_slot">
				<input type="hidden" name="id" value="<?= h($currentPreset['steamid'] ?? '') ?>" data-sticker-advanced-id>
				<input type="hidden" name="team" value="<?= h((string)($team ?? 1)) ?>" data-sticker-advanced-team>
				<input type="hidden" name="weapon_defindex" value="" data-sticker-advanced-defindex>
				<input type="hidden" name="sticker_slot" value="" data-sticker-advanced-slot>
				<div class="modal-header">
					<div>
						<h5 class="modal-title" data-sticker-advanced-title><?= h(t('sticker_slot_settings')) ?></h5>
						<div class="sticker-advanced-subtitle" data-sticker-advanced-name></div>
					</div>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= h(t('close')) ?>"></button>
				</div>
				<div class="modal-body sticker-advanced-body">
					<?php $stickerParams = [
						'wear' => [t('sticker_wear'), '0', '1', '0.01', '0.00'],
						'x' => [t('sticker_x'), '-1', '1', '0.01', '0.00'],
						'y' => [t('sticker_y'), '-1', '1', '0.01', '0.00'],
						'scale' => [t('sticker_scale'), '0.2', '5', '0.01', '1.00'],
						'rotation' => [t('sticker_rotation'), '0', '360', '1', '0'],
					]; ?>
					<?php foreach ($stickerParams as $paramKey => $paramConfig) : ?>
						<div class="sticker-advanced-row" data-sticker-param="<?= h($paramKey) ?>">
							<label><?= h($paramConfig[0]) ?></label>
							<div class="sticker-advanced-controls">
								<input type="range" min="<?= h($paramConfig[1]) ?>" max="<?= h($paramConfig[2]) ?>" step="<?= h($paramConfig[3]) ?>" value="<?= h($paramConfig[4]) ?>" data-sticker-param-range>
								<input type="number" name="sticker_<?= h($paramKey) ?>" min="<?= h($paramConfig[1]) ?>" max="<?= h($paramConfig[2]) ?>" step="<?= h($paramConfig[3]) ?>" value="<?= h($paramConfig[4]) ?>" class="form-control" data-sticker-param-number>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-outline-light" data-sticker-advanced-reset><?= h(t('reset')) ?></button>
					<button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= h(t('cancel')) ?></button>
					<button type="submit" class="btn btn-primary"><?= h(t('save')) ?></button>
				</div>
			</form>
		</div>
	</div>
	<div class="modal fade keychain-picker-modal" id="keychainPickerModal" tabindex="-1" aria-hidden="true">
		<div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title"><?= h(t('choose_keychain')) ?></h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= h(t('close')) ?>"></button>
				</div>
				<div class="modal-body picker-modal-body" aria-busy="false" data-keychain-picker-body>
					<div class="picker-loading-state" role="status" aria-live="polite" aria-atomic="true" data-keychain-loading hidden>
						<span class="picker-loading-label"><?= h(t('loading')) ?></span>
						<span class="cs2-spinner cs2-spinner--lg" aria-hidden="true"></span>
					</div>
					<div class="picker-search-bar">
						<input type="search" class="form-control keychain-search" placeholder="<?= h(t('search_keychain')) ?>" autocomplete="off">
					</div>
					<div class="keychain-picker-grid picker-results-scroll" data-keychain-results></div>
				</div>
			</div>
		</div>
	</div>
	<?php if (skinFusionEnabled() && $action === 'edit' && $currentPreset) : ?>
		<div class="modal fade fusion-picker-modal" id="fusionPickerModal" tabindex="-1" aria-hidden="true">
			<div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered">
				<div class="modal-content">
					<div class="modal-header">
						<div>
							<h5 class="modal-title" data-fusion-picker-title><?= h(t('choose_fusion_finish')) ?></h5>
							<div class="modal-subtitle"><?= h(t('fusion_experimental_hint')) ?></div>
						</div>
						<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= h(t('close')) ?>"></button>
					</div>
					<div class="modal-body picker-modal-body" aria-busy="false" data-fusion-picker-body>
						<div class="picker-loading-state" role="status" aria-live="polite" aria-atomic="true" data-fusion-loading hidden>
							<span class="picker-loading-label"><?= h(t('loading')) ?></span>
							<span class="cs2-spinner cs2-spinner--lg" aria-hidden="true"></span>
						</div>
						<div class="picker-search-bar">
							<input type="search" class="form-control fusion-search" placeholder="<?= h(t('search_fusion_finish')) ?>" autocomplete="off">
						</div>
						<div class="fusion-picker-grid picker-results-scroll" data-fusion-results data-search-more-hint="<?= h(t('fusion_search_more_hint')) ?>"></div>
					</div>
				</div>
			</div>
		</div>
		<div class="modal fade fusion-source-modal" id="fusionSourceModal" tabindex="-1" aria-hidden="true">
			<div class="modal-dialog modal-dialog-scrollable modal-dialog-centered">
				<div class="modal-content">
					<div class="modal-header">
						<div>
							<h5 class="modal-title"><?= h(t('fusion_sources_title')) ?></h5>
							<div class="modal-subtitle" data-fusion-source-paint-name></div>
						</div>
					</div>
					<div class="modal-body">
						<div class="fusion-source-grid" data-fusion-source-results></div>
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= h(t('back')) ?></button>
					</div>
				</div>
			</div>
		</div>
		<form method="post" id="fusionSkinForm" class="d-none">
			<?= csrfInput() ?>
			<input type="hidden" name="action" value="save_skin">
			<input type="hidden" name="id" value="<?= h($currentPreset['steamid']) ?>">
			<input type="hidden" name="team" value="<?= h((string)$team) ?>">
			<input type="hidden" name="skin_forma" value="" data-fusion-forma>
		</form>
	<?php endif; ?>
	<?php if ($accessGranted) : ?>
		<div class="modal fade" id="loadoutPasswordModal" tabindex="-1" aria-hidden="true">
			<div class="modal-dialog modal-dialog-centered modal-sm">
				<form method="post" class="modal-content">
					<?= csrfInput() ?>
					<input type="hidden" name="action" value="verify_loadout_password">
					<input type="hidden" name="id" value="" data-loadout-password-id-input>
					<input type="hidden" name="team" value="1" data-loadout-password-team-input>
					<div class="modal-header">
						<div>
							<h5 class="modal-title"><?= h(t('enter_loadout_password')) ?></h5>
							<div class="modal-subtitle" data-loadout-password-label></div>
						</div>
						<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= h(t('cancel')) ?>"></button>
					</div>
					<div class="modal-body form-grid">
						<p class="hint"><?= h(t('loadout_password_prompt')) ?></p>
						<div class="alert alert-danger d-none" data-loadout-password-error><?= h(isset($_GET['loadout_password_rate_limited']) ? sprintf(t('auth_rate_limited'), max(1, (int)($_GET['retry_after'] ?? 1))) : t('loadout_password_incorrect')) ?></div>
						<label><?= h(t('enter_loadout_password')) ?>
							<input class="form-control" type="password" name="loadout_password" autocomplete="one-time-code" required data-loadout-password-modal-input>
						</label>
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= h(t('cancel')) ?></button>
						<button type="submit" class="btn btn-primary"><?= h(t('edit')) ?></button>
					</div>
				</form>
			</div>
		</div>

		<?php if (serverAddress() !== '' && serverPassword() !== '') : ?>
			<div class="modal fade" id="serverCommandModal" tabindex="-1" aria-hidden="true">
				<div class="modal-dialog modal-dialog-centered">
					<div class="modal-content">
						<div class="modal-header">
							<h5 class="modal-title"><?= h(t('join_server')) ?></h5>
							<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= h(t('close')) ?>"></button>
						</div>
						<div class="modal-body form-grid">
							<p class="hint" role="status" data-server-command-status><?= h(t('server_command_copied')) ?></p>
							<label><?= h(t('server_command_label')) ?>
								<input class="form-control" type="text" value="<?= h(serverConsoleCommand()) ?>" readonly data-server-command-display>
							</label>
						</div>
						<div class="modal-footer">
							<button type="button" class="btn btn-primary" data-bs-dismiss="modal"><?= h(t('close')) ?></button>
						</div>
					</div>
				</div>
			</div>
		<?php endif; ?>

		<div class="modal fade" id="adminModal" tabindex="-1" aria-hidden="true">
			<div class="modal-dialog modal-dialog-centered modal-dialog-scrollable <?= isAdmin() ? 'modal-lg' : 'modal-sm' ?>">
				<div class="modal-content">
					<div class="modal-header<?= isAdmin() ? ' admin-settings-modal-header' : '' ?>">
						<?php if (isAdmin()) : ?>
							<div class="admin-settings-heading">
								<div class="admin-settings-heading-row">
									<h5 class="modal-title"><?= h(t('site_settings')) ?></h5>
									<span class="admin-mode-badge"><span aria-hidden="true"></span><?= h(t('admin_mode_badge')) ?></span>
								</div>
							</div>
						<?php else : ?>
							<h5 class="modal-title"><?= h(t('admin_login')) ?></h5>
						<?php endif; ?>
						<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= h(t('cancel')) ?>"></button>
					</div>
					<div class="modal-body<?= isAdmin() ? ' admin-settings-modal-body' : '' ?>">
						<?php if (adminPassword() === '') : ?>
							<div class="alert alert-info mb-0"><?= h(t('admin_disabled')) ?></div>
						<?php elseif (isAdmin()) : ?>
							<?php if (($siteSettingsStatus ?? '') !== '') : ?>
								<div class="alert <?= $siteSettingsStatus === 'saved' ? 'alert-success admin-settings-status' : 'alert-danger' ?>" role="status"<?= $siteSettingsStatus === 'saved' ? ' data-site-settings-success' : '' ?>>
									<?= h(t('site_settings_' . $siteSettingsStatus)) ?>
								</div>
							<?php endif; ?>
							<form method="post" class="admin-settings-form" id="siteSettingsForm">
								<?= csrfInput() ?>
								<input type="hidden" name="action" value="save_site_settings">
								<input type="hidden" name="return_to" value="<?= h($returnTo) ?>">
								<div class="admin-settings-layout">
									<div class="admin-settings-section">
										<div class="admin-settings-fields">
											<label><?= h(t('site_name_en')) ?>
												<input class="form-control" type="text" name="site_name_en" value="<?= h(siteSetting('site_name_en')) ?>" maxlength="100" data-site-name-input>
											</label>
											<label><?= h(t('site_name_zh_cn')) ?>
												<input class="form-control" type="text" name="site_name_zh_cn" value="<?= h(siteSetting('site_name_zh_cn')) ?>" maxlength="100" data-site-name-input>
											</label>
										</div>
									</div>
									<div class="admin-settings-section">
										<div class="admin-settings-fields">
											<label><?= h(t('default_language')) ?>
												<select class="form-select" name="default_language">
													<?php foreach ($availableLanguages as $languageCode => $languageName) : ?>
														<option value="<?= h($languageCode) ?>"<?= siteSetting('default_language') === $languageCode ? ' selected' : '' ?>><?= h($languageName) ?></option>
													<?php endforeach; ?>
												</select>
											</label>
											<label><?= h(t('default_theme')) ?>
												<select class="form-select" name="default_web_theme">
													<option value="dark"<?= siteSetting('default_web_theme') === 'dark' ? ' selected' : '' ?>><?= h(t('theme_dark')) ?></option>
													<option value="light"<?= siteSetting('default_web_theme') === 'light' ? ' selected' : '' ?>><?= h(t('theme_light')) ?></option>
												</select>
											</label>
										</div>
									</div>
									<div class="admin-settings-section admin-settings-section-wide">
										<div class="admin-settings-fields admin-settings-fields-split">
											<label><?= h(t('server_address')) ?>
												<input class="form-control" type="text" name="server_address" value="<?= h(serverAddress()) ?>" maxlength="261" autocomplete="off" spellcheck="false" data-server-address-input>
												<small><?= h(t('server_address_hint')) ?></small>
											</label>
											<label><?= h(t('server_password')) ?>
												<input class="form-control" type="text" name="server_password" value="<?= h(serverPassword()) ?>" maxlength="128" autocomplete="off" spellcheck="false" pattern="[^\s;&quot;;\\]+" data-server-password-input>
												<small><?= h(t('server_password_hint')) ?></small>
											</label>
										</div>
									</div>
									<div class="admin-settings-section admin-settings-section-wide">
										<label class="admin-setting-toggle form-check form-switch" for="enableSkinFusionSetting">
											<input class="form-check-input" type="checkbox" role="switch" id="enableSkinFusionSetting" name="enable_skin_fusion" value="1"<?= siteSettingEnabled('enable_skin_fusion') ? ' checked' : '' ?>>
											<span>
												<strong><?= h(t('enable_skin_fusion')) ?></strong>
												<small><?= h(t('enable_skin_fusion_hint')) ?></small>
											</span>
										</label>
									</div>
								</div>
							</form>
						<?php else : ?>
							<?php if ($adminError) : ?>
								<div class="alert alert-danger"><?= h($adminError === 'rate_limited' ? sprintf(t('auth_rate_limited'), max(1, $adminRetryAfter)) : t('admin_invalid')) ?></div>
							<?php endif; ?>
							<form method="post" class="form-grid" id="adminLoginForm">
								<?= csrfInput() ?>
								<input type="hidden" name="action" value="admin_login">
								<input type="hidden" name="return_to" value="<?= h($returnTo) ?>">
								<label><?= h(t('admin_password')) ?>
									<input class="form-control" type="password" name="admin_password" autocomplete="current-password" required>
								</label>
							</form>
						<?php endif; ?>
					</div>
					<?php if (adminPassword() !== '') : ?>
						<div class="modal-footer<?= isAdmin() ? ' admin-settings-footer' : '' ?>">
							<?php if (isAdmin()) : ?>
								<form method="post" class="admin-settings-logout">
									<?= csrfInput() ?>
									<input type="hidden" name="action" value="admin_logout">
									<input type="hidden" name="return_to" value="<?= h($returnTo) ?>">
									<button class="btn btn-outline-danger" type="submit"><?= h(t('admin_exit')) ?></button>
								</form>
								<div class="admin-settings-footer-actions">
									<button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= h(t('back')) ?></button>
									<button class="btn btn-primary" type="submit" form="siteSettingsForm"><?= h(t('save_settings')) ?></button>
								</div>
							<?php else : ?>
								<button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= h(t('back')) ?></button>
								<button class="btn btn-primary" type="submit" form="adminLoginForm"><?= h(t('admin_enter')) ?></button>
							<?php endif; ?>
						</div>
					<?php endif; ?>
				</div>
			</div>
		</div>
	<?php endif; ?>
