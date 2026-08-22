			<div class="modal fade steamid-help-modal" id="steamIdHelpModal" tabindex="-1" aria-labelledby="steamIdHelpModalLabel" aria-hidden="true">
				<div class="modal-dialog modal-dialog-centered">
					<div class="modal-content">
						<div class="modal-header">
							<h2 class="modal-title fs-5" id="steamIdHelpModalLabel"><?= h(t('steamid_help_title')) ?></h2>
							<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= h(t('close')) ?>"></button>
						</div>
						<div class="modal-body steamid-help-body">
							<p><?= h(t('steamid_help_intro')) ?></p>
							<div class="steamid-help-options">
								<div class="steamid-help-option">
									<strong><?= h(t('steamid_help_standard_title')) ?></strong>
									<p><?= h(t('steamid_help_standard_body')) ?></p>
								</div>
								<div class="steamid-help-option">
									<strong><?= h(t('steamid_help_custom_title')) ?></strong>
									<p><?= h(t('steamid_help_custom_body_before')) ?> <a href="<?= h(t('steamid_help_custom_resolver_url')) ?>" target="_blank" rel="noopener noreferrer"><?= h(t('steamid_help_custom_resolver_label')) ?></a> <?= h(t('steamid_help_custom_body_after')) ?></p>
								</div>
							</div>
						</div>
						<div class="modal-footer">
							<button type="button" class="btn btn-primary" data-bs-dismiss="modal"><?= h(t('close')) ?></button>
						</div>
					</div>
				</div>
			</div>
