<?
function apply_globaldestinationregistry($targetversion, $rev, $db) {
	// require the necessary version upgrade scripts
	require_once("db_0-1.php");
	require_once("db_1-0.php");
	
	switch ($targetversion) {
		case "0.1":
			if (!globaldestinationregistry_upgrade_1_0($rev, $db)) {
				exit("Error upgrading DB");
			}
			break;
		case "1.0":
			if (!globaldestinationregistry_upgrade_1_0($rev, $db)) {
				exit("Error upgrading DB");
			}
			break;
	}
}

?>
