			<a class="back-link" href="index.php"><?= h(t('back_home')) ?></a>
			<section class="panel loadout-info-panel create-loadout-panel">
				<div class="identity-panel-head">
					<div>
						<h1><?= h(t('new_preset')) ?></h1>
						<p><?= h(t('basic_info')) ?></p>
					</div>
					<span class="identity-status" data-loadout-password-status data-enabled-label="<?= h(t('loadout_password_enabled')) ?>" data-disabled-label="<?= h(t('loadout_password_disabled')) ?>"><?= h(t('loadout_password_disabled')) ?></span>
				</div>
				<?php if ($error) : ?><div class="alert alert-danger"><?= h($error) ?></div><?php endif; ?>
				<form method="post" class="identity-form loadout-info-form">
					<?= csrfInput() ?>
					<input type="hidden" name="action" value="create_preset">
					<div class="identity-main-fields">
						<div class="identity-field">
							<div class="identity-field-heading">
								<label for="newPresetSteamId">Steam64 ID</label>
								<button class="steamid-help-button" type="button" data-bs-toggle="modal" data-bs-target="#steamIdHelpModal" aria-label="<?= h(t('steamid_help_open')) ?>" title="<?= h(t('steamid_help_open')) ?>">
									<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
										<circle cx="12" cy="12" r="9"></circle>
										<path d="M9.8 9.2a2.4 2.4 0 1 1 3.1 2.3c-.8.3-1.3.9-1.3 1.7v.4"></path>
										<circle class="steamid-help-dot" cx="12" cy="17" r=".85"></circle>
									</svg>
								</button>
							</div>
							<input id="newPresetSteamId" class="form-control" name="steamid" value="<?= h($_POST['steamid'] ?? '') ?>" inputmode="numeric" pattern="\d{5,18}" minlength="5" maxlength="18" autocomplete="off" placeholder="<?= h(t('steamid_placeholder')) ?>" required>
						</div>
						<label><?= h(t('nickname')) ?>
							<input class="form-control" name="nickname" value="<?= h($_POST['nickname'] ?? '') ?>" maxlength="100" autocomplete="off" placeholder="<?= h(t('nickname_placeholder')) ?>">
						</label>
					</div>
					<div class="identity-loadout-password-settings">
						<div class="loadout-password-setting-copy">
							<strong><?= h(t('loadout_password_protection')) ?></strong>
							<small><?= h(t('loadout_password_optional_hint')) ?></small>
						</div>
						<label class="loadout-password-toggle form-check form-switch">
							<input class="form-check-input" type="checkbox" role="switch" name="enable_loadout_password" value="1" data-loadout-password-toggle <?= isset($_POST['enable_loadout_password']) ? 'checked' : '' ?>>
							<span><?= h(t('enable_loadout_password')) ?></span>
						</label>
						<label class="loadout-password-input-wrap<?= isset($_POST['enable_loadout_password']) ? '' : ' is-inactive' ?>" data-loadout-password-input-wrap>
							<span class="visually-hidden"><?= h(t('enter_loadout_password')) ?></span>
							<input class="form-control" type="password" name="loadout_password" autocomplete="one-time-code" placeholder="<?= h(t('loadout_password_set_placeholder')) ?>" data-loadout-password-input data-loadout-password-required-when-enabled>
						</label>
					</div>
					<div class="identity-form-actions">
						<button class="btn btn-primary" type="submit"><?= h(t('create')) ?></button>
					</div>
				</form>
			</section>

			<?php require __DIR__ . '/../components/steamid-help-modal.php'; ?>
