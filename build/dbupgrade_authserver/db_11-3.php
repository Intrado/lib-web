<?

function authserver_upgrade_11_3($rev, $db) {

	switch ($rev) {
	default:
	case 0:
		echo "|";
		apply_sql_db("dbupgrade_authserver/db_11-3_pre.sql", $db, 1);
	case 1:
		echo "|";
		apply_sql_db("dbupgrade_authserver/db_11-3_pre.sql", $db, 2);
	}

	return true;
}

?>
