<?
function upgrade_8_1 ($rev, $shardid, $customerid, $db) {
	global $authdb;
	
	switch($rev) {
		default:
		case 0:
			echo "|";
			apply_sql("upgrades/db_8-1_pre.sql", $customerid, $db, 1);
		case 1:
			echo "|";
			apply_sql("upgrades/db_8-1_pre.sql", $customerid, $db, 2);
		case 2:
			echo "|";
			apply_sql("upgrades/db_8-1_pre.sql", $customerid, $db, 3);
		case 3:
			echo "|";
			apply_sql("upgrades/db_8-1_pre.sql", $customerid, $db, 4);
		case 4:
			echo "|";
			$customerenabled = QuickQuery("select enabled from customer where id = ?", $authdb, array($customerid));
			QuickUpdate("insert into setting (name, value) values ('_customerenabled', ?)", $db, array($customerenabled));
			apply_sql("upgrades/db_8-1_pre.sql", $customerid, $db, 5);	
		case 5:
			echo "|";
			apply_sql("upgrades/db_8-1_pre.sql", $customerid, $db, 6);
		case 6:
			echo "|";
			apply_sql("upgrades/db_8-1_pre.sql", $customerid, $db, 7);
		case 7:
			echo "|";
			apply_sql("upgrades/db_8-1_pre.sql", $customerid, $db, 8);
		case 8:
			echo "|";
			apply_sql("upgrades/db_8-1_pre.sql", $customerid, $db, 9);
	}
	
	return true;	
}
?>