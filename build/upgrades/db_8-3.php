<?

function upgrade_8_3 ($rev, $shardid, $customerid, $db) {
	global $authdb;

	switch($rev) {
		default:
		case 0:
			echo "|";
			apply_sql("upgrades/db_8-3_pre.sql", $customerid, $db, 1);
		case 1:
			echo "|";
			apply_sql("upgrades/db_8-3_pre.sql", $customerid, $db, 2);
		case 2:
			echo "|";
			apply_sql("upgrades/db_8-3_pre.sql", $customerid, $db, 3);
		case 3:
			echo "|";
			apply_sql("upgrades/db_8-3_pre.sql", $customerid, $db, 4);
		case 4:
			echo "|";
			apply_sql("upgrades/db_8-3_pre.sql", $customerid, $db, 5);
	}
	
	return true;
}

?>