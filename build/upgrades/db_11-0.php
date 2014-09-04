<?

function upgrade_11_0($rev, $shardid, $customerid, $db) {
	global $authdb;

	switch ($rev) {
		default:
		case 0:
			echo "|";
			apply_sql("upgrades/db_11-0_pre.sql", $customerid, $db, 1);
		case 1:
			echo "|";
			apply_sql("upgrades/db_11-0_pre.sql", $customerid, $db, 2);
		case 2:
			echo "|";
			apply_sql("upgrades/db_11-0_pre.sql", $customerid, $db, 3);
		case 3:
			echo "|";
			apply_sql("upgrades/db_11-0_pre.sql", $customerid, $db, 4);
		case 4:
			echo "|";
			apply_sql("upgrades/db_11-0_pre.sql", $customerid, $db, 5);

			//enable ICRA for all existing customers
			Query("BEGIN", $db);
			if (!QuickQuery("select count(*) from setting where name = ?", $db, array("_hasicra"))) {
				$portal = QuickQuery("select value from setting where name = ?", $db, array("_hasportal"));
				QuickUpdate("insert into setting (name, value) values ('_hasicra',?)", false, array($portal ? 0 : 1));
			}
			Query("COMMIT", $db);
	}
	//This statement should appear in each upgrade script, when relevent.
	apply_sql("../db/update_SMAdmin_access.sql", $customerid, $db);

	return true;
}

?>
