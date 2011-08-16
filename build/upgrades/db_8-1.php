<?
function upgrade_8_1 ($rev, $shardid, $customerid, $db) {
	
	switch($rev) {
		default:
		case 0:
			echo "|";
			apply_sql("upgrades/db_8-1_pre.sql", $customerid, $db, 1);
	}
	
	return true;	
}
?>