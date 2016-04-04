<?

function authserver_upgrade_11_7($rev, $db) {
	
	switch ($rev) {
	    case 0:
			echo "|";
			apply_sql_db("dbupgrade_authserver/db_11-7_pre.sql", $db, 1);
	    case 1:
			echo "|";
			apply_sql_db("dbupgrade_authserver/db_11-7_pre.sql", $db, 2);
		case 2:
			echo "|";
			apply_sql_db("dbupgrade_authserver/db_11-7_pre.sql", $db, 3);
	}

	return true;
}

?>
