<?php

function selectedTeam()
{
	$team = (int)($_GET['team'] ?? $_POST['team'] ?? 1);
	return in_array($team, [1, 2, 3], true) ? $team : 1;
}

function readTeam($team)
{
	return $team === 1 ? 2 : $team;
}

function writeTeams($team)
{
	return $team === 1 ? [2, 3] : [$team];
}

function knifeDefindexes($knifes)
{
	return array_values(array_filter(array_map('intval', array_keys($knifes)), static fn($key) => $key > 0));
}

function agentsFromJson()
{
	return UtilsClass::agentsFromJson();
}

function musicFromJson()
{
	$music = [
		0 => [
			'id' => 0,
			'name' => t('default_music'),
			'image' => '',
		],
	];
	foreach (UtilsClass::musicFromJson() as $musicKit) {
		$id = (int)($musicKit['id'] ?? 0);
		$music[$id] = [
			'id' => $id,
			'name' => $musicKit['name'] ?? '',
			'image' => $musicKit['image'] ?? '',
		];
	}
	ksort($music);
	return $music;
}

function pinsFromJson()
{
	$pins = [
		0 => [
			'id' => 0,
			'name' => t('default_pin'),
			'image' => '',
		],
	];
	foreach (UtilsClass::pinsFromJson() as $pin) {
		$id = (int)($pin['id'] ?? 0);
		$pins[$id] = [
			'id' => $id,
			'name' => $pin['name'] ?? '',
			'image' => $pin['image'] ?? '',
		];
	}
	ksort($pins);
	return $pins;
}

function itemAliasNamesFromJson($languageFile, $nameKey = 'name')
{
	$aliases = [];
	foreach (UtilsClass::dataFromJson($languageFile, $languageFile) as $item) {
		$id = (int)($item['id'] ?? 0);
		if ($id > 0) {
			$aliases[$id] = (string)($item[$nameKey] ?? '');
		}
	}
	return $aliases;
}

function skinAliasNamesFromJson($prefix)
{
	$aliases = [];
	$languageFile = alternateLanguageDataName($prefix);
	foreach (UtilsClass::dataFromJson($languageFile, $languageFile) as $item) {
		$defindex = (int)($item['weapon_defindex'] ?? 0);
		$paint = (int)($item['paint'] ?? 0);
		if ($defindex > 0) {
			$aliases[$defindex][$paint] = (string)($item['paint_name'] ?? '');
		}
	}
	return $aliases;
}

function alternateLanguageDataName($prefix)
{
	return UtilsClass::currentLanguage() === 'en' ? "{$prefix}_zh-CN" : "{$prefix}_en";
}

function unknownItemData($id, $nameKey = 'name', $imageKey = 'image')
{
	return [
		'id' => (int)$id,
		$nameKey => sprintf(t('unknown_item'), (int)$id),
		$imageKey => '',
		'unknown' => true,
	];
}


function stickersFromJson()
{
	$stickers = [
		0 => [
			'id' => 0,
			'name' => t('no_sticker'),
			'image' => '',
		],
	];
	foreach (UtilsClass::stickersFromJson() as $sticker) {
		$id = (int)($sticker['id'] ?? 0);
		$stickers[$id] = [
			'id' => $id,
			'name' => $sticker['name'] ?? '',
			'image' => $sticker['image'] ?? '',
		];
	}
	ksort($stickers);
	return $stickers;
}

function keychainsFromJson()
{
	$keychains = [
		0 => [
			'id' => 0,
			'name' => t('no_keychain'),
			'image' => '',
		],
	];
	foreach (UtilsClass::keychainsFromJson() as $keychain) {
		$id = (int)($keychain['id'] ?? 0);
		$keychains[$id] = [
			'id' => $id,
			'name' => $keychain['name'] ?? '',
			'image' => $keychain['image'] ?? '',
		];
	}
	ksort($keychains);
	return $keychains;
}

