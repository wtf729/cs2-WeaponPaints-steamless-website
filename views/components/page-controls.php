		<?php if ($accessGranted) : ?>
			<?php if (serverConnectUri() !== '') : ?>
				<?php if (serverPassword() !== '') : ?>
					<button class="server-connect-button" type="button" data-server-command="<?= h(serverConsoleCommand()) ?>" aria-haspopup="dialog" aria-label="<?= h(t('join_server')) ?>" title="<?= h(t('join_server')) ?>">
						<img src="assets/icons/cs2.png?v=<?= filemtime(__DIR__ . '/../../assets/icons/cs2.png') ?>" alt="" aria-hidden="true">
					</button>
				<?php else : ?>
					<a class="server-connect-button" href="<?= h(serverConnectUri()) ?>" aria-label="<?= h(t('join_server')) ?>" title="<?= h(t('join_server')) ?>">
						<img src="assets/icons/cs2.png?v=<?= filemtime(__DIR__ . '/../../assets/icons/cs2.png') ?>" alt="" aria-hidden="true">
					</a>
				<?php endif; ?>
			<?php endif; ?>
			<button class="admin-button<?= isAdmin() ? ' active' : '' ?>" type="button" data-bs-toggle="modal" data-bs-target="#adminModal" aria-label="<?= h(isAdmin() ? t('admin_enabled') : t('admin')) ?>" title="<?= h(isAdmin() ? t('admin_enabled') : t('admin')) ?>">
				<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3 4.5 6v5.2c0 4.6 3.1 8.2 7.5 9.8 4.4-1.6 7.5-5.2 7.5-9.8V6L12 3Z"></path><path d="M9.5 12.2 11.2 14l3.6-4"></path></svg>
			</button>
		<?php endif; ?>
		<nav class="language-switch" aria-label="<?= h(t('language')) ?>">
			<details class="language-menu">
				<summary class="language-button" aria-label="<?= h(t('language')) ?>" title="<?= h(t('language')) ?>">
					<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
						<circle cx="12" cy="12" r="9"></circle>
						<path d="M3 12h18M12 3c2.4 2.7 3.6 5.7 3.6 9s-1.2 6.3-3.6 9M12 3c-2.4 2.7-3.6 5.7-3.6 9s1.2 6.3 3.6 9"></path>
					</svg>
				</summary>
				<div class="language-dropdown">
					<?php foreach ($availableLanguages as $languageCode => $languageName) : ?>
						<a class="<?= $currentLanguage === $languageCode ? 'active' : '' ?>" href="<?= h(languageUrl($languageCode)) ?>"><?= h($languageName) ?></a>
					<?php endforeach; ?>
				</div>
			</details>
		</nav>
		<button class="theme-switch" type="button" data-theme-toggle data-light-label="<?= h(t('switch_to_light_theme')) ?>" data-dark-label="<?= h(t('switch_to_dark_theme')) ?>" aria-label="<?= h(t($defaultWebTheme === 'dark' ? 'switch_to_light_theme' : 'switch_to_dark_theme')) ?>" title="<?= h(t($defaultWebTheme === 'dark' ? 'switch_to_light_theme' : 'switch_to_dark_theme')) ?>">
			<svg class="theme-icon theme-icon-dark" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
				<circle cx="12" cy="12" r="4"></circle>
				<path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"></path>
			</svg>
			<svg class="theme-icon theme-icon-light" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
				<path d="M20.5 15.2A8.5 8.5 0 0 1 8.8 3.5 8.5 8.5 0 1 0 20.5 15.2Z"></path>
			</svg>
		</button>
