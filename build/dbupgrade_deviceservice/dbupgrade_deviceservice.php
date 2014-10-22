<?
function apply_deviceservice($targetversion, $rev, $db) {
	// require the necessary version upgrade scripts
	require_once("db_11-1.php");

	switch ($targetversion) {
		case "11.1":
			if (!deviceservice_upgrade_11_1($rev, $db)) {
				exit("Error upgrading DB");
			}
			break;
	}
}

?>
