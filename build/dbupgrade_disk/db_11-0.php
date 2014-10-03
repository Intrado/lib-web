<?

function disk_upgrade_11_0($rev, $db) {

	switch ($rev) {
		default:
		case 0:
			echo "|";
			apply_sql_db("dbupgrade_disk/db_11-0_pre.sql", $db, 1);
	}

	return true;
}

?>
