<?

function globaldestinationregistry_upgrade_12_0($rev, $db) {

	switch ($rev) {
		case 0:
			//no op
		case 1:
			echo "|";
			apply_sql_db("dbupgrade_globaldestinationregistry/db_12-0_pre.sql", $db, 2);
		case 2:
			echo "|";
			apply_sql_db("dbupgrade_globaldestinationregistry/db_12-0_pre.sql", $db, 3);
			
	}

	return true;
}

?>
