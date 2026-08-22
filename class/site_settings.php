<?php

function siteSettingDefaults()
{
	$defaultLanguage = defined('DEFAULT_LANGUAGE') ? (string)DEFAULT_LANGUAGE : 'zh-CN';
	if (!in_array($defaultLanguage, ['zh-CN', 'en'], true)) {
		$defaultLanguage = 'zh-CN';
	}
	$defaultTheme = defined('DEFAULT_WEB_THEME') ? strtolower(trim((string)DEFAULT_WEB_THEME)) : 'dark';
	if (!in_array($defaultTheme, ['dark', 'light'], true)) {
		$defaultTheme = 'dark';
	}
	$serverAddress = defined('SERVER_ADDRESS') ? trim((string)SERVER_ADDRESS) : '';
	$serverPassword = defined('SERVER_PASSWORD') ? trim((string)SERVER_PASSWORD) : '';
	if (!isValidServerAddress($serverAddress)) {
		$serverAddress = '';
	}
	if (!isValidServerPassword($serverPassword)) {
		$serverPassword = '';
	}

	return [
		'site_name_en' => defined('SITE_NAME_EN') ? trim((string)SITE_NAME_EN) : 'CS2 Loadout Manager',
		'site_name_zh_cn' => defined('SITE_NAME_ZH_CN') ? trim((string)SITE_NAME_ZH_CN) : '',
		'default_language' => $defaultLanguage,
		'default_web_theme' => $defaultTheme,
		'enable_skin_fusion' => defined('ENABLE_SKIN_FUSION') && ENABLE_SKIN_FUSION === true ? '1' : '0',
		'server_address' => $serverAddress,
		'server_password' => $serverPassword,
	];
}

function isValidServerAddress($value)
{
	if (!is_string($value)) {
		return false;
	}
	$value = trim($value);
	if ($value === '') {
		return true;
	}
	if (strlen($value) > 261) {
		return false;
	}

	$host = '';
	$port = 0;
	if (preg_match('/^\[([^\]]+)\]:(\d{1,5})$/D', $value, $matches) === 1) {
		$host = $matches[1];
		$port = (int)$matches[2];
		if (filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) === false) {
			return false;
		}
	} elseif (preg_match('/^([^:]+):(\d{1,5})$/D', $value, $matches) === 1) {
		$host = $matches[1];
		$port = (int)$matches[2];
		$isIpv4 = filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false;
		$isHostname = preg_match('/^(?=.{1,253}$)(?:[A-Za-z0-9](?:[A-Za-z0-9-]{0,61}[A-Za-z0-9])?\.)*[A-Za-z0-9](?:[A-Za-z0-9-]{0,61}[A-Za-z0-9])?$/D', $host) === 1;
		if (!$isIpv4 && !$isHostname) {
			return false;
		}
	} else {
		return false;
	}

	return $host !== '' && $port >= 1 && $port <= 65535;
}

function isValidServerPassword($value)
{
	if (!is_string($value)) {
		return false;
	}
	$value = trim($value);
	return $value === '' || (
		strlen($value) <= 128
		&& preg_match('/[\x00-\x20\x7F;"\\\\]/', $value) !== 1
	);
}

function isValidSiteName($value)
{
	return is_string($value)
		&& (function_exists('mb_strlen') ? mb_strlen(trim($value), 'UTF-8') : strlen(trim($value))) <= 100
		&& preg_match('/[\x00-\x1F\x7F]/', $value) !== 1;
}

