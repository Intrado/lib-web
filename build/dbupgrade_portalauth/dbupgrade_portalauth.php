<?
function apply_portalauth($targetversion, $rev, $db) {
	// require the necessary version upgrade scripts
	require_once("db_11-0.php");
	require_once("db_11-1.php");
	
	switch ($targetversion) {
		case "11.0":
			if (!portalauth_upgrade_11_0($rev, $db)) {
				exit("Error upgrading DB");
			}
			break;
		case "11.1":
			if (!portalauth_upgrade_11_1($rev, $db)) {
				exit("Error upgrading DB");
			}
			break;
	}
}

?>
