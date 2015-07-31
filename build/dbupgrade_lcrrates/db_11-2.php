<?

function lcrrates_upgrade_11_2($rev, $db) {

	switch ($rev) {
	default:
	case 0:
		echo "|";
		apply_sql_db("dbupgrade_lcrrates/db_11-2_pre.sql", $db, 1);
	case 1:
		echo "|";
		apply_sql_db("dbupgrade_lcrrates/db_11-2_pre.sql", $db, 2);
	case 2:
		echo "|";
		apply_sql_db("dbupgrade_lcrrates/db_11-2_pre.sql", $db, 3);
	case 3:
		echo "|";
		apply_sql_db("dbupgrade_lcrrates/db_11-2_pre.sql", $db, 4);
	}

	return true;
}

?>
