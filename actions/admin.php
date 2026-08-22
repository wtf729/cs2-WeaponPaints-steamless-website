<?php

if ($accessGranted && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'admin_login') {
	$returnTo = safeReturnUrl($_POST['return_to'] ?? 'index.php');
	$submittedPassword = (string)($_POST['admin_password'] ?? '');
	$adminRetryAfter = authRateLimit('admin');
	if ($adminRetryAfter > 0) {
		$_SESSION['cs2_admin_error'] = 'rate_limited';
		$_SESSION['cs2_admin_retry_after'] = $adminRetryAfter;
		go($returnTo);
	}
	if (adminPassword() !== '' && hash_equals(adminPassword(), $submittedPassword)) {
		authRateLimit('admin', '', 'clear');
		$_SESSION['is_admin'] = true;
		$_SESSION['cs2_admin_key'] = hash('sha256', adminPassword());
		session_regenerate_id(true);
		$_SESSION['cs2_admin_authenticated'] = true;
		go($returnTo);
	}
	$adminRetryAfter = authRateLimit('admin', '', 'fail');
	$_SESSION['cs2_admin_error'] = $adminRetryAfter > 0 ? 'rate_limited' : 'invalid';
	$_SESSION['cs2_admin_retry_after'] = $adminRetryAfter;
	go($returnTo);
}

if ($accessGranted && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'admin_logout') {
	unset($_SESSION['is_admin'], $_SESSION['cs2_admin_key']);
	session_regenerate_id(true);
	go(safeReturnUrl($_POST['return_to'] ?? 'index.php'));
}

if ($accessGranted && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'verify_loadout_password') {
	$id = cleanSteamId($_POST['id'] ?? '');
	$team = selectedTeam();
	$preset = findPreset($db, $presetTable, $id);
	$submittedLoadoutPassword = (string)($_POST['loadout_password'] ?? '');
	$loadoutRetryAfter = authRateLimit('loadout', $id);
	if ($loadoutRetryAfter > 0) {
		go('index.php?action=list&loadout_password_error=' . rawurlencode($id) . '&loadout_password_rate_limited=1&retry_after=' . $loadoutRetryAfter . '&loadout_password_team=' . $team);
	}
	if ($preset && (isAdmin() || !loadoutHasPassword($preset) || password_verify($submittedLoadoutPassword, (string)$preset['loadout_password_hash']))) {
		authRateLimit('loadout', $id, 'clear');
		if (loadoutHasPassword($preset) && !isAdmin()) {
			markLoadoutPasswordVerified($preset);
		}
		session_regenerate_id(true);
		go(editUrl($preset, $team));
	}
	$loadoutRetryAfter = authRateLimit('loadout', $id, 'fail');
	$rateLimitQuery = $loadoutRetryAfter > 0 ? '&loadout_password_rate_limited=1&retry_after=' . $loadoutRetryAfter : '';
	go('index.php?action=list&loadout_password_error=' . rawurlencode($id) . $rateLimitQuery . '&loadout_password_team=' . $team);
}

$adminError = (string)($_SESSION['cs2_admin_error'] ?? '');
$adminRetryAfter = max(0, (int)($_SESSION['cs2_admin_retry_after'] ?? 0));
$adminAuthenticated = !empty($_SESSION['cs2_admin_authenticated']);
unset($_SESSION['cs2_admin_error'], $_SESSION['cs2_admin_retry_after'], $_SESSION['cs2_admin_authenticated']);