function normalizedSiteSettings(array $settings)
{
	$defaults = siteSettingDefaults();
	$normalized = $defaults;
	foreach ($defaults as $key => $defaultValue) {
		if (array_key_exists($key, $settings)) {
			$normalized[$key] = trim((string)$settings[$key]);
		}
	}
	if (!in_array($normalized['default_language'], ['zh-CN', 'en'], true)) {
		$normalized['default_language'] = $defaults['default_language'];
	}
	if (!in_array($normalized['default_web_theme'], ['dark', 'light'], true)) {
		$normalized['default_web_theme'] = $defaults['default_web_theme'];
	}
	$normalized['enable_skin_fusion'] = in_array(
		strtolower($normalized['enable_skin_fusion']),
		['1', 'true', 'on', 'yes'],
		true
	) ? '1' : '0';
	if (!isValidServerAddress($normalized['server_address'])) {
		$normalized['server_address'] = $defaults['server_address'];
	}
	if (!isValidServerPassword($normalized['server_password'])) {
		$normalized['server_password'] = $defaults['server_password'];
	}
	return $normalized;
}

function setRuntimeSiteSettings(array $settings)
{
	$GLOBALS['siteSettings'] = normalizedSiteSettings($settings);
}

function siteSetting($key, $fallback = '')
{
	$settings = $GLOBALS['siteSettings'] ?? siteSettingDefaults();
	return array_key_exists($key, $settings) ? (string)$settings[$key] : (string)$fallback;
}

function siteSettingEnabled($key)
{
	return siteSetting($key) === '1';
}

function serverConnectUri()
{
	$address = serverAddress();
	return $address === '' ? '' : 'steam://rungameid/730//+connect ' . $address;
}

function serverAddress()
{
	$value = siteSetting('server_address');
	return isValidServerAddress($value) ? trim($value) : '';
}

function serverPassword()
{
	$value = siteSetting('server_password');
	return isValidServerPassword($value) ? trim($value) : '';
}

function serverConsoleCommand()
{
	$address = serverAddress();
	if ($address === '') {
		return '';
	}
	$command = 'connect ' . $address;
	$password = serverPassword();
	return $password === '' ? $command : $command . ';password ' . $password;
}

function editableSiteSettingConstants()
{
	return [
		'site_name_en' => 'SITE_NAME_EN',
		'site_name_zh_cn' => 'SITE_NAME_ZH_CN',
		'default_language' => 'DEFAULT_LANGUAGE',
		'default_web_theme' => 'DEFAULT_WEB_THEME',
		'enable_skin_fusion' => 'ENABLE_SKIN_FUSION',
		'server_address' => 'SERVER_ADDRESS',
		'server_password' => 'SERVER_PASSWORD',
	];
}

function isValidEditableSiteSettings(array $settings)
{
	$requiredKeys = array_keys(editableSiteSettingConstants());
	foreach ($requiredKeys as $key) {
		if (!array_key_exists($key, $settings) || !is_string($settings[$key])) {
			return false;
		}
	}
	return isValidSiteName($settings['site_name_en'])
		&& isValidSiteName($settings['site_name_zh_cn'])
		&& (trim($settings['site_name_en']) !== '' || trim($settings['site_name_zh_cn']) !== '')
		&& in_array($settings['default_language'], ['zh-CN', 'en'], true)
		&& in_array($settings['default_web_theme'], ['dark', 'light'], true)
		&& in_array($settings['enable_skin_fusion'], ['0', '1'], true)
		&& isValidServerAddress($settings['server_address'])
		&& isValidServerPassword($settings['server_password']);
}

function replaceConfigConstant($config, $constant, $value)
{
	$exportedValue = $constant === 'ENABLE_SKIN_FUSION'
		? ($value === '1' ? 'true' : 'false')
		: var_export($value, true);
	$pattern = '/^([ \t]*)define\(\s*([\'\"])' . preg_quote($constant, '/') . '\2\s*,[^\r\n]*\);([ \t]*(?:\/\/[^\r\n]*)?)$/m';
	$replacement = "define('{$constant}', {$exportedValue});";
	$updated = preg_replace_callback($pattern, static function ($matches) use ($replacement) {
		return $matches[1] . $replacement . $matches[3];
	}, $config, 1, $replacementCount);
	if (!is_string($updated) || $replacementCount !== 1) {
		throw new RuntimeException("Unable to locate {$constant} in config.php");
	}
	return $updated;
}

