<?

function upgrade_11_4($rev, $shardid, $customerid, $db) {
	global $authdb;

	switch ($rev) {
	default:
	case 0:
		echo "|";
		apply_sql("upgrades/db_11-4_pre.sql", $customerid, $db, 1);
	case 1:
		echo "|";
		apply_sql("upgrades/db_11-4_pre.sql", $customerid, $db, 2);
	case 2:
		echo "|";
		apply_sql("upgrades/db_11-4_pre.sql", $customerid, $db, 3);
	case 3:
		echo "|";
		
		$cmaAppId = QuickQuery("select value from setting where name = '_cmaappid'", $db);
		$cmaAppType = "none";
		if ($cmaAppId > 0) {
			$cmaAppType = "legacy";
		}
		QuickUpdate("insert into setting (name, value) values ('_cmaapptype', ?)", $db, array($cmaAppType));

		apply_sql("upgrades/db_11-4_pre.sql", $customerid, $db, 4);
	case 4:
		echo "|";
		apply_sql("upgrades/db_11-4_pre.sql", $customerid, $db, 5);
	case 5:
		echo "|";
		apply_sql("upgrades/db_11-4_pre.sql", $customerid, $db, 6);
	case 6:
		echo "|";
		apply_sql("upgrades/db_11-4_pre.sql", $customerid, $db, 7);
	}
	//This statement should appear in each upgrade script, when relevant.
	apply_sql("../db/update_SMAdmin_access.sql", $customerid, $db);

	return true;
}

?>
