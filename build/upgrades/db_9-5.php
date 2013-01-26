<?

function upgrade_9_5 ($rev, $shardid, $customerid, $db) {
	global $authdb;

	switch($rev) {
		default:
		case 0:
			echo "|";
			apply_sql("upgrades/db_9-5_pre.sql", $customerid, $db, 1);
		case 1:
			echo "|";
			apply_sql("upgrades/db_9-5_pre.sql", $customerid, $db, 2);
	}
	
	return true;
}


?>