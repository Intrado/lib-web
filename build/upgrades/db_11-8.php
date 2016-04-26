<?

function upgrade_11_8($rev, $shardid, $customerid, $db) {
	global $authdb;

	switch ($rev) {
	default:
	case 0:
		// no-op
	case 1:
		echo "|";
		apply_sql("upgrades/db_11-8_pre.sql", $customerid, $db, 2);

		//Set _hasinfocenter value
		$cnt = QuickQuery("select count(*) from setting where name='_hasinfocenter' and organizationid is null", $db);
		if($cnt == 0) {
			QuickUpdate("insert into setting (name, value) values ('_hasinfocenter', ?)", $db, array(1));
		}
		else {
			QuickUpdate("update setting set value = ? where name = '_hasinfocenter' and organizationid is null", $db, array(1));
		}
	case 2:
		echo "|";
		apply_sql("upgrades/db_11-8_pre.sql", $customerid, $db, 3);
	}
	//This statement should appear in each upgrade script, when relevant.
	apply_sql("../db/update_SMAdmin_access.sql", $customerid, $db);

	return true;
}

?>
