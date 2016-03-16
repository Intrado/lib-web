<?

function infocenter_upgrade_11_8($rev, $db) {

	switch ($rev) {
		case 0:
		    echo "|";
			apply_sql_db("dbupgrade_infocenter/db_11-8_pre.sql", $db, 1);
	}

	return true;
}

?>
