<?
/*
 * put required external objects in the db_7-8_oldcode.php file. DO NOT INCLUDE files from kona
 */

function upgrade_7_8 ($rev, $shardid, $customerid, $db) {
	
	switch($rev) {
		default:
		case 0:
			echo "|";
			apply_sql("upgrades/db_7-8_pre.sql", $customerid, $db, 1);
			
			apply_sql("upgrades/db_7-8_post.sql", $customerid, $db, 1);
			
		case 1:
			echo "|";
			apply_sql("upgrades/db_7-8_pre.sql", $customerid, $db, 2);
			
		case 2:
			echo "|";
			// moved templates to case 5
			
		case 3:
			echo "|";
			// moved templates to case 5
			
		case 4:
			echo "|";
			// moved templates to case 5

		case 5:
			// case 5 is rev 6, 7.8/6			
			echo "|";
			apply_sql("upgrades/db_7-8_pre.sql", $customerid, $db, 6);
			
			// moved templates to ASP_8-2
			
		case 6:
			// case 6 is rev 7, 7.8/7			
			echo "|";
			apply_sql("upgrades/db_7-8_pre.sql", $customerid, $db, 7);

			// moved templates to ASP_8-2
			
	}
	
	apply_sql("../db/update_SMAdmin_access.sql",$customerid,$db);
	
	return true;
}


?>
