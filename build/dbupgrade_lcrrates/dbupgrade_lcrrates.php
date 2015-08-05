<?
function apply_lcrrates($targetversion, $rev, $db) {
	// require the necessary version upgrade scripts
	require_once("db_11-0.php");
	require_once("db_11-2.php");
	require_once("db_11-3.php");

	switch ($targetversion) {
	case "11.0":
		if (!lcrrates_upgrade_11_0($rev, $db)) {
			exit("Error upgrading DB");
		}
		break;
	case "11.2":
		if (!lcrrates_upgrade_11_2($rev, $db)) {
			exit("Error upgrading DB");
		}
		break;
	case "11.3":
		if (!lcrrates_upgrade_11_3($rev, $db)) {
			exit("Error upgrading DB");
		}
		break;
	}
}

?>