function skinFusionEnabled()
{
	return siteSettingEnabled('enable_skin_fusion');
}

function paintKitFinishBadges()
{
	static $badges = null;
	if ($badges !== null) {
		return $badges;
	}
	$badges = [
		415 => ['label' => t('doppler_ruby_badge'), 'class' => 'ruby'],
		416 => ['label' => t('doppler_sapphire_badge'), 'class' => 'sapphire'],
		417 => ['label' => t('doppler_black_pearl_badge'), 'class' => 'black-pearl'],
		418 => ['label' => 'P1', 'class' => 'doppler-p1'],
		419 => ['label' => 'P2', 'class' => 'doppler-p2'],
		420 => ['label' => 'P3', 'class' => 'doppler-p3'],
		421 => ['label' => 'P4', 'class' => 'doppler-p4'],
		568 => ['label' => t('gamma_emerald_badge'), 'class' => 'emerald'],
		569 => ['label' => 'P1', 'class' => 'gamma-p1'],
		570 => ['label' => 'P2', 'class' => 'gamma-p2'],
		571 => ['label' => 'P3', 'class' => 'gamma-p3'],
		572 => ['label' => 'P4', 'class' => 'gamma-p4'],
		617 => ['label' => t('doppler_black_pearl_badge'), 'class' => 'black-pearl'],
		618 => ['label' => 'P2', 'class' => 'doppler-p2'],
		619 => ['label' => t('doppler_sapphire_badge'), 'class' => 'sapphire'],
		852 => ['label' => 'P1', 'class' => 'doppler-p1'],
		853 => ['label' => 'P2', 'class' => 'doppler-p2'],
		854 => ['label' => 'P3', 'class' => 'doppler-p3'],
		855 => ['label' => 'P4', 'class' => 'doppler-p4'],
		1119 => ['label' => t('gamma_emerald_badge'), 'class' => 'emerald'],
		1120 => ['label' => 'P1', 'class' => 'gamma-p1'],
		1121 => ['label' => 'P2', 'class' => 'gamma-p2'],
		1122 => ['label' => 'P3', 'class' => 'gamma-p3'],
		1123 => ['label' => 'P4', 'class' => 'gamma-p4'],
	];
	return $badges;
}

function paintKitFinishBadge($paint)
{
	$badges = paintKitFinishBadges();
	return $badges[(int)$paint] ?? null;
}

function paintKitFinishBadgeHtml($paint)
{
	$badge = paintKitFinishBadge($paint);
	if (!$badge) {
		return '';
	}
	return '<span class="paint-variant-badge paint-variant-' . h($badge['class']) . '">' . h($badge['label']) . '</span>';
}

function paintKitsFromJson()
{
	$paintKits = [];
	foreach (UtilsClass::paintKitsFromJson() as $paintKit) {
		$paint = (int)($paintKit['paint'] ?? 0);
		if ($paint <= 0) {
			continue;
		}
		$paintKits[$paint] = [
			'paint' => $paint,
			'name' => (string)($paintKit['name'] ?? ''),
			'source_name' => (string)($paintKit['source_name'] ?? ''),
			'source_weapon' => (string)($paintKit['source_weapon'] ?? ''),
			'source_defindex' => (int)($paintKit['source_defindex'] ?? 0),
			'image' => (string)($paintKit['image'] ?? ''),
		];
	}
	return $paintKits;
}

function fusionTargetName($skin, $fallback = '')
{
	$name = trim(explode('|', (string)($skin['paint_name'] ?? ''), 2)[0]);
	$name = preg_replace('/^(?:\x{4F7F}\x{7528}\x{5E93}\x{5B58}|Use inventory)\s*/u', '', $name);
	return $name !== '' ? $name : (string)$fallback;
}

