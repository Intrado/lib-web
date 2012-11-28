<?

function upgrade_9_4 ($rev, $shardid, $customerid, $db) {
	global $authdb;

	switch($rev) {
		default:
		case 0:
			echo "|";
			apply_sql("upgrades/db_9-4_pre.sql", $customerid, $db, 1);
	}
	
	// SM admin
	apply_sql("../db/update_SMAdmin_access.sql", $customerid, $db);
	
	return true;
}

?>