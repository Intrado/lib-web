<?
function apply_globalidentity($targetversion, $rev, $db) {
	// require the necessary version upgrade scripts
	require_once("db_11-8.php");
	require_once("db_12-0.php");
	
	switch ($targetversion) {
	case "11.8":
		if (!globalidentity_upgrade_11_8($rev, $db)) {
			exit("Error upgrading DB");
		}
		break;
	case "12.0":
		if (!globalidentity_upgrade_12_0($rev, $db)) {
			exit("Error upgrading DB");
		}
		break;
	}
}
