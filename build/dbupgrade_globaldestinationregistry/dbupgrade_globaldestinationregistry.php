<?
function apply_globaldestinationregistry($targetversion, $rev, $db) {
	// require the necessary version upgrade scripts
	require_once("db_11-7.php");
	
	switch ($targetversion) {
		case "11.7":
			if (!globaldestinationregistry_upgrade_11_7($rev, $db)) {
				exit("Error upgrading DB");
			}
			break;
	}
}

?>
