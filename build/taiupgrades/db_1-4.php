<?

function tai_upgrade_1_4 ($rev, $shardid, $customerid, $db) {
	global $authdb;

	switch($rev) {
		default:
		case 0:
			echo "|";
			apply_sql("taiupgrades/db_1-4_pre.sql", $customerid, $db, 1);

	}
	
	// SM admin
	apply_sql("../db/tai_update_SMAdmin_access.sql", $customerid, $db);
	
	return true;
}

?>