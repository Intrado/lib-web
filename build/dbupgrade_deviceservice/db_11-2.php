<?

function deviceservice_upgrade_11_2($rev, $db) {

	switch ($rev) {
	default:
	case 0:
		echo "|";
		apply_sql_db("dbupgrade_deviceservice/db_11-2_pre.sql", $db, 1);
	case 1:
		echo "|";
		apply_sql_db("dbupgrade_deviceservice/db_11-2_pre.sql", $db, 2);

		// Shameful hack: ORDER BY id DESC because there are early test appinstances that use outdated credentials
		// for the same appinstance name. By processing the newer ones first, we associate an appinstance only with
		// the newer credentials.
		$data = QuickQueryMultiRow("SELECT * FROM appinstance ORDER BY id DESC", true);
		$instanceid = 0;
		$credentialid = 0;
		$appids = array('0' => '2', '1' => '1');

		QuickUpdate("INSERT INTO app (id, productName) VALUES (1, 'InfoCenter'), (2, 'InfoCenter Debug')");

		// populate appcredential with distinct credentials
		// skip invalid credentials
		foreach ($data as $row) {

			if (strlen($row['applePassPhrase']) > 3 && !isset($apple[$row['appleCert']])) {
				$apple[$row['appleCert']] = ++$credentialid;
				QuickUpdate("INSERT INTO appcredential SET id=?, protocol=?, isProduction=?, appleCert=?, applePassPhrase=?", false,
					array($credentialid, 'APNS', $row['isProduction'], $row['appleCert'], $row['applePassPhrase']));
			}

			if (strlen($row['googleApiKey']) > 3 && !isset($google[$row['googleApiKey']])) {
				$google[$row['googleApiKey']] =  ++$credentialid;
				QuickUpdate("INSERT INTO appcredential SET id=?, protocol=?, isProduction=?, googleApiKey=?", false,
					array($credentialid, 'GCM', $row['isProduction'], $row['googleApiKey']));
			}

		}

		// prepare temporary table to map old appinstanceid values to new values
		QuickUpdate("CREATE TEMPORARY TABLE IF NOT EXISTS tmpAppInstanceIdMap (
			appInstanceId INT,
			osType ENUM('ios', 'android'),
			newAppInstanceId INT,
			appVersion varchar(20) NOT NULL,
			PRIMARY KEY(appInstanceId, osType)
		)");
		QuickUpdate("TRUNCATE TABLE tmpAppInstanceIdMap");

		// populate appinstance with unique instances per app and per osType
		foreach ($data as $row) {
			$appid = $appids[$row['isProduction']];

			if (isset($apple[$row['appleCert']])) {
				$cred = $apple[$row['appleCert']];
				if (!isset($instance[$appid][$row['name']]['ios'])) {
					$instance[$appid][$row['name']]['ios'] = ++$instanceid;
					QuickUpdate("INSERT INTO appinstance_new SET id=?, name=?, appId=?, appCredentialid=?, osType=?", false,
						array($instanceid, $row['name'], $appid, $cred, 'ios'));
				}
				QuickUpdate("INSERT INTO appversion SET appInstanceId=?, appVersion=?", false,
					array($instance[$appid][$row['name']]['ios'], $row['version']));
				QuickUpdate("INSERT INTO tmpAppInstanceIdMap SET appInstanceId=?, osType=?, newAppInstanceId=?, appVersion=?", false,
					array($row['id'], 'ios', $instance[$appid][$row['name']]['ios'], $row['version']));
			}

			if (isset($google[$row['googleApiKey']])) {
				$cred = $google[$row['googleApiKey']];
				if (!isset($instance[$appid][$row['name']]['android'])) {
					$instance[$appid][$row['name']]['android'] = ++$instanceid;
					QuickUpdate("INSERT INTO appinstance_new SET id=?, name=?, appId=?, appCredentialid=?, osType=?", false,
						array($instanceid, $row['name'], $appid, $cred, 'android'));
				}
				QuickUpdate("INSERT INTO appversion SET appInstanceId=?, appVersion=?", false,
					array($instance[$appid][$row['name']]['android'], $row['version']));
				QuickUpdate("INSERT INTO tmpAppInstanceIdMap SET appInstanceId=?, osType=?, newAppInstanceId=?, appVersion=?", false,
					array($row['id'], 'android', $instance[$appid][$row['name']]['android'], $row['version']));
			}

		}

		// update device to associate to new appinstanceid values
		QuickUpdate("UPDATE device AS d JOIN tmpAppInstanceIdMap AS tmp USING (appInstanceId)
			SET d.appInstanceId = tmp.newAppInstanceId, d.appVersion = tmp.appVersion");
		// temp table should get dropped automatically as $db closes
	case 2:
		echo "|";
		apply_sql_db("dbupgrade_deviceservice/db_11-2_pre.sql", $db, 3);
	}

	return true;
}

?>
