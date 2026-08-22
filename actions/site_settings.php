<?php

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_site_settings') {
	$returnTo = safeReturnUrl($_POST['return_to'] ?? 'index.php');
	if (!isAdmin()) {
		http_response_code(403);
		header('Content-Type: text/plain; charset=utf-8');
		echo t('admin_required');
		exit;
	}

	$siteNameEn = $_POST['site_name_en'] ?? null;
	$siteNameZhCn = $_POST['site_name_zh_cn'] ?? null;
	$defaultLanguage = $_POST['default_language'] ?? null;
	$defaultWebTheme = $_POST['default_web_theme'] ?? null;
	$fusionValue = $_POST['enable_skin_fusion'] ?? null;
	$serverAddress = $_POST['server_address'] ?? null;
	$serverPassword = $_POST['server_password'] ?? null;
	$hasValidTypes = is_string($siteNameEn)
		&& is_string($siteNameZhCn)
		&& is_string($defaultLanguage)
		&& is_string($defaultWebTheme)
		&& is_string($serverAddress)
		&& is_string($serverPassword)
		&& ($fusionValue === null || $fusionValue === '1');
	$submittedSettings = $hasValidTypes ? [
		'site_name_en' => trim($siteNameEn),
		'site_name_zh_cn' => trim($siteNameZhCn),
		'default_language' => $defaultLanguage,
		'default_web_theme' => $defaultWebTheme,
		'enable_skin_fusion' => $fusionValue === '1' ? '1' : '0',
		'server_address' => trim($serverAddress),
		'server_password' => trim($serverPassword),
	] : [];
	$isValid = $hasValidTypes && isValidEditableSiteSettings($submittedSettings);
	if (!$isValid) {
		$_SESSION['cs2_site_settings_status'] = 'invalid';
		go($returnTo);
	}

	try {
		saveSiteSettingsToConfig($submittedSettings);
		$_SESSION['cs2_site_settings_status'] = 'saved';
	} catch (Throwable $exception) {
		error_log('Unable to save site settings: ' . $exception->getMessage());
		$_SESSION['cs2_site_settings_status'] = 'save_failed';
	}
	go($returnTo);
}

$siteSettingsStatus = (string)($_SESSION['cs2_site_settings_status'] ?? '');
unset($_SESSION['cs2_site_settings_status']);
