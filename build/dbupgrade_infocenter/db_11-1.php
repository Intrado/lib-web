<?

function infocenter_upgrade_11_1($rev, $db) {

	switch ($rev) {
		default:
		case 0:
			echo "|";
			apply_sql_db("dbupgrade_infocenter/db_11-1_pre.sql", $db, 1);
	}

	return true;
}

?>
