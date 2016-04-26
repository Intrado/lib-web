<?
function apply_globaldestinationregistry($targetversion, $rev, $db) {
	// require the necessary version upgrade scripts
	require_once("db_11-7.php");
	require_once("db_11-8.php");
	
	switch ($targetversion) {
	case "11.7":
		if (!globaldestinationregistry_upgrade_11_7($rev, $db)) {
			exit("Error upgrading DB");
		}
		break;
	case "11.8":
		if (!globaldestinationregistry_upgrade_11_8($rev, $db)) {
			exit("Error upgrading DB");
		}
		break;
	}
}
