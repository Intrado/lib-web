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
	}

	return true;
}

?>