function saveSiteSettingsToConfig(array $settings, $configPath = null)
{
	if (!isValidEditableSiteSettings($settings)) {
		throw new InvalidArgumentException('Invalid site settings');
	}
	$configPath = $configPath === null ? APP_ROOT . '/config.php' : (string)$configPath;
	if (!is_file($configPath) || !is_readable($configPath)) {
		throw new RuntimeException('config.php is not readable');
	}
	$configDirectory = dirname($configPath);
	if (!is_writable($configPath) || !is_writable($configDirectory)) {
		throw new RuntimeException('config.php or its directory is not writable');
	}

	$lockPath = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR
		. 'cs2-wp-config-' . hash('sha256', $configPath) . '.lock';
	$lockHandle = fopen($lockPath, 'c');
	if ($lockHandle === false || !flock($lockHandle, LOCK_EX)) {
		if (is_resource($lockHandle)) {
			fclose($lockHandle);
		}
		throw new RuntimeException('Unable to lock config.php for writing');
	}

	$tempPath = null;
	$backupPath = null;
	try {
		$config = file_get_contents($configPath);
		if (!is_string($config)) {
			throw new RuntimeException('Unable to read config.php');
		}
		foreach (editableSiteSettingConstants() as $key => $constant) {
			$config = replaceConfigConstant($config, $constant, $settings[$key]);
		}

		$tempPath = tempnam($configDirectory, '.config-');
		if ($tempPath === false || file_put_contents($tempPath, $config, LOCK_EX) !== strlen($config)) {
			throw new RuntimeException('Unable to write the temporary config file');
		}
		$permissions = fileperms($configPath);
		if ($permissions !== false) {
			@chmod($tempPath, $permissions & 0777);
		}

		if (DIRECTORY_SEPARATOR === '\\') {
			$backupPath = $configPath . '.backup-' . bin2hex(random_bytes(6));
			if (!rename($configPath, $backupPath)) {
				throw new RuntimeException('Unable to prepare config.php for replacement');
			}
			if (!rename($tempPath, $configPath)) {
				@rename($backupPath, $configPath);
				throw new RuntimeException('Unable to replace config.php');
			}
			$tempPath = null;
			@unlink($backupPath);
			if (!is_file($backupPath)) {
				$backupPath = null;
			}
		} elseif (!rename($tempPath, $configPath)) {
			throw new RuntimeException('Unable to replace config.php');
		} else {
			$tempPath = null;
		}
		if (function_exists('opcache_invalidate')) {
			@opcache_invalidate($configPath, true);
		}
	} finally {
		if ($tempPath !== null && is_file($tempPath)) {
			@unlink($tempPath);
		}
		if ($backupPath !== null && is_file($backupPath)) {
			if (!is_file($configPath)) {
				@rename($backupPath, $configPath);
			} else {
				@unlink($backupPath);
			}
		}
		flock($lockHandle, LOCK_UN);
		fclose($lockHandle);
	}
	return normalizedSiteSettings($settings);
}

function applySiteSettingsContext(array $settings)
{
	global $availableLanguages, $languageCookieName, $cookiePath, $isHttps;
	global $currentLanguage, $siteNames, $siteName, $teams, $defaultWebTheme;

	setRuntimeSiteSettings($settings);
	$requestedLanguage = $_GET['lang'] ?? $_COOKIE[$languageCookieName] ?? siteSetting('default_language', 'zh-CN');
	$currentLanguage = array_key_exists($requestedLanguage, $availableLanguages) ? $requestedLanguage : siteSetting('default_language', 'zh-CN');
	if (isset($_GET['lang']) && array_key_exists($_GET['lang'], $availableLanguages)) {
		setcookie($languageCookieName, $currentLanguage, [
			'expires' => time() + 60 * 60 * 24 * 365,
			'path' => $cookiePath,
			'secure' => $isHttps,
			'httponly' => false,
			'samesite' => 'Lax',
		]);
	}
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
}
