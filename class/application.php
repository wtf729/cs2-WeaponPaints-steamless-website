<?php

$isHttps = (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off')
	|| (int)($_SERVER['SERVER_PORT'] ?? 0) === 443;
if (session_status() !== PHP_SESSION_ACTIVE) {
	$sessionCookie = session_get_cookie_params();
	session_set_cookie_params([
		'lifetime' => (int)($sessionCookie['lifetime'] ?? 0),
		'path' => (string)($sessionCookie['path'] ?? '') !== '' ? (string)$sessionCookie['path'] : '/',
		'domain' => (string)($sessionCookie['domain'] ?? ''),
		'secure' => $isHttps,
		'httponly' => true,
		'samesite' => 'Lax',
	]);
	session_start();
}

$presetTable = 'wp_presets';
$skinSettingsTable = 'wp_skin_settings_cache';
$availableLanguages = ['zh-CN' => '简体中文', 'en' => 'English'];
$languageCookieName = 'cs2_wp_lang';
$cookiePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
$cookiePath = $cookiePath === '' ? '/' : $cookiePath . '/';
$siteSettings = siteSettingDefaults();
setRuntimeSiteSettings($siteSettings);
$currentLanguage = siteSetting('default_language', 'zh-CN');
UtilsClass::setLanguage($currentLanguage);

$siteNames = [
	'en' => siteSetting('site_name_en'),
	'zh-CN' => siteSetting('site_name_zh_cn'),
];
$siteNameFallback = 'CS2 Loadout Manager';
$siteName = $siteNames[$currentLanguage] !== ''
	? $siteNames[$currentLanguage]
	: ($siteNames['en'] !== '' ? $siteNames['en'] : $siteNameFallback);
$teams = $currentLanguage === 'en'
	? [1 => 'Global', 2 => 'T', 3 => 'CT']
	: [1 => '全局', 2 => 'T 阵营', 3 => 'CT 阵营'];
$defaultWebTheme = siteSetting('default_web_theme', 'dark');
