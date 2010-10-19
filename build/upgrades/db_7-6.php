<?

function upgrade_7_6 ($rev, $shardid, $customerid, $db) {
	
	switch($rev) {
		default:
		case 0:
			apply_sql("upgrades/db_7-6_pre.sql", $customerid, $db, 1);
			//no code needed, fall through
	}
	
	return true;
}
			
?>
