<?

function tai_upgrade_1_3 ($rev, $shardid, $customerid, $db) {
	global $authdb;

	switch($rev) {
		default:
		case 0:
			echo "|";
			apply_sql("taiupgrades/db_1-3_pre.sql", $customerid, $db, 1);

	}
	
	return true;
}

?>