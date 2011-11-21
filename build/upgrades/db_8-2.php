<?
function upgrade_8_2 ($rev, $shardid, $customerid, $db) {
	global $authdb;
	
	switch($rev) {
		default:
		case 0:
			echo "|";
			apply_sql("upgrades/db_8-2_pre.sql", $customerid, $db, 1);
			
	}
	
	return true;	
}
?>