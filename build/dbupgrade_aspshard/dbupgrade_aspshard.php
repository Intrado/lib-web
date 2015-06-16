<?
function apply_aspshard($targetversion, $rev, $db) {
	// require the necessary version upgrade scripts
	require_once("db_11-0.php");
	require_once("db_11-1.php");
	require_once("db_11-2.php");

	switch ($targetversion) {
	case "11.0":
		if (!aspshard_upgrade_11_0($rev, $db)) {
			exit("Error upgrading DB");
		}
		break;
	case "11.1":
		if (!aspshard_upgrade_11_1($rev, $db)) {
			exit("Error upgrading DB");
		}
		break;
	case "11.2":
		if (!aspshard_upgrade_11_2($rev, $db)) {
			exit("Error upgrading DB");
		}
		break;
	}
}

?>
