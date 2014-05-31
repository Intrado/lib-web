<?

function upgrade_10_3 ($rev, $shardid, $customerid, $db) {
	global $authdb;

	switch($rev) {
		default:
		case 0:
			echo "|";
			apply_sql("upgrades/db_10-3_pre.sql", $customerid, $db, 1);
			
		case 1:
			echo "|";
			apply_sql("upgrades/db_10-3_pre.sql", $customerid, $db, 2);
			
		case 2:
			echo "|";
			apply_sql("upgrades/db_10-3_pre.sql", $customerid, $db, 3);
			
		case 3:
			echo "|";
			apply_sql("upgrades/db_10-3_pre.sql", $customerid, $db, 4);
			
		case 4:
			echo "|";
			apply_sql("upgrades/db_10-3_pre.sql", $customerid, $db, 5);
	}
	
	//This statement should appear in each upgrade script, when relevent.
	apply_sql("../db/update_SMAdmin_access.sql", $customerid, $db);
	
	return true;
}


?>
