<?

function tai_upgrade_0_1 ($rev, $shardid, $customerid, $db) {
	global $authdb;

	switch($rev) {
		default:
		case 0:
			echo "|";
			apply_sql("taiupgrades/db_0-1_pre.sql", $customerid, $db, 1);
		case 1:
			echo "|";
			apply_sql("taiupgrades/db_0-1_pre.sql", $customerid, $db, 2);
		case 2:
			echo "|";
			apply_sql("taiupgrades/db_0-1_pre.sql", $customerid, $db, 3);
		case 3:
			echo "|";
			apply_sql("taiupgrades/db_0-1_pre.sql", $customerid, $db, 4);
		case 4:
			echo "|";
			apply_sql("taiupgrades/db_0-1_pre.sql", $customerid, $db, 5);
		case 5:
			echo "|";
			apply_sql("taiupgrades/db_0-1_pre.sql", $customerid, $db, 6);
		case 6:
			echo "|";
			apply_sql("taiupgrades/db_0-1_pre.sql", $customerid, $db, 7);
		case 7:
			echo "|";
			apply_sql("taiupgrades/db_0-1_pre.sql", $customerid, $db, 8);
	}
	
	return true;
}

?>