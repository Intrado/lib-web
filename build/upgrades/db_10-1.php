<?

function upgrade_10_1 ($rev, $shardid, $customerid, $db) {
	global $authdb;

	switch($rev) {
		default:
		case 0:
			echo "|";
			apply_sql("upgrades/db_10-1_pre.sql", $customerid, $db, 1);
			
		case 1:
			echo "|";
			apply_sql("upgrades/db_10-1_pre.sql", $customerid, $db, 2);

		case 2:
			echo "|";
			apply_sql("upgrades/db_10-1_pre.sql", $customerid, $db, 3);

		case 3:
			echo "|";
			apply_sql("upgrades/db_10-1_pre.sql", $customerid, $db, 4);

		case 4:
			echo "|";
			apply_sql("upgrades/db_10-1_pre.sql", $customerid, $db, 5);

		case 5:
			echo "|";
			apply_sql("upgrades/db_10-1_pre.sql", $customerid, $db, 6);

	}
	
	//This statement should appear in each upgrade script, when relevent.
	apply_sql("../db/update_SMAdmin_access.sql", $customerid, $db);
	
	return true;
}


?>
