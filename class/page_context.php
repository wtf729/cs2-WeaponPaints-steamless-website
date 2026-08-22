<?php

$team = selectedTeam();
$currentPreset = null;
$weapons = [];
$skins = [];
$selectedSkins = [];
$selectedKnife = null;
$knifes = [];
$agents = [];
$selectedAgent = null;
$gloves = [];
$selectedGlove = null;
$stickers = [];
$keychains = [];
$music = [];
$selectedMusic = null;
$pins = [];
$selectedPin = null;

if ($action === 'edit') {
	$id = cleanSteamId($_GET['id'] ?? '');
	$displayTeam = readTeam($team);
	$currentPreset = findPreset($db, $presetTable, $id);
	if (!$currentPreset) {
		go('index.php?action=list');
	}
	if (!canEditPreset($currentPreset)) {
		go('index.php?action=list&loadout_password_required=' . rawurlencode($currentPreset['steamid']) . '&loadout_password_team=' . $team);
	}

	$steamid = $currentPreset['steamid'];
	$weapons = UtilsClass::getWeaponsFromArray();
	$skins = UtilsClass::skinsFromJson();
	$paintKits = paintKitsFromJson();
	$knifes = UtilsClass::getKnifeTypes();
	$selectedRows = $db->select("SELECT `weapon_defindex`, `weapon_paint_id`, `weapon_wear`, `weapon_seed`, `weapon_stattrak`, `weapon_stattrak_count`, `weapon_nametag`, `weapon_sticker_0`, `weapon_sticker_1`, `weapon_sticker_2`, `weapon_sticker_3`, `weapon_sticker_4`, `weapon_keychain`
		FROM `wp_player_skins` WHERE `steamid` = :steamid AND `weapon_team` = :team", [
		"steamid" => $steamid,
		"team" => $displayTeam,
	]);
	$selectedSkins = UtilsClass::getSelectedSkins($selectedRows);
	$selectedKnifeRows = $db->select("SELECT * FROM `wp_player_knife` WHERE `steamid` = :steamid AND `weapon_team` = :team LIMIT 1", [
		"steamid" => $steamid,
		"team" => $displayTeam,
	]);
	$selectedKnife = $selectedKnifeRows[0] ?? null;
	$gloves = glovesFromJson();
	if (tableExists($db, 'wp_player_gloves')) {
		$selectedGloveRows = $db->select("SELECT `weapon_defindex` FROM `wp_player_gloves` WHERE `steamid` = :steamid AND `weapon_team` = :team LIMIT 1", [
			"steamid" => $steamid,
			"team" => $displayTeam,
		]);
		$selectedGlove = $selectedGloveRows[0] ?? null;
	}
	$stickers = stickersFromJson();
	$keychains = keychainsFromJson();
	$music = musicFromJson();
	if (tableExists($db, 'wp_player_music')) {
		$selectedMusicRows = $db->select("SELECT `music_id` FROM `wp_player_music` WHERE `steamid` = :steamid AND `weapon_team` = :team LIMIT 1", [
			"steamid" => $steamid,
			"team" => $displayTeam,
		]);
		$selectedMusic = isset($selectedMusicRows[0]['music_id']) ? (int)$selectedMusicRows[0]['music_id'] : null;
	}
	$pins = pinsFromJson();
	if (tableExists($db, 'wp_player_pins')) {
		$selectedPinRows = $db->select("SELECT `id` FROM `wp_player_pins` WHERE `steamid` = :steamid AND `weapon_team` = :team LIMIT 1", [
			"steamid" => $steamid,
			"team" => $displayTeam,
		]);
		$selectedPin = isset($selectedPinRows[0]['id']) ? (int)$selectedPinRows[0]['id'] : null;
	}
	$agents = agentsFromJson();
	if (in_array($team, [2, 3], true) && tableExists($db, 'wp_player_agents')) {
		$agentColumn = $team === 2 ? 'agent_t' : 'agent_ct';
		$selectedAgentRows = $db->select("SELECT `{$agentColumn}` AS `agent_model` FROM `wp_player_agents` WHERE `steamid` = :steamid LIMIT 1", [
			"steamid" => $steamid,
		]);
		$selectedAgent = trim((string)($selectedAgentRows[0]['agent_model'] ?? ''));
		if ($selectedAgent === 'null') {
			$selectedAgent = '';
		}
	}
}

$presets = $accessGranted ? $db->select("SELECT * FROM `{$presetTable}` ORDER BY `created_time` ASC, `id` ASC") : [];
$returnTo = 'index.php' . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '');
$returnTo = safeReturnUrl($returnTo);