function fusionSkinData($targetSkin, $paintKit, $fallbackName = '')
{
	$targetName = fusionTargetName($targetSkin, $fallbackName);
	$finishName = trim((string)($paintKit['name'] ?? ''));
	return [
		'weapon_name' => (string)($targetSkin['weapon_name'] ?? $fallbackName),
		'paint_name' => trim($targetName . ' | ' . $finishName, ' |'),
		'image_url' => '',
	];
}

function isFusionPaint($defindex, $paint, $skins, $paintKits)
{
	$defindex = (int)$defindex;
	$paint = (int)$paint;
	return $defindex > 0
		&& $paint > 0
		&& !isset($skins[$defindex][$paint])
		&& isset($paintKits[$paint]);
}

function isSelectedSkinPaint($selectedSkins, $defindex, $paint)
{
	$defindex = (int)$defindex;
	$paint = (int)$paint;
	return $defindex > 0
		&& $paint > 0
		&& isset($selectedSkins[$defindex])
		&& (int)($selectedSkins[$defindex]['weapon_paint_id'] ?? 0) === $paint;
}

function defaultStickerValue()
{
	return '0;0;0;0;0;0;0';
}

function defaultKeychainValue()
{
	return '0;0;0;0;0';
}

function stickerSlotCount($defindex)
{
	return 5;
}

function customizableWeaponDefindexes()
{
	return [
		1, 2, 3, 4, 7, 8, 9, 10, 11, 13, 14, 16, 17, 19, 23, 24, 25, 26, 27,
		28, 29, 30, 31, 32, 33, 34, 35, 36, 38, 39, 40, 60, 61, 63, 64,
	];
}

function unknownSkinData($targetSkin, $paint, $fallbackName = '')
{
	$targetName = fusionTargetName($targetSkin, $fallbackName);
	return [
		'weapon_name' => (string)($targetSkin['weapon_name'] ?? $fallbackName),
		'paint_name' => trim($targetName . ' | ' . sprintf(t('unknown_item'), (int)$paint), ' |'),
		'image_url' => '',
		'unknown' => true,
	];
}

function supportsWeaponCustomization($defindex)
{
	return in_array((int)$defindex, customizableWeaponDefindexes(), true);
}

function allowedAgentModels($team)
{
	$models = ['' => true];
	foreach (['agents_en', 'agents_zh-CN'] as $languageFile) {
		foreach (UtilsClass::dataFromJson($languageFile, 'agents_en') as $agent) {
			$model = trim((string)($agent['model'] ?? ''));
			if ((int)($agent['team'] ?? 0) === (int)$team) {
				$models[$model] = true;
			}
		}
	}
	return $models;
}

function stickerNumber($value, $min, $max, $default, $scaleMustBePositive = false)
{
	if ($value === null || $value === '' || !is_numeric($value)) {
		return $default;
	}
	$value = (float)$value;
	if ($scaleMustBePositive && $value <= 0) {
		return $default;
	}
	return max((float)$min, min((float)$max, $value));
}

function stickerValueParts($value)
{
	$parts = array_pad(explode(';', (string)$value), 7, '');
	$id = max(0, (int)($parts[0] ?? 0));
	$schema = max(0, (int)($parts[1] ?? 0));
	if ($id > 0 && $schema === 0) {
		$schema = $id;
	}
	return [
		'id' => $id,
		'schema' => $schema,
		'x' => stickerNumber($parts[2] ?? null, -1, 1, 0),
		'y' => stickerNumber($parts[3] ?? null, -1, 1, 0),
		'wear' => stickerNumber($parts[4] ?? null, 0, 1, 0),
		'scale' => stickerNumber($parts[5] ?? null, 0.2, 5, 1, true),
		'rotation' => stickerNumber($parts[6] ?? null, 0, 360, 0),
	];
}

function stickerIdFromValue($value)
{
	$parts = stickerValueParts($value);
	return $parts['id'];
}

function stickerFloatValue($value)
{
	return number_format((float)$value, 2, '.', '');
}

