<?
function upgrade_11_1($rev, $shardid, $customerid, $db) {
	global $authdb;

	switch ($rev) {
		default:
		case 0:
			echo "|";
			apply_sql("upgrades/db_11-1_pre.sql", $customerid, $db, 1);
		case 1:
			echo "|";
			apply_sql("upgrades/db_11-1_pre.sql", $customerid, $db, 2);
			
			// rename InfoCenter settings
			Query("BEGIN", $db);
			QuickUpdate("update setting set name = '_hasicplus' where name = '_hasinfocenter'", $db);
			QuickUpdate("update setting set name = '_hasinfocenter' where name = '_hasicra'", $db);
			
			$hasGuardian = QuickQuery("select value from setting where name = 'maxguardians'", $db);
			$hasContactManager = QuickQuery("select value from setting where name = '_hasportal'", $db);
			$hasSubscriber = QuickQuery("select value from setting where name = '_hasselfsignup'", $db);
			
			if ($hasGuardian > 0 && !$hasContactManager && !$hasSubscriber) {
				QuickUpdate("update setting set value = '1' where name = '_hasinfocenter'", $db);
			} else {
				QuickUpdate("update setting set value = '0' where name = '_hasinfocenter'", $db);
			}
			Query("COMMIT", $db);
		case 2:
			echo "|";
			apply_sql("upgrades/db_11-1_pre.sql", $customerid, $db, 3);
			
			// no schema, disable all _hasinfocenter (keeping _hasicplus)
			// manual process by support to enable infocenter and guardian data for our customers
			Query("BEGIN", $db);
			$hasICPlus = QuickQuery("select value from setting where name = '_hasicplus'", $db);
			
			// if has ICPlus, must also have IC, all other customers disabled
			if ($hasICPlus) {
				QuickUpdate("update setting set value = '1' where name = '_hasinfocenter'", $db);
			} else {
				QuickUpdate("update setting set value = '0' where name = '_hasinfocenter'", $db);
			}
			Query("COMMIT", $db);
			case 3:
				echo "|";
				apply_sql("upgrades/db_11-1_pre.sql", $customerid, $db, 4);
			case 4:
				echo "|";
				apply_sql("upgrades/db_11-1_pre.sql", $customerid, $db, 5);
				Query("BEGIN", $db);
				QuickUpdate("update list set recipientmode = 'selfAndGuardian'", $db);
				Query("COMMIT", $db);

	}
	//This statement should appear in each upgrade script, when relevent.
	apply_sql("../db/update_SMAdmin_access.sql", $customerid, $db);

	return true;
}

?>
