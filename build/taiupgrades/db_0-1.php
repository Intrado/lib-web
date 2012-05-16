<?

function tai_upgrade_0_1 ($rev, $shardid, $customerid, $db) {
	global $authdb;

	switch($rev) {
		default:
		case 0:
			echo "|";
			apply_sql("taiupgrades/db_0-1_pre.sql", $customerid, $db, 1);
	}
	
	return true;
}

?>