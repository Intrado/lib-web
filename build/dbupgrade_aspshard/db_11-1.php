<?

function aspshard_upgrade_11_1($rev, $db) {

	switch ($rev) {
		default:
		case 0:
			echo "|";
			apply_sql_db("dbupgrade_aspshard/db_11-1_pre.sql", $db, 1);
		case 1:
			echo "|";
			apply_sql_db("dbupgrade_aspshard/db_11-1_pre.sql", $db, 2);
		case 2:
			echo "|";
			apply_sql_db("dbupgrade_aspshard/db_11-1_pre.sql", $db, 3);
	}

	return true;
}

?>
