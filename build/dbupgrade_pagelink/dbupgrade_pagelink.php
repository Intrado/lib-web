<?
function apply_pagelink($targetversion, $rev, $db) {
	// require the necessary version upgrade scripts
	require_once("db_11-0.php");

	switch ($targetversion) {
		case "11.0":
			if (!pagelink_upgrade_11_0($rev, $db)) {
				exit("Error upgrading DB");
			}
			break;
	}
}

?>
