<?php
define('DEFAULT_LANGUAGE', 'en'); // Available values: en, zh-CN
define('DEFAULT_WEB_THEME', 'dark'); // Available values: dark, light; visitors can override it in the browser
define('SITE_NAME_EN', 'CS2 Loadout Manager'); // English name and fallback
define('SITE_NAME_ZH_CN', 'CS2 饰品管理器'); // Simplified Chinese name
define('AUTH_RATE_LIMIT_ATTEMPTS', 5); // Failed attempts allowed within the time window
define('AUTH_RATE_LIMIT_WINDOW_SECONDS', 1800); // Failure tracking window in seconds
define('AUTH_RATE_LIMIT_LOCK_SECONDS', 60); // Lock duration in seconds
define('ENABLE_SKIN_FUSION', true); // Allow cross-weapon paint combinations

define('SERVER_ADDRESS', ''); // Hostname or IP with port; leave empty to hide the Connect to Server button
define('SERVER_PASSWORD', ''); // Leave empty to launch CS2 directly; a value copies the console command instead
define('SITE_ACCESS_PASSWORD', ''); // Set a password to enable access protection
define('ADMIN_PASSWORD', ''); // Set a password to enable administrator mode

define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'your_db_name');
define('DB_USER', 'your_db_user');
define('DB_PASS', 'your_db_password');
