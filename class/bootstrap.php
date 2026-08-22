<?php

if (!defined('APP_ROOT')) {
	define('APP_ROOT', dirname(__DIR__));
}

require_once APP_ROOT . '/config.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/utils.php';
require_once __DIR__ . '/translations.php';
require_once __DIR__ . '/http.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/loadout.php';
require_once __DIR__ . '/site_settings.php';
require_once __DIR__ . '/weapon_data.php';
require_once __DIR__ . '/skin_settings.php';
require_once __DIR__ . '/application.php';
