<?
function reportcontact_recipientpersonid_11_0($db) {
	$hasChanges = false;
	Query("BEGIN", $db);
	$hasChanges = QuickUpdate("update reportcontact set recipientpersonid = personid where recipientpersonid = 0 limit 10000", $db);
	Query("COMMIT", $db);
	return $hasChanges;
}

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
		case 5:
			echo "|";
			apply_sql("upgrades/db_11-1_pre.sql", $customerid, $db, 6);
			Query("BEGIN", $db);
			$maxguardians = QuickQuery("select value from setting where name = 'maxguardians'", $db);
			if ($maxguardians > 0) {
				QuickUpdate("update list set recipientmode = 'selfAndGuardian'", $db);
			}else {
				QuickUpdate("update list set recipientmode = 'self'", $db);
			}
			Query("COMMIT", $db);

		case 6:
			echo "|";
			apply_sql("upgrades/db_11-1_pre.sql", $customerid, $db, 7);
					
			// reportcontact change in 11.0/7 some test servers may have missed so be nice and apply now
			$hasChange = QuickQuery("select column_name from information_schema.columns where table_schema = ? and table_name = 'reportcontact' and column_name = 'recipientpersonid'", $db, array("c_".$customerid));
			if (!$hasChange) {
				echo "alter reportcontact for test servers do not expect this on production";
				QuickUpdate("ALTER TABLE `reportcontact` ADD `recipientpersonid` INT NOT NULL default 0 AFTER `sequence`, ADD INDEX (`recipientpersonid`)", $db);
			}
			// batch update reportcontact recipientpersonid, loop over small batches
			while (reportcontact_recipientpersonid_11_0($db)) {
				// keep running until no more changes
				echo ".";
			}
			
		case 7:
			echo "|";
			apply_sql("upgrades/db_11-1_pre.sql", $customerid, $db, 8);
		case 8:
			echo "|";
			apply_sql("upgrades/db_11-1_pre.sql", $customerid, $db, 9);
		case 9:
			echo "|";
			apply_sql("upgrades/db_11-1_pre.sql", $customerid, $db, 10);
		case 10:
			echo "|";
			apply_sql("upgrades/db_11-1_pre.sql", $customerid, $db, 11);
		case 11:
			echo "|";
			apply_sql("upgrades/db_11-1_pre.sql", $customerid, $db, 12);
		case 12:
			echo "|";
			apply_sql("upgrades/db_11-1_pre.sql", $customerid, $db, 13);
	}
	//This statement should appear in each upgrade script, when relevent.
	apply_sql("../db/update_SMAdmin_access.sql", $customerid, $db);

	return true;
}

?>