function skinWearDisplayValue($value)
{
	$formatted = number_format(round(max(0.0, min(1.0, (float)$value)), 8), 8, '.', '');
	$formatted = rtrim(rtrim($formatted, '0'), '.');
	return $formatted === '' ? '0' : $formatted;
}

function buildStickerValueFromParts($id, $schema, $params)
{
	$id = max(0, (int)$id);
	$schema = max(0, (int)$schema);
	if ($id === 0) {
		return defaultStickerValue();
	}
	if ($schema === 0) {
		$schema = $id;
	}
	$x = stickerFloatValue(stickerNumber($params['x'] ?? 0, -1, 1, 0));
	$y = stickerFloatValue(stickerNumber($params['y'] ?? 0, -1, 1, 0));
	$wear = stickerFloatValue(stickerNumber($params['wear'] ?? 0, 0, 1, 0));
	$scale = stickerFloatValue(stickerNumber($params['scale'] ?? 1, 0.2, 5, 1, true));
	$rotation = (string)(int)round(stickerNumber($params['rotation'] ?? 0, 0, 360, 0));
	return "{$id};{$schema};{$x};{$y};{$wear};{$scale};{$rotation}";
}

function buildStickerValue($stickerId)
{
	$stickerId = max(0, (int)$stickerId);
	if ($stickerId === 0) {
		return defaultStickerValue();
	}
	return buildStickerValueFromParts($stickerId, $stickerId, [
		'x' => 0,
		'y' => 0,
		'wear' => 0,
		'scale' => 1,
		'rotation' => 0,
	]);
}

function readStickerAdvancedParamsFromPost()
{
	return [
		'wear' => stickerNumber($_POST['sticker_wear'] ?? null, 0, 1, 0),
		'x' => stickerNumber($_POST['sticker_x'] ?? null, -1, 1, 0),
		'y' => stickerNumber($_POST['sticker_y'] ?? null, -1, 1, 0),
		'scale' => stickerNumber($_POST['sticker_scale'] ?? null, 0.2, 5, 1, true),
		'rotation' => stickerNumber($_POST['sticker_rotation'] ?? null, 0, 360, 0),
	];
}

function keychainValueParts($value)
{
	$parts = array_pad(explode(';', (string)$value), 5, '');
	$id = max(0, (int)($parts[0] ?? 0));
	return [
		'id' => $id,
		'x' => stickerNumber($parts[1] ?? null, -1, 1, 0),
		'y' => stickerNumber($parts[2] ?? null, -1, 1, 0),
		'z' => stickerNumber($parts[3] ?? null, -1, 1, 0),
		'template' => $id > 0 ? max(1, min(99999, (int)($parts[4] ?? 1))) : 0,
	];
}

function keychainIdFromValue($value)
{
	return keychainValueParts($value)['id'];
}

function buildKeychainValueFromParts($id, $params)
{
	$id = max(0, (int)$id);
	if ($id === 0) {
		return defaultKeychainValue();
	}
	$x = stickerFloatValue(stickerNumber($params['x'] ?? 0, -1, 1, 0));
	$y = stickerFloatValue(stickerNumber($params['y'] ?? 0, -1, 1, 0));
	$z = stickerFloatValue(stickerNumber($params['z'] ?? 0, -1, 1, 0));
	$template = (string)max(1, min(99999, (int)($params['template'] ?? 1)));
	return "{$id};{$x};{$y};{$z};{$template}";
}

function buildKeychainValue($keychainId)
{
	return buildKeychainValueFromParts($keychainId, [
		'x' => 0,
		'y' => 0,
		'z' => 0,
		'template' => 1,
	]);
}

