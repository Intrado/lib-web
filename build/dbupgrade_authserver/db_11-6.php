<?

function authserver_upgrade_11_6($rev, $db) {

	switch ($rev) {
	default:
	case 0:
		echo "|";
		apply_sql_db("dbupgrade_authserver/db_11-6_pre.sql", $db, 1);
	case 1:
		echo "|";
		apply_sql_db("dbupgrade_authserver/db_11-6_pre.sql", $db, 2);
	case 2:
		echo "|";
		apply_sql_db("dbupgrade_authserver/db_11-6_pre.sql", $db, 3);
	case 3:
    	echo "|";
    	apply_sql_db("dbupgrade_authserver/db_11-6_pre.sql", $db, 4);
    }

	return true;
}

?>
