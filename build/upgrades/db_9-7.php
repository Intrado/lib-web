<?

function upgrade_9_7 ($rev, $shardid, $customerid, $db) {
	global $authdb;

	switch($rev) {
		default:
		case 0:
			echo "|";
			apply_sql("upgrades/db_9-7_pre.sql", $customerid, $db, 1);
		case 1:
			echo "|";
			apply_sql("upgrades/db_9-7_pre.sql", $customerid, $db, 2);
		case 2:
			echo "|";
			apply_sql("upgrades/db_9-7_pre.sql", $customerid, $db, 3);
			
	}
	
	//This statement should appear in each upgrade script, when relevent.
	apply_sql("../db/update_SMAdmin_access.sql", $customerid, $db);
	
	return true;
}


?>