<?php

	if ($postAction === 'save_skin') {
		$id = cleanSteamId($_POST['id'] ?? '');
		$team = selectedTeam();
		$displayTeam = readTeam($team);
		$preset = findPreset($db, $presetTable, $id);
		if (!$preset || !canEditPreset($preset)) {
			go('index.php?action=list');
		}

		$steamid = $preset['steamid'];
		$weapons = UtilsClass::getWeaponsFromArray();
		$skins = UtilsClass::skinsFromJson();
		$paintKits = paintKitsFromJson();
		$knifes = UtilsClass::getKnifeTypes();
		$gloves = glovesFromJson();
		$stickers = stickersFromJson();
		$keychains = keychainsFromJson();
		$selectedRows = $db->select("SELECT `weapon_defindex`, `weapon_paint_id`, `weapon_wear`, `weapon_seed`, `weapon_stattrak`, `weapon_stattrak_count`, `weapon_nametag`, `weapon_sticker_0`, `weapon_sticker_1`, `weapon_sticker_2`, `weapon_sticker_3`, `weapon_sticker_4`, `weapon_keychain`
			FROM `wp_player_skins`
			WHERE `steamid` = :steamid AND `weapon_team` = :team", [
			"steamid" => $steamid,
			"team" => $displayTeam,
		]);
		$selectedSkins = UtilsClass::getSelectedSkins($selectedRows);

		$ex = explode("-", (string)($_POST['skin_forma'] ?? $_POST['forma'] ?? ''));
		$isSkinSelection = array_key_exists('skin_forma', $_POST);
		$selectionNoticeKey = '';
		$db->beginTransaction();
		try {
		if (($ex[0] ?? '') === 'knife' && isset($ex[1]) && array_key_exists((int)$ex[1], $knifes)) {
			$selectionNoticeKey = 'knife_selection_saved';
			$knifeKey = (int)$ex[1];
			$knifeDefindexes = knifeDefindexes($knifes);
			foreach (writeTeams($team) as $targetTeam) {
				$db->query("INSERT INTO `wp_player_knife` (`steamid`, `knife`, `weapon_team`)
					VALUES(:steamid, :knife, :team)
					ON DUPLICATE KEY UPDATE `knife` = :knife_update", [
					"steamid" => $steamid,
					"knife" => $knifes[$knifeKey]['weapon_name'],
					"team" => $targetTeam,
					"knife_update" => $knifes[$knifeKey]['weapon_name'],
				]);

				if ($knifeKey === 0 && $knifeDefindexes) {
					$placeholders = [];
					$bindings = [
						"steamid" => $steamid,
						"team" => $targetTeam,
					];
					foreach ($knifeDefindexes as $index => $defindex) {
						$param = "knife_defindex_{$index}";
						$placeholders[] = ":{$param}";
						$bindings[$param] = $defindex;
					}
					$db->query("DELETE FROM `wp_player_skins`
						WHERE `steamid` = :steamid AND `weapon_team` = :team AND `weapon_defindex` IN (" . implode(',', $placeholders) . ")", $bindings);
				}
			}
		} elseif (($ex[0] ?? '') === 'glove' && isset($ex[1]) && array_key_exists((int)$ex[1], $gloves)) {
			$selectionNoticeKey = 'glove_selection_saved';
			$gloveDefindex = (int)$ex[1];
			$gloveDefindexes = gloveDefindexes($gloves);
			if (tableExists($db, 'wp_player_gloves')) {
				foreach (writeTeams($team) as $targetTeam) {
					if ($gloveDefindex === 0) {
						$currentGloveType = $db->select("SELECT `weapon_defindex` FROM `wp_player_gloves`
							WHERE `steamid` = :steamid AND `weapon_team` = :team LIMIT 1", [
							"steamid" => $steamid,
							"team" => $targetTeam,
						]);
						$currentGloveDefindex = (int)($currentGloveType[0]['weapon_defindex'] ?? 0);
						if ($currentGloveDefindex > 0) {
							$currentGloveSkin = $db->select("SELECT `weapon_defindex`, `weapon_paint_id`, `weapon_wear`, `weapon_seed`, `weapon_stattrak`, `weapon_stattrak_count`, `weapon_nametag`, `weapon_sticker_0`, `weapon_sticker_1`, `weapon_sticker_2`, `weapon_sticker_3`, `weapon_sticker_4`, `weapon_keychain`
								FROM `wp_player_skins`
								WHERE `steamid` = :steamid AND `weapon_defindex` = :weapon_defindex AND `weapon_team` = :team LIMIT 1", [
								"steamid" => $steamid,
								"weapon_defindex" => $currentGloveDefindex,
								"team" => $targetTeam,
							]);
							if ($currentGloveSkin) {
								saveSkinRowSettingCache($db, $skinSettingsTable, $steamid, $targetTeam, $currentGloveSkin[0]);
								markLastSelectedSkinCache($db, $skinSettingsTable, $steamid, $targetTeam, $currentGloveDefindex, (int)$currentGloveSkin[0]['weapon_paint_id']);
							}
						}
						$db->query("DELETE FROM `wp_player_gloves`
							WHERE `steamid` = :steamid AND `weapon_team` = :team", [
							"steamid" => $steamid,
							"team" => $targetTeam,
						]);

						if ($gloveDefindexes) {
							$placeholders = [];
							$bindings = [
								"steamid" => $steamid,
								"team" => $targetTeam,
							];
							foreach ($gloveDefindexes as $index => $defindex) {
								$param = "glove_defindex_{$index}";
								$placeholders[] = ":{$param}";
								$bindings[$param] = $defindex;
							}
							$db->query("DELETE FROM `wp_player_skins`
								WHERE `steamid` = :steamid AND `weapon_team` = :team AND `weapon_defindex` IN (" . implode(',', $placeholders) . ")", $bindings);
						}
						continue;
					}

					$db->query("INSERT INTO `wp_player_gloves` (`steamid`, `weapon_team`, `weapon_defindex`)
						VALUES (:steamid, :team, :weapon_defindex)
						ON DUPLICATE KEY UPDATE `weapon_defindex` = :weapon_defindex_update", [
						"steamid" => $steamid,
						"team" => $targetTeam,
						"weapon_defindex" => $gloveDefindex,
						"weapon_defindex_update" => $gloveDefindex,
					]);

					$existing = $db->select("SELECT `weapon_defindex`, `weapon_paint_id`, `weapon_wear`, `weapon_seed`, `weapon_stattrak`, `weapon_stattrak_count`, `weapon_nametag`, `weapon_sticker_0`, `weapon_sticker_1`, `weapon_sticker_2`, `weapon_sticker_3`, `weapon_sticker_4`, `weapon_keychain` FROM `wp_player_skins`
						WHERE `steamid` = :steamid AND `weapon_defindex` = :weapon_defindex AND `weapon_team` = :team LIMIT 1", [
						"steamid" => $steamid,
						"weapon_defindex" => $gloveDefindex,
						"team" => $targetTeam,
					]);
					if ($existing) {
						$current = $existing[0];
						saveSkinSettingCache($db, $skinSettingsTable, $steamid, $targetTeam, $gloveDefindex, (int)$current['weapon_paint_id'], (float)$current['weapon_wear'], (int)$current['weapon_seed'], 0, 0, null, defaultStickerValues(), defaultKeychainValue());
						if (array_key_exists((int)$current['weapon_paint_id'], $gloves[$gloveDefindex])) {
							markLastSelectedSkinCache($db, $skinSettingsTable, $steamid, $targetTeam, $gloveDefindex, (int)$current['weapon_paint_id']);
						}
					}

					$selectionTeam = $team === 1 ? readTeam($team) : $targetTeam;
					$lastSelected = loadLastSelectedSkinCache($db, $skinSettingsTable, $steamid, $selectionTeam, $gloveDefindex);
					$lastSelectedPaint = (int)($lastSelected['weapon_paint_id'] ?? 0);
					$paint = array_key_exists($lastSelectedPaint, $gloves[$gloveDefindex])
						? $lastSelectedPaint
						: (int)array_key_first($gloves[$gloveDefindex]);
					$cached = loadSkinSettingCache($db, $skinSettingsTable, $steamid, $targetTeam, $gloveDefindex, $paint);
					$wear = $cached ? (float)$cached['weapon_wear'] : 0.0;
					$seed = $cached ? (int)$cached['weapon_seed'] : 0;

					if ($existing) {
						$db->query("UPDATE `wp_player_skins`
							SET `weapon_paint_id` = :weapon_paint_id, `weapon_wear` = :weapon_wear, `weapon_seed` = :weapon_seed, `weapon_stattrak` = 0, `weapon_stattrak_count` = 0, `weapon_nametag` = NULL
							WHERE `steamid` = :steamid AND `weapon_defindex` = :weapon_defindex AND `weapon_team` = :team", [
							"steamid" => $steamid,
							"weapon_defindex" => $gloveDefindex,
							"weapon_paint_id" => $paint,
							"weapon_wear" => $wear,
							"weapon_seed" => $seed,
							"team" => $targetTeam,
						]);
					} else {
						$db->query("INSERT INTO `wp_player_skins`
							(`steamid`, `weapon_defindex`, `weapon_paint_id`, `weapon_wear`, `weapon_seed`, `weapon_stattrak`, `weapon_stattrak_count`, `weapon_nametag`, `weapon_sticker_0`, `weapon_sticker_1`, `weapon_sticker_2`, `weapon_sticker_3`, `weapon_sticker_4`, `weapon_team`)
							VALUES (:steamid, :weapon_defindex, :weapon_paint_id, :weapon_wear, :weapon_seed, 0, 0, NULL, '0;0;0;0;0;0;0', '0;0;0;0;0;0;0', '0;0;0;0;0;0;0', '0;0;0;0;0;0;0', '0;0;0;0;0;0;0', :team)", [
							"steamid" => $steamid,
							"weapon_defindex" => $gloveDefindex,
							"weapon_paint_id" => $paint,
							"weapon_wear" => $wear,
							"weapon_seed" => $seed,
							"team" => $targetTeam,
						]);
					}
					saveSkinSettingCache($db, $skinSettingsTable, $steamid, $targetTeam, $gloveDefindex, $paint, $wear, $seed, 0, 0, null, defaultStickerValues(), defaultKeychainValue());
					markLastSelectedSkinCache($db, $skinSettingsTable, $steamid, $targetTeam, $gloveDefindex, $paint);
				}
			}
		} elseif (($ex[0] ?? '') === 'gloveskin' && isset($ex[1], $ex[2])
			&& ((array_key_exists((int)$ex[1], $gloves) && array_key_exists((int)$ex[2], $gloves[(int)$ex[1]] ?? []))
				|| (!$isSkinSelection && isSelectedSkinPaint($selectedSkins, (int)$ex[1], (int)$ex[2])))) {
			$selectionNoticeKey = $isSkinSelection ? 'skin_selection_saved' : 'skin_settings_saved';
			$defindex = (int)$ex[1];
			$paint = (int)$ex[2];
			$hasExplicitWear = !$isSkinSelection && array_key_exists('wear', $_POST);
			$hasExplicitSeed = !$isSkinSelection && array_key_exists('seed', $_POST);
			$hasExplicitSettings = $hasExplicitWear || $hasExplicitSeed;
			$submittedWear = $hasExplicitWear ? round(max(0.0, min(1.0, (float)$_POST['wear'])), 8) : null;
			$submittedSeed = $hasExplicitSeed ? max(0, min(1000, (int)$_POST['seed'])) : null;

			foreach (writeTeams($team) as $targetTeam) {
				if (tableExists($db, 'wp_player_gloves')) {
					$db->query("INSERT INTO `wp_player_gloves` (`steamid`, `weapon_team`, `weapon_defindex`)
						VALUES (:steamid, :team, :weapon_defindex)
						ON DUPLICATE KEY UPDATE `weapon_defindex` = :weapon_defindex_update", [
						"steamid" => $steamid,
						"team" => $targetTeam,
						"weapon_defindex" => $defindex,
						"weapon_defindex_update" => $defindex,
					]);
				}

				$existing = $db->select("SELECT `weapon_defindex`, `weapon_paint_id`, `weapon_wear`, `weapon_seed`, `weapon_stattrak`, `weapon_stattrak_count`, `weapon_nametag`, `weapon_sticker_0`, `weapon_sticker_1`, `weapon_sticker_2`, `weapon_sticker_3`, `weapon_sticker_4`, `weapon_keychain` FROM `wp_player_skins`
					WHERE `steamid` = :steamid AND `weapon_defindex` = :weapon_defindex AND `weapon_team` = :team LIMIT 1", [
					"steamid" => $steamid,
					"weapon_defindex" => $defindex,
					"team" => $targetTeam,
				]);
				if ($existing) {
					$current = $existing[0];
					saveSkinSettingCache($db, $skinSettingsTable, $steamid, $targetTeam, $defindex, (int)$current['weapon_paint_id'], (float)$current['weapon_wear'], (int)$current['weapon_seed'], 0, 0, null, defaultStickerValues(), defaultKeychainValue());
				}

				if ($hasExplicitSettings) {
					$wear = $submittedWear ?? ($existing[0]['weapon_wear'] ?? 0.0);
					$seed = $submittedSeed ?? ($existing[0]['weapon_seed'] ?? 0);
				} else {
					$cached = loadSkinSettingCache($db, $skinSettingsTable, $steamid, $targetTeam, $defindex, $paint);
					$wear = $cached ? (float)$cached['weapon_wear'] : 0.0;
					$seed = $cached ? (int)$cached['weapon_seed'] : 0;
				}

				if ($existing) {
					$db->query("UPDATE `wp_player_skins`
						SET `weapon_paint_id` = :weapon_paint_id, `weapon_wear` = :weapon_wear, `weapon_seed` = :weapon_seed, `weapon_stattrak` = 0, `weapon_stattrak_count` = 0, `weapon_nametag` = NULL
						WHERE `steamid` = :steamid AND `weapon_defindex` = :weapon_defindex AND `weapon_team` = :team", [
						"steamid" => $steamid,
						"weapon_defindex" => $defindex,
						"weapon_paint_id" => $paint,
						"weapon_wear" => $wear,
						"weapon_seed" => $seed,
						"team" => $targetTeam,
					]);
				} else {
					$db->query("INSERT INTO `wp_player_skins`
						(`steamid`, `weapon_defindex`, `weapon_paint_id`, `weapon_wear`, `weapon_seed`, `weapon_stattrak`, `weapon_stattrak_count`, `weapon_nametag`, `weapon_sticker_0`, `weapon_sticker_1`, `weapon_sticker_2`, `weapon_sticker_3`, `weapon_sticker_4`, `weapon_team`)
						VALUES (:steamid, :weapon_defindex, :weapon_paint_id, :weapon_wear, :weapon_seed, 0, 0, NULL, '0;0;0;0;0;0;0', '0;0;0;0;0;0;0', '0;0;0;0;0;0;0', '0;0;0;0;0;0;0', '0;0;0;0;0;0;0', :team)", [
						"steamid" => $steamid,
						"weapon_defindex" => $defindex,
						"weapon_paint_id" => $paint,
						"weapon_wear" => $wear,
						"weapon_seed" => $seed,
						"team" => $targetTeam,
					]);
				}
				saveSkinSettingCache($db, $skinSettingsTable, $steamid, $targetTeam, $defindex, $paint, $wear, $seed, 0, 0, null, defaultStickerValues(), defaultKeychainValue());
				markLastSelectedSkinCache($db, $skinSettingsTable, $steamid, $targetTeam, $defindex, $paint);
			}

		} elseif (isset($ex[0], $ex[1])
			&& array_key_exists((int)$ex[0], $weapons)
			&& (array_key_exists((int)$ex[1], $skins[(int)$ex[0]] ?? [])
				|| (skinFusionEnabled() && (int)$ex[1] > 0 && isset($paintKits[(int)$ex[1]]))
				|| (!$isSkinSelection && isSelectedSkinPaint($selectedSkins, (int)$ex[0], (int)$ex[1])))) {
			$selectionNoticeKey = $isSkinSelection ? 'skin_selection_saved' : 'skin_settings_saved';
			$defindex = (int)$ex[0];
			$paint = (int)$ex[1];
			$isKnifeSkin = in_array($defindex, knifeDefindexes($knifes), true);
			$allowsCustomization = supportsWeaponCustomization($defindex);
			$hasExplicitWear = !$isSkinSelection && array_key_exists('wear', $_POST);
			$hasExplicitSeed = !$isSkinSelection && array_key_exists('seed', $_POST);
			$submittedStickerValues = !$isSkinSelection && $allowsCustomization && array_key_exists('sticker_present', $_POST) ? true : null;
			$submittedKeychainValue = !$isSkinSelection && $allowsCustomization && array_key_exists('keychain_present', $_POST) ? true : null;
			$hasExplicitSettings = !$isSkinSelection && ($hasExplicitWear || $hasExplicitSeed || array_key_exists('stattrak', $_POST) || array_key_exists('nametag_present', $_POST) || $submittedStickerValues !== null || $submittedKeychainValue !== null);
			$submittedWear = $hasExplicitWear ? round(max(0.0, min(1.0, (float)$_POST['wear'])), 8) : null;
			$submittedSeed = $hasExplicitSeed ? max(0, min(1000, (int)$_POST['seed'])) : null;
			$submittedStatTrak = !$isSkinSelection && array_key_exists('stattrak', $_POST) ? 1 : 0;
			$submittedStatTrakCount = $submittedStatTrak ? max(0, min(999999, (int)($_POST['weapon_stattrak_count'] ?? 0))) : 0;
			$submittedNameTag = $isSkinSelection ? null : readNameTagFromPost();
			if ($submittedNameTag === false) {
				$db->rollBack();
				go("index.php?action=edit&id={$id}&team={$team}&error=nametag");
			}
			$isInventorySkin = $paint === 0 && !$isKnifeSkin;

			foreach (writeTeams($team) as $targetTeam) {
				if ($isKnifeSkin) {
					$db->query("INSERT INTO `wp_player_knife` (`steamid`, `knife`, `weapon_team`)
						VALUES(:steamid, :knife, :team)
						ON DUPLICATE KEY UPDATE `knife` = :knife_update", [
						"steamid" => $steamid,
						"knife" => $knifes[$defindex]['weapon_name'],
						"team" => $targetTeam,
						"knife_update" => $knifes[$defindex]['weapon_name'],
					]);
				}

				$existing = $db->select("SELECT `weapon_defindex`, `weapon_paint_id`, `weapon_wear`, `weapon_seed`, `weapon_stattrak`, `weapon_stattrak_count`, `weapon_nametag`, `weapon_sticker_0`, `weapon_sticker_1`, `weapon_sticker_2`, `weapon_sticker_3`, `weapon_sticker_4`, `weapon_keychain` FROM `wp_player_skins`
					WHERE `steamid` = :steamid AND `weapon_defindex` = :weapon_defindex AND `weapon_team` = :team LIMIT 1", [
					"steamid" => $steamid,
					"weapon_defindex" => $defindex,
					"team" => $targetTeam,
				]);
				if ($existing) {
					$current = $existing[0];
					saveSkinRowSettingCache($db, $skinSettingsTable, $steamid, $targetTeam, $current);
				}
				$submittedStickerValuesForTeam = $submittedStickerValues === true
					? readStickerValuesFromPost(stickerSlotCount($defindex), $stickers, $existing ? stickerValuesFromRow($existing[0]) : null)
					: null;
				$submittedKeychainValueForTeam = $submittedKeychainValue === true
					? readKeychainValueFromPost($keychains, $existing[0]['weapon_keychain'] ?? null)
					: null;

				if ($isInventorySkin) {
					$wear = 0.0;
					$seed = 0;
					$stattrak = 0;
					$stattrakCount = 0;
					$nameTag = null;
					$stickerValues = defaultStickerValues();
					$keychainValue = defaultKeychainValue();
				} elseif ($hasExplicitSettings) {
					$wear = $submittedWear ?? ($existing[0]['weapon_wear'] ?? 0.0);
					$seed = $submittedSeed ?? ($existing[0]['weapon_seed'] ?? 0);
					$stattrak = $submittedStatTrak;
					$stattrakCount = $stattrak ? $submittedStatTrakCount : 0;
					$nameTag = array_key_exists('nametag_present', $_POST) ? $submittedNameTag : ($existing[0]['weapon_nametag'] ?? null);
					$stickerValues = $submittedStickerValuesForTeam ?? ($existing ? stickerValuesFromRow($existing[0]) : defaultStickerValues());
					$keychainValue = $submittedKeychainValueForTeam ?? ($existing[0]['weapon_keychain'] ?? defaultKeychainValue());
				} else {
					$cached = loadSkinSettingCache($db, $skinSettingsTable, $steamid, $targetTeam, $defindex, $paint);
					$wear = $cached ? (float)$cached['weapon_wear'] : 0.0;
					$seed = $cached ? (int)$cached['weapon_seed'] : 0;
					$stattrak = $cached ? (int)$cached['weapon_stattrak'] : 0;
					$stattrakCount = $stattrak && $cached ? (int)($cached['weapon_stattrak_count'] ?? 0) : 0;
					$nameTag = $cached ? $cached['weapon_nametag'] : null;
					$stickerValues = $cached ? stickerValuesFromRow($cached) : defaultStickerValues();
					$keychainValue = $cached['weapon_keychain'] ?? defaultKeychainValue();
				}

				if ($existing) {
					$db->query("UPDATE `wp_player_skins`
						SET `weapon_paint_id` = :weapon_paint_id, `weapon_wear` = :weapon_wear, `weapon_seed` = :weapon_seed, `weapon_stattrak` = :weapon_stattrak, `weapon_stattrak_count` = :weapon_stattrak_count, `weapon_nametag` = :weapon_nametag, `weapon_sticker_0` = :weapon_sticker_0, `weapon_sticker_1` = :weapon_sticker_1, `weapon_sticker_2` = :weapon_sticker_2, `weapon_sticker_3` = :weapon_sticker_3, `weapon_sticker_4` = :weapon_sticker_4, `weapon_keychain` = :weapon_keychain
						WHERE `steamid` = :steamid AND `weapon_defindex` = :weapon_defindex AND `weapon_team` = :team", [
						"steamid" => $steamid,
						"weapon_defindex" => $defindex,
						"weapon_paint_id" => $paint,
						"weapon_wear" => $wear,
						"weapon_seed" => $seed,
						"weapon_stattrak" => $stattrak,
						"weapon_stattrak_count" => $stattrakCount,
						"weapon_nametag" => $nameTag,
						"weapon_sticker_0" => $stickerValues[0],
						"weapon_sticker_1" => $stickerValues[1],
						"weapon_sticker_2" => $stickerValues[2],
						"weapon_sticker_3" => $stickerValues[3],
						"weapon_sticker_4" => $stickerValues[4],
						"weapon_keychain" => $keychainValue,
						"team" => $targetTeam,
					]);
				} else {
					$db->query("INSERT INTO `wp_player_skins`
						(`steamid`, `weapon_defindex`, `weapon_paint_id`, `weapon_wear`, `weapon_seed`, `weapon_stattrak`, `weapon_stattrak_count`, `weapon_nametag`, `weapon_sticker_0`, `weapon_sticker_1`, `weapon_sticker_2`, `weapon_sticker_3`, `weapon_sticker_4`, `weapon_keychain`, `weapon_team`)
						VALUES (:steamid, :weapon_defindex, :weapon_paint_id, :weapon_wear, :weapon_seed, :weapon_stattrak, :weapon_stattrak_count, :weapon_nametag, :weapon_sticker_0, :weapon_sticker_1, :weapon_sticker_2, :weapon_sticker_3, :weapon_sticker_4, :weapon_keychain, :team)", [
						"steamid" => $steamid,
						"weapon_defindex" => $defindex,
						"weapon_paint_id" => $paint,
						"weapon_wear" => $wear,
						"weapon_seed" => $seed,
						"weapon_stattrak" => $stattrak,
						"weapon_stattrak_count" => $stattrakCount,
						"weapon_nametag" => $nameTag,
						"weapon_sticker_0" => $stickerValues[0],
						"weapon_sticker_1" => $stickerValues[1],
						"weapon_sticker_2" => $stickerValues[2],
						"weapon_sticker_3" => $stickerValues[3],
						"weapon_sticker_4" => $stickerValues[4],
						"weapon_keychain" => $keychainValue,
						"team" => $targetTeam,
					]);
				}
				saveSkinSettingCache($db, $skinSettingsTable, $steamid, $targetTeam, $defindex, $paint, $wear, $seed, $stattrak, $stattrakCount, $nameTag, $stickerValues, $keychainValue);
			}
		}
			$db->commit();
		} catch (Throwable $exception) {
			if ($db->inTransaction()) {
				$db->rollBack();
			}
			throw $exception;
		}
		if ($selectionNoticeKey !== '') {
			queueFloatingNotice($selectionNoticeKey);
		}

		go("index.php?action=edit&id={$id}&team={$team}");
	}
