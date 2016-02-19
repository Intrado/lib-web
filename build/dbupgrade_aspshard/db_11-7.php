<?

function aspshard_upgrade_11_7($rev, $db) {
	switch ($rev) {
	    case "0":
		//no op
	    case "1":
		echo "|";
		apply_sql_db("dbupgrade_aspshard/db_11-7_pre.sql", $db, 2);	   
	}

	return true;
}

?>
