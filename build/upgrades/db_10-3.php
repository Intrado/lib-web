<?

function upgrade_10_3 ($rev, $shardid, $customerid, $db) {
	global $authdb;

	switch($rev) {
		default:
		case 0:
			echo "|";
			apply_sql("upgrades/db_10-3_pre.sql", $customerid, $db, 1);
			
		case 1:
			echo "|";
			apply_sql("upgrades/db_10-3_pre.sql", $customerid, $db, 2);
			
		case 2:
			echo "|";
			apply_sql("upgrades/db_10-3_pre.sql", $customerid, $db, 3);
			
		case 3:
			echo "|";
			apply_sql("upgrades/db_10-3_pre.sql", $customerid, $db, 4);
			
		case 4:
			echo "|";
			apply_sql("upgrades/db_10-3_pre.sql", $customerid, $db, 5);

		case 5:
			echo "|";
			apply_sql("upgrades/db_10-3_pre.sql", $customerid, $db, 6);
		
		case 6:
			echo "|";
			apply_sql("upgrades/db_10-3_pre.sql", $customerid, $db, 7);
		
	}
	
	// smartcall customers should not have any neospeech voices enabled
	$q = "update ttsvoice set enabled = 0 where provider = 'neospeech' and (select 1 from setting where name = '_dmmethod' and value != 'asp')";
	QuickUpdate($q, $db);
	$q = "update ttsvoice set enabled = 0 where provider = 'neospeech' and (select 1 from custdm where enablestate != 'disabled')";
	QuickUpdate($q, $db);
	
	//This statement should appear in each upgrade script, when relevent.
	apply_sql("../db/update_SMAdmin_access.sql", $customerid, $db);
	
	return true;
}


?>
