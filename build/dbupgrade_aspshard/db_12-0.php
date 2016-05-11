<?

function aspshard_upgrade_12_0($rev, $db) {
	switch ($rev) {
	    case "0":
			echo "|";
			apply_sql_db("dbupgrade_aspshard/db_12-0_pre.sql", $db, 1);
	}

	return true;
}

?>
