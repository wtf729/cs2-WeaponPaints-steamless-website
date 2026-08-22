<?php

	if ($postAction === 'save_agent') {
		$id = cleanSteamId($_POST['id'] ?? '');
		$team = selectedTeam();
		$preset = findPreset($db, $presetTable, $id);
		if (!$preset || !canEditPreset($preset) || !in_array($team, [2, 3], true) || !tableExists($db, 'wp_player_agents')) {
			go("index.php?action=edit&id={$id}&team={$team}");
		}

		$agentColumn = $team === 2 ? 'agent_t' : 'agent_ct';
		$agentModel = trim((string)($_POST['agent_model'] ?? ''));
		if ($agentModel === 'null') {
			$agentModel = '';
		}
		if (!isset(allowedAgentModels($team)[$agentModel])) {
			go("index.php?action=edit&id={$id}&team={$team}");
		}
		$agentValue = $agentModel === '' ? null : $agentModel;
		$db->query("INSERT INTO `wp_player_agents` (`steamid`, `{$agentColumn}`)
			VALUES (:steamid, :agent_model)
			ON DUPLICATE KEY UPDATE `{$agentColumn}` = :agent_model_update", [
			"steamid" => $preset['steamid'],
			"agent_model" => $agentValue,
			"agent_model_update" => $agentValue,
		]);

		queueFloatingNotice('agent_selection_saved');
		go("index.php?action=edit&id={$id}&team={$team}");
	}
