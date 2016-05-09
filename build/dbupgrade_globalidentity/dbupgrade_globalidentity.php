<?
function apply_globalidentity($targetversion, $rev, $db) {
	// require the necessary version upgrade scripts
	require_once("db_11-8.php");
	
	switch ($targetversion) {
	case "11.8":
		if (!globalidentity_upgrade_11_8($rev, $db)) {
			exit("Error upgrading DB");
		}
		break;
	}
}
