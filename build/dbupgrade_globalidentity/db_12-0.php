<?

function globalidentity_upgrade_12_0($rev, $db) {

	switch ($rev) {
	case 0:
		//no op
	case 1:
		echo "|";
		apply_sql_db("dbupgrade_globalidentity/db_12-0_pre.sql", $db, 2);
	}
	return true;
}

?>
