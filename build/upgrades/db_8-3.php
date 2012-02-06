<?

function upgrade_8_3 ($rev, $shardid, $customerid, $db) {
	global $authdb;

	switch($rev) {
		default:
		case 0:
			echo "|";
			apply_sql("upgrades/db_8-3_pre.sql", $customerid, $db, 1);
	}
	
	return true;
}

?>