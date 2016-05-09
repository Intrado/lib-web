<?

function globaldestinationregistry_upgrade_11_8($rev, $db) {

	switch ($rev) {
	case 0:
		echo "|";
		apply_sql_db("dbupgrade_globaldestinationregistry/db_11-8_pre.sql", $db, 1);
	case 1:
		echo "|";
		apply_sql_db("dbupgrade_globaldestinationregistry/db_11-8_pre.sql", $db, 2);
	case 2:
		echo "|";
		apply_sql_db("dbupgrade_globaldestinationregistry/db_11-8_pre.sql", $db, 3);
	case 3:
		echo "|";
		apply_sql_db("dbupgrade_globaldestinationregistry/db_11-8_pre.sql", $db, 4);
	}

	return true;
}

?>