function readKeychainValueFromPost($keychains, $existingValue = null)
{
	if (!array_key_exists('keychain_present', $_POST)) {
		return null;
	}
	$keychainId = (int)($_POST['keychain_id'] ?? 0);
	$existingParts = $existingValue !== null ? keychainValueParts($existingValue) : null;
	if (!array_key_exists($keychainId, $keychains)) {
		if ($keychainId > 0 && $existingParts && $existingParts['id'] === $keychainId) {
			return (string)$existingValue;
		}
		return $existingValue !== null ? (string)$existingValue : defaultKeychainValue();
	}
	$postedParts = keychainValueParts($_POST['keychain_value'] ?? '');
	if ($keychainId > 0 && $postedParts['id'] === $keychainId) {
		return buildKeychainValueFromParts($keychainId, $postedParts);
	}
	if ($existingParts !== null) {
		if ($keychainId > 0 && $existingParts['id'] === $keychainId) {
			return buildKeychainValueFromParts($keychainId, $existingParts);
		}
	}
	return buildKeychainValue($keychainId);
}

function wantsJsonResponse()
{
	$requestedWith = strtolower((string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? ''));
	$accept = strtolower((string)($_SERVER['HTTP_ACCEPT'] ?? ''));
	return ($_POST['ajax'] ?? '') === '1' || $requestedWith === 'fetch' || strpos($accept, 'application/json') !== false;
}

