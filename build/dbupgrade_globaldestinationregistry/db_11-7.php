<?

function globaldestinationregistry_upgrade_11_7($rev, $db) {

	switch ($rev) {
		case 0:
			echo "|";
			apply_sql_db("dbupgrade_globaldestinationregistry/db_11-7_pre.sql", $db, 1);
		case 1:
			echo "|";
			apply_sql_db("dbupgrade_globaldestinationregistry/db_11-7_pre.sql", $db, 2);
	}

	return true;
}

?>
