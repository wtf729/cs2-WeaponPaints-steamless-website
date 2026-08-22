<?php

$postAction = $_SERVER['REQUEST_METHOD'] === 'POST' ? (string)($_POST['action'] ?? '') : '';

require __DIR__ . '/access.php';

if ($accessGranted) {
	require __DIR__ . '/admin.php';
	require __DIR__ . '/site_settings.php';
	require __DIR__ . '/loadout.php';
	require __DIR__ . '/sticker.php';
	require __DIR__ . '/keychain.php';
	require __DIR__ . '/skin.php';
	require __DIR__ . '/music.php';
	require __DIR__ . '/pin.php';
	require __DIR__ . '/agent.php';
}

$floatingNoticeKey = pullFloatingNoticeKey();
if (!$accessGranted) {
	$floatingNoticeKey = '';
}