function stickerSlotResponse($ok, $payload, $fallbackUrl)
{
	if (wantsJsonResponse()) {
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(['ok' => $ok] + $payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		exit;
	}
	go($fallbackUrl);
}
function defaultStickerValues()
{
	return array_fill(0, 5, defaultStickerValue());
}

function stickerValuesFromRow($row)
{
	$values = defaultStickerValues();
	for ($i = 0; $i < 5; $i++) {
		$key = "weapon_sticker_{$i}";
		if (isset($row[$key]) && $row[$key] !== '') {
			$values[$i] = $row[$key];
		}
	}
	return $values;
}

function readStickerValuesFromPost($slotCount, $stickers, $existingValues = null)
{
	if (!array_key_exists('sticker_present', $_POST)) {
		return null;
	}
	$values = is_array($existingValues)
		? array_pad(array_slice(array_values($existingValues), 0, 5), 5, defaultStickerValue())
		: defaultStickerValues();
	for ($i = 0; $i < min(5, (int)$slotCount); $i++) {
		$stickerId = (int)($_POST["sticker_{$i}"] ?? 0);
		if (!array_key_exists($stickerId, $stickers)) {
			$existingParts = stickerValueParts($values[$i] ?? '');
			if ($stickerId > 0 && $existingParts['id'] === $stickerId) {
				continue;
			}
			continue;
		}
		$postedValue = (string)($_POST["sticker_value_{$i}"] ?? '');
		$postedParts = stickerValueParts($postedValue);
		if ($stickerId > 0 && $postedParts['id'] === $stickerId) {
			$values[$i] = buildStickerValueFromParts($postedParts['id'], $postedParts['schema'], $postedParts);
		} else {
			$values[$i] = buildStickerValue($stickerId);
		}
	}
	return $values;
}
function jsonDataFileIsValid($relativeFile)
{
	static $validityCache = [];
	$path = APP_ROOT . '/' . ltrim((string)$relativeFile, '/');
	if (!is_file($path)) {
		return false;
	}
	$cacheKey = $path . '|' . (string)filemtime($path) . '|' . (string)filesize($path);
	if (array_key_exists($cacheKey, $validityCache)) {
		return $validityCache[$cacheKey];
	}
	$raw = file_get_contents($path);
	if ($raw === false) {
		return $validityCache[$cacheKey] = false;
	}
	return $validityCache[$cacheKey] = is_array(json_decode($raw, true));
}

function localizedClientDataFile($prefix)
{
	$currentLanguage = in_array(UtilsClass::currentLanguage(), ['zh-CN', 'en'], true)
		? UtilsClass::currentLanguage()
		: 'en';
	$preferred = "data/{$prefix}_{$currentLanguage}.json";
	$fallback = "data/{$prefix}_" . ($currentLanguage === 'en' ? 'zh-CN' : 'en') . '.json';
	if (jsonDataFileIsValid($preferred)) {
		return $preferred;
	}
	if (jsonDataFileIsValid($fallback)) {
		return $fallback;
	}
	return $preferred;
}

function alternateClientDataFile($currentFile, $prefix)
{
	$alternate = str_ends_with((string)$currentFile, '_en.json')
		? "data/{$prefix}_zh-CN.json"
		: "data/{$prefix}_en.json";
	return jsonDataFileIsValid($alternate) ? $alternate : '';
}

function stickerDataFile()
{
	return localizedClientDataFile('stickers');
}

function dataFileUrl($relativeFile)
{
	$path = APP_ROOT . '/' . ltrim((string)$relativeFile, '/');
	$version = is_file($path) ? filemtime($path) : time();
	return $relativeFile . '?v=' . $version;
}

function stickerAliasDataFile()
{
	return alternateClientDataFile(stickerDataFile(), 'stickers');
}

function keychainDataFile()
{
	return localizedClientDataFile('keychains');
}

function keychainAliasDataFile()
{
	return alternateClientDataFile(keychainDataFile(), 'keychains');
}

function paintKitDataFile()
{
	return localizedClientDataFile('paint_kits');
}

function paintKitAliasDataFile()
{
	return alternateClientDataFile(paintKitDataFile(), 'paint_kits');
}

function glovesFromJson()
{
	$gloves = [];
	foreach (UtilsClass::glovesFromJson() as $glove) {
		$defindex = (int)($glove['weapon_defindex'] ?? 0);
		$paint = (int)($glove['paint'] ?? 0);
		$gloves[$defindex][$paint] = [
			'weapon_defindex' => $defindex,
			'paint' => $paint,
			'paint_name' => $glove['paint_name'] ?? '',
			'image_url' => $glove['image'] ?? '',
		];
	}
	ksort($gloves);
	return $gloves;
}

function gloveDefindexes($gloves)
{
	return array_values(array_filter(array_map('intval', array_keys($gloves)), static fn($key) => $key > 0));
}

function gloveTypeOptions($gloves)
{
	$options = [];
	foreach ($gloves as $defindex => $paints) {
		$first = reset($paints);
		if (!$first) {
			continue;
		}
		$name = $first['paint_name'];
		if ((int)$defindex === 0) {
			$name = UtilsClass::currentLanguage() === 'en' ? 'Use inventory gloves' : '使用库存手套';
		} elseif (str_contains($name, '|')) {
			$name = trim(explode('|', $name, 2)[0]);
		} else {
			$name = preg_replace('/^(使用库存|Use inventory)\s+/u', '', $name);
		}
		$options[(int)$defindex] = [
			'paint_name' => $name,
			'image_url' => $first['image_url'],
		];
	}
	return $options;
}

function glovePlaceholderImage($defindex)
{
	if ((int)$defindex === 0) {
		return 'img/skins/gloves.png';
	}

	$placeholders = [
		4725 => 'studded_brokenfang_gloves.png',
		5027 => 'studded_bloodhound_gloves.png',
		5030 => 'sporty_gloves.png',
		5031 => 'slick_gloves.png',
		5032 => 'leather_handwraps.png',
		5033 => 'motorcycle_gloves.png',
		5034 => 'specialist_gloves.png',
		5035 => 'studded_hydra_gloves.png',
	];
	$file = $placeholders[(int)$defindex] ?? '';
	return $file !== '' ? "img/skins/{$file}" : '';
}

function weaponPlaceholderImage($weaponName)
{
	$weaponName = basename((string)$weaponName);
	if ($weaponName === '') {
		return '';
	}
	if ($weaponName === 'weapon_knife') {
		return 'img/skins/knife.png';
	}
	$path = "img/weapon/{$weaponName}.png";
	return is_file(APP_ROOT . "/{$path}") ? $path : '';
}

