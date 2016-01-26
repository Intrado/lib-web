<?

function globaldestinationregistry_upgrade_0_1($rev, $db) {

	switch ($rev) {
		default:
		case 0:
			echo "|";
			apply_sql_db("dbupgrade_globaldestinationregistry/db_0-1_pre.sql", $db, 1);
	}

	return true;
}

?>
