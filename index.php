<?php

require_once __DIR__ . '/class/bootstrap.php';
require __DIR__ . '/actions/bootstrap.php';
require __DIR__ . '/class/page_context.php';

$clientConfig = [
	'csrfToken' => csrfToken(),
	'stickerDataUrl' => dataFileUrl(stickerDataFile()),
	'stickerAliasDataUrl' => stickerAliasDataFile() !== '' ? dataFileUrl(stickerAliasDataFile()) : '',
	'keychainDataUrl' => dataFileUrl(keychainDataFile()),
	'keychainAliasDataUrl' => keychainAliasDataFile() !== '' ? dataFileUrl(keychainAliasDataFile()) : '',
	'paintKitDataUrl' => dataFileUrl(paintKitDataFile()),
	'paintKitAliasDataUrl' => paintKitAliasDataFile() !== '' ? dataFileUrl(paintKitAliasDataFile()) : '',
	'paintKitFinishBadges' => paintKitFinishBadges(),
	'requestedLoadoutPasswordId' => (string)($_GET['loadout_password_error'] ?? $_GET['loadout_password_required'] ?? ''),
	'requestedLoadoutPasswordTeam' => (string)($_GET['loadout_password_team'] ?? '1'),
	'hasLoadoutPasswordError' => isset($_GET['loadout_password_error']),
	'showAdminError' => $adminError !== '' && $accessGranted,
	'showAdminModal' => ($adminError !== '' || ($siteSettingsStatus ?? '') !== '') && $accessGranted,
	'showAdminAuthenticatedNotice' => ($adminAuthenticated ?? false) && $accessGranted,
	'floatingNotice' => ($floatingNoticeKey ?? '') !== '' ? t($floatingNoticeKey) : '',
	'text' => [
		'stickerSlotSettings' => t('sticker_slot_settings'),
		'stickerSaveFailed' => t('sticker_save_failed'),
		'dataLoadFailed' => t('data_load_failed'),
		'fusionSourceCount' => t('fusion_source_count'),
		'fusionNativeFinish' => t('fusion_native_finish'),
		'chooseFusionFinishFor' => t('choose_fusion_finish_for'),
		'noSticker' => t('no_sticker'),
		'stickerSlot' => t('sticker_slot'),
		'stickerSelectionSaved' => t('sticker_selection_saved'),
		'stickerSettingsSaved' => t('sticker_settings_saved'),
		'keychainSaveFailed' => t('keychain_save_failed'),
		'keychain' => t('keychain'),
		'noKeychain' => t('no_keychain'),
		'keychainSelectionSaved' => t('keychain_selection_saved'),
		'serverCommandCopied' => t('server_command_copied'),
		'serverCommandCopyFailed' => t('server_command_copy_failed'),
		'serverCommandClipboardNotice' => t('server_command_clipboard_notice'),
		'adminAuthenticated' => t('admin_authenticated'),
		'siteSettingsNameRequired' => t('site_settings_name_required'),
		'serverAddressInvalid' => t('server_address_invalid'),
		'serverPasswordInvalid' => t('server_password_invalid'),
		'validationRequired' => t('validation_required'),
		'validationNumberRange' => t('validation_number_range'),
		'validationDecimalRange' => t('validation_decimal_range'),
		'validationIntegerRange' => t('validation_integer_range'),
	],
];

require __DIR__ . '/views/layout/header.php';
require __DIR__ . '/views/page.php';
require __DIR__ . '/views/layout/footer.php';
