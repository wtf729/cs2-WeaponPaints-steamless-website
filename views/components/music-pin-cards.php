				<?php if ($team === 1) : ?>
				<?php
					$currentMusicId = $selectedMusic !== null ? (int)$selectedMusic : 0;
					$currentMusic = $music[$currentMusicId] ?? unknownItemData($currentMusicId);
					$musicAliases = itemAliasNamesFromJson(alternateLanguageDataName('music'));
				?>
				<div class="skin-card featured">
					<div class="card-title-wrap">
						<span><?= h(t('music_kit')) ?></span>
						<h2><?= h($currentMusic['name']) ?></h2>
					</div>
					<?php if (!empty($currentMusic['image'])) : ?>
						<img src="img/skins/music_kit.png" data-remote-src="<?= h($currentMusic['image']) ?>" class="skin-image" alt="">
					<?php else : ?>
						<img src="img/skins/music_kit.png" class="skin-image" alt="">
					<?php endif; ?>
					<form method="post" class="modal-form">
						<?= csrfInput() ?>
						<input type="hidden" name="action" value="save_music">
						<input type="hidden" name="id" value="<?= h($currentPreset['steamid']) ?>">
						<input type="hidden" name="team" value="<?= $team ?>">
						<div class="settings-row">
							<button type="button" class="btn btn-sm btn-outline-light" data-bs-toggle="modal" data-bs-target="#musicModal">
								<?= h(t('select')) ?>
							</button>
						</div>
						<div class="modal fade skin-picker-modal" id="musicModal" tabindex="-1" aria-hidden="true">
							<div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered">
								<div class="modal-content">
									<div class="modal-header">
										<h5 class="modal-title"><?= h(t('choose_music')) ?></h5>
										<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= h(t('close')) ?>"></button>
									</div>
									<div class="modal-body picker-modal-body">
										<div class="picker-search-bar">
											<input type="search" class="form-control picker-search" placeholder="<?= h(t('search_music')) ?>" autocomplete="off" data-picker-search>
										</div>
										<div class="skin-picker-grid picker-results-scroll">
											<?php foreach ($music as $musicId => $musicKit) : ?>
												<?php $musicImage = (string)($musicKit['image'] ?? ''); ?>
												<?php $musicSearchText = trim(($musicKit['name'] ?? '') . ' ' . ($musicAliases[(int)$musicId] ?? '')); ?>
												<button type="submit" name="music_id" value="<?= (int)$musicId ?>" class="skin-result <?= $currentMusicId === (int)$musicId ? 'active' : '' ?>" data-picker-result data-search="<?= h($musicSearchText) ?>">
													<?php if ($musicImage !== '') : ?>
														<img src="img/skins/music_kit.png" data-picker-remote-src="<?= h($musicImage) ?>" alt="">
													<?php else : ?>
														<img src="img/skins/music_kit.png" alt="">
													<?php endif; ?>
													<span><?= h($musicKit['name']) ?></span>
												</button>
											<?php endforeach; ?>
										</div>
									</div>
								</div>
							</div>
						</div>
					</form>
				</div>

				<?php endif; ?>
				<?php
				$currentPinId = $selectedPin !== null ? (int)$selectedPin : 0;
				$currentPin = $pins[$currentPinId] ?? unknownItemData($currentPinId);
				$pinAliases = itemAliasNamesFromJson(alternateLanguageDataName('collectibles'));
				?>
				<div class="skin-card featured">
					<div class="card-title-wrap">
						<span><?= h(t('pin')) ?></span>
						<h2><?= h($currentPin['name']) ?></h2>
					</div>
					<?php if (!empty($currentPin['image'])) : ?>
						<img src="img/skins/pin.png" data-remote-src="<?= h($currentPin['image']) ?>" class="skin-image" alt="">
					<?php else : ?>
						<img src="img/skins/pin.png" class="skin-image" alt="">
					<?php endif; ?>
					<form method="post" class="modal-form">
						<?= csrfInput() ?>
						<input type="hidden" name="action" value="save_pin">
						<input type="hidden" name="id" value="<?= h($currentPreset['steamid']) ?>">
						<input type="hidden" name="team" value="<?= $team ?>">
						<div class="settings-row">
							<button type="button" class="btn btn-sm btn-outline-light" data-bs-toggle="modal" data-bs-target="#pinModal">
								<?= h(t('select')) ?>
							</button>
						</div>
						<div class="modal fade skin-picker-modal" id="pinModal" tabindex="-1" aria-hidden="true" data-picker-deferred-init>
							<div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered">
								<div class="modal-content">
									<div class="modal-header">
										<h5 class="modal-title"><?= h(t('choose_pin')) ?></h5>
										<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= h(t('close')) ?>"></button>
									</div>
									<div class="modal-body picker-modal-body" aria-busy="false" data-picker-deferred-body>
										<div class="picker-loading-state" role="status" aria-live="polite" aria-atomic="true" data-picker-deferred-loading hidden>
											<span class="picker-loading-label"><?= h(t('loading')) ?></span>
											<span class="cs2-spinner cs2-spinner--lg" aria-hidden="true"></span>
										</div>
										<div class="picker-search-bar">
											<input type="search" class="form-control picker-search" placeholder="<?= h(t('search_pin')) ?>" autocomplete="off" data-picker-search>
										</div>
										<div class="skin-picker-grid picker-results-scroll">
											<?php foreach ($pins as $pinId => $pin) : ?>
												<?php $pinImage = (string)($pin['image'] ?? ''); ?>
												<?php $pinSearchText = trim(($pin['name'] ?? '') . ' ' . ($pinAliases[(int)$pinId] ?? '')); ?>
												<button type="submit" name="pin_id" value="<?= (int)$pinId ?>" class="skin-result <?= $currentPinId === (int)$pinId ? 'active' : '' ?>" data-picker-result data-search="<?= h($pinSearchText) ?>">
													<?php if ($pinImage !== '') : ?>
														<img src="img/skins/pin.png" data-picker-remote-src="<?= h($pinImage) ?>" alt="">
													<?php else : ?>
														<img src="img/skins/pin.png" alt="">
													<?php endif; ?>
													<span><?= h($pin['name']) ?></span>
												</button>
											<?php endforeach; ?>
										</div>
									</div>
								</div>
							</div>
						</div>
					</form>
				</div>
