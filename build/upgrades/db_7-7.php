<?

function upgrade_7_7 ($rev, $shardid, $customerid, $db) {
	
	switch($rev) {
		default:
		case 0:
			echo "|";
			apply_sql("upgrades/db_7-7_pre.sql", $customerid, $db, 1);
			//no code needed, fall through
		case 1:
			// upgrade from rev 1 to rev 2
			echo "|";
			apply_sql("upgrades/db_7-7_pre.sql", $customerid, $db, 2);
			//no code needed, fall through
		case 2:
			// upgrade from rev 2 to rev 3
			echo "|";
			apply_sql("upgrades/db_7-7_pre.sql", $customerid, $db, 3);
			//no code needed, fall through
			
	}
	
	apply_sql("../db/update_SMAdmin_access.sql",$customerid,$db);
	
	return true;
}
			
?>
