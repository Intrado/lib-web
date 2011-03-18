<?
/*
 * put required external objects in the db_7-8_oldcode.php file. DO NOT INCLUDE files from kona
 */

//some db objects here, used to create message templates
require_once("upgrades/db_7-8_oldcode.php");
require_once("../db/createtemplates.php");


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
			
			// set global to customer db, restore after this section
			global $_dbcon;
			$savedbcon = $_dbcon;
			$_dbcon = $db;
			
			if (getCustomerSystemSetting("_hascallback", "0") && 0 < strlen(trim(getCustomerSystemSetting("inboundnumber", ""))))
				$useSmsMessagelinkInboundnumber = true;
			else
				$useSmsMessagelinkInboundnumber = false;
			
			if (!createDefaultTemplates_7_8('7-8', $useSmsMessagelinkInboundnumber))
				return false;
			
			// restore global db connection
			$_dbcon = $savedbcon;
			
			
	}
	
	apply_sql("../db/update_SMAdmin_access.sql",$customerid,$db);
	
	return true;
}
			
?>
