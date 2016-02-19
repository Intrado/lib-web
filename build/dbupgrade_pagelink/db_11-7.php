<?

function pagelink_upgrade_11_7($rev, $db) {

	switch ($rev) {
		case 0:
		    //no oop
		case 1:
			echo "|";
			apply_sql_db("dbupgrade_pagelink/db_11-7_pre.sql", $db, 2);
	}

	return true;
}

?>
