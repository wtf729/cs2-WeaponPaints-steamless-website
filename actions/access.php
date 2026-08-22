<?php

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !verifyCsrfRequest()) {
	rejectInvalidCsrf();
}

$db = new DataBase();
applySiteSettingsContext(siteSettingDefaults());

$message = '';
$error = '';
$accessError = '';
$accessRetryAfter = 0;
$adminError = '';
$adminRetryAfter = 0;
$action = $_GET['action'] ?? 'home';
$accessPassword = defined('SITE_ACCESS_PASSWORD') ? (string)SITE_ACCESS_PASSWORD : '';
$accessRequired = $accessPassword !== '';
$accessSessionKey = $accessRequired ? hash('sha256', $accessPassword) : '';
$accessGranted = !$accessRequired || (($_SESSION['cs2_site_access_granted'] ?? '') === $accessSessionKey);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'verify_access') {
	$submittedPassword = (string)($_POST['access_password'] ?? '');
	$accessRetryAfter = authRateLimit('access');
	if ($accessRetryAfter > 0) {
		$accessError = 'rate_limited';
		$action = 'access';
	} elseif ($accessRequired && hash_equals($accessPassword, $submittedPassword)) {
		authRateLimit('access', '', 'clear');
		$_SESSION['cs2_site_access_granted'] = $accessSessionKey;
		session_regenerate_id(true);
		go('index.php');
	} else {
		$accessRetryAfter = authRateLimit('access', '', 'fail');
		$accessError = $accessRetryAfter > 0 ? 'rate_limited' : 'invalid';
		$action = 'access';
	}
}

if (!$accessGranted) {
	$action = 'access';
}

if ($accessGranted) {
	ensurePresetTable($db, $presetTable);
	ensureSkinSettingsTable($db, $skinSettingsTable);
}
