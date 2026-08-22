<?php

	if ($postAction === 'save_pin') {
		$id = cleanSteamId($_POST['id'] ?? '');
		$team = selectedTeam();
		$preset = findPreset($db, $presetTable, $id);
		$pins = pinsFromJson();
		$pinId = (int)($_POST['pin_id'] ?? 0);
		if (!$preset || !canEditPreset($preset) || !tableExists($db, 'wp_player_pins') || !array_key_exists($pinId, $pins)) {
			go("index.php?action=edit&id={$id}&team={$team}");
		}

		$db->beginTransaction();
		try {
		foreach (writeTeams($team) as $targetTeam) {
			if ($pinId === 0) {
				$db->query("DELETE FROM `wp_player_pins` WHERE `steamid` = :steamid AND `weapon_team` = :team", [
					"steamid" => $preset['steamid'],
					"team" => $targetTeam,
				]);
				continue;
			}
			$db->query("INSERT INTO `wp_player_pins` (`steamid`, `weapon_team`, `id`)
				VALUES (:steamid, :team, :pin_id)
				ON DUPLICATE KEY UPDATE `id` = :pin_id_update", [
				"steamid" => $preset['steamid'],
				"team" => $targetTeam,
				"pin_id" => $pinId,
				"pin_id_update" => $pinId,
			]);
		}
			$db->commit();
		} catch (Throwable $exception) {
			if ($db->inTransaction()) {
				$db->rollBack();
			}
			throw $exception;
		}

		queueFloatingNotice('pin_selection_saved');
		go("index.php?action=edit&id={$id}&team={$team}");
	}
