<?php

	if ($postAction === 'save_music') {
		$id = cleanSteamId($_POST['id'] ?? '');
		$team = selectedTeam();
		$preset = findPreset($db, $presetTable, $id);
		$music = musicFromJson();
		$musicId = (int)($_POST['music_id'] ?? 0);
		if (!$preset || !canEditPreset($preset) || $team !== 1 || !tableExists($db, 'wp_player_music') || !array_key_exists($musicId, $music)) {
			go("index.php?action=edit&id={$id}&team={$team}");
		}

		$db->beginTransaction();
		try {
		foreach (writeTeams($team) as $targetTeam) {
			if ($musicId === 0) {
				$db->query("DELETE FROM `wp_player_music` WHERE `steamid` = :steamid AND `weapon_team` = :team", [
					"steamid" => $preset['steamid'],
					"team" => $targetTeam,
				]);
				continue;
			}
			$db->query("INSERT INTO `wp_player_music` (`steamid`, `weapon_team`, `music_id`)
				VALUES (:steamid, :team, :music_id)
				ON DUPLICATE KEY UPDATE `music_id` = :music_id_update", [
				"steamid" => $preset['steamid'],
				"team" => $targetTeam,
				"music_id" => $musicId,
				"music_id_update" => $musicId,
			]);
		}
			$db->commit();
		} catch (Throwable $exception) {
			if ($db->inTransaction()) {
				$db->rollBack();
			}
			throw $exception;
		}

		queueFloatingNotice('music_selection_saved');
		go("index.php?action=edit&id={$id}&team={$team}");
	}
