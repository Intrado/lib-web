<?

function globaldestinationregistry_upgrade_1_0($rev, $db) {

	switch ($rev) {
		default:
		case 0:
			echo "|";
			apply_sql_db("dbupgrade_globaldestinationregistry/db_1-0_pre.sql", $db, 1);
	}

	return true;
}

?>
