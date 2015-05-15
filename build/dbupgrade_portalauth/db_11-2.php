<?

function portalauth_upgrade_11_2($rev, $db) {

	switch ($rev) {
		default:
		case 0:
			echo "|";
			apply_sql_db("dbupgrade_portalauth/db_11-2_pre.sql", $db, 1);
		case 1:
			echo "|";
			apply_sql_db("dbupgrade_portalauth/db_11-2_pre.sql", $db, 2);
				}

	return true;
}

?>
