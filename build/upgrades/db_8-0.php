<?
function upgrade_8_0 ($rev, $shardid, $customerid, $db) {
	
	switch($rev) {
		default:
		case 0:
			echo "|";
			apply_sql("upgrades/db_8-0_pre.sql", $customerid, $db, 1);
	
	}
	
	apply_sql("../db/update_SMAdmin_access.sql", $customerid, $db);
	
	return true;		
}
?>