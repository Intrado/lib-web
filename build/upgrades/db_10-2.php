<?

function upgrade_10_2 ($rev, $shardid, $customerid, $db) {
	global $authdb;

	switch($rev) {
		default:
		case 0:
			echo "|";
			apply_sql("upgrades/db_10-2_pre.sql", $customerid, $db, 1);
			
	}
	
	//This statement should appear in each upgrade script, when relevent.
	apply_sql("../db/update_SMAdmin_access.sql", $customerid, $db);
	
	return true;
}


?>