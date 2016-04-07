<?

function portalauth_upgrade_11_8($rev, $db) {

	switch ($rev) {
	case 0:
		//no op
	case 1:
		echo "|";
		apply_sql_db("dbupgrade_portalauth/db_11-8_pre.sql", $db, 2);
	}

	return true;
}

?>
