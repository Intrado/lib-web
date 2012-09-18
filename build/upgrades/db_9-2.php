<?

function upgrade_9_2 ($rev, $shardid, $customerid, $db) {
	global $authdb;

	switch($rev) {
		default:
		case 0:
			echo "|";
			apply_sql("upgrades/db_9-2_pre.sql", $customerid, $db, 1);
	}
	
	return true;
}

?>