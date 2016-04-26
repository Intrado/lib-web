<?

function aspshard_upgrade_11_8($rev, $db) {
	switch ($rev) {
	    case "0":
		//no op
	    case "1":
			echo "|";
			apply_sql_db("dbupgrade_aspshard/db_11-8_pre.sql", $db, 2);
		case "2":
			echo "|";
			apply_sql_db("dbupgrade_aspshard/db_11-8_pre.sql", $db, 3);
		case "3":
			echo "|";
			apply_sql_db("dbupgrade_aspshard/db_11-8_pre.sql", $db, 4);
		case "4":
			echo "|";
			apply_sql_db("dbupgrade_aspshard/db_11-8_pre.sql", $db, 5);
		case "5":
			echo "|";
			apply_sql_db("dbupgrade_aspshard/db_11-8_pre.sql", $db, 6);
	}

	return true;
}

?>
