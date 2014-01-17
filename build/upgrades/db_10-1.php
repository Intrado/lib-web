<?

function upgrade_10_1 ($rev, $shardid, $customerid, $db) {
	global $authdb;

	switch($rev) {
		default:
		case 0:
			echo "|";
			apply_sql("upgrades/db_10-1_pre.sql", $customerid, $db, 1);
			
		case 1:
			echo "|";
			apply_sql("upgrades/db_10-1_pre.sql", $customerid, $db, 2);

		case 2:
			echo "|";
			apply_sql("upgrades/db_10-1_pre.sql", $customerid, $db, 3);

		case 3:
			echo "|";
			apply_sql("upgrades/db_10-1_pre.sql", $customerid, $db, 4);

		case 4:
			echo "|";
			apply_sql("upgrades/db_10-1_pre.sql", $customerid, $db, 5);

		case 5:
			echo "|";
			apply_sql("upgrades/db_10-1_pre.sql", $customerid, $db, 6);

			GLOBAL $_dbcon;
			$old_dbcon = $_dbcon;
			$_dbcon = $db;
			Query("BEGIN");
			$ma_ids = QuickQueryList("select id from messageattachment", false, $db);
			$count = 0;
			foreach ($ma_ids as $ma_id) {
				if ($count++ % 10 == 0)
					echo "*";
				$messageAttachment = new MessageAttachment($ma_id);
				$contentAttachment = new ContentAttachment();
				$contentAttachment->contentid = $messageAttachment->contentid;
				$contentAttachment->filename = $messageAttachment->filename;
				$contentAttachment->size = $messageAttachment->size;
				$contentAttachment->create();

				$messageAttachment->type = 'content';
				$messageAttachment->contentattachmentid = $contentAttachment->id;
				$messageAttachment->update();
			}
			Query("COMMIT");

			$_dbcon = $old_dbcon;

		case 6:
			echo "|";
			apply_sql("upgrades/db_10-1_pre.sql", $customerid, $db, 7);

		case 7:
			echo '|';
			// update the subscriber limited user account
			$custdbname = "c_$customerid";
			$limitedusername = "c_".$customerid."_limited";
			$limitedpassword = genpassword();
			$grantedhost = '%';
			Query("BEGIN", $authdb);
			QuickUpdate("update customer set limitedusername = ?, limitedpassword = ? where id = ?",
					$authdb, array($limitedusername, $limitedpassword, $customerid))
				or dieWithError("failed to insert customer into auth server", $authdb);
			createLimitedUser($limitedusername, $limitedpassword, $custdbname, $db, $grantedhost);
			Query("COMMIT", $authdb);
		
		case 8:
			echo "|";
			apply_sql("upgrades/db_10-1_pre.sql", $customerid, $db, 9);
		
		case 9:
			echo "|";
			apply_sql("upgrades/db_10-1_pre.sql", $customerid, $db, 10);
	}
	
	//This statement should appear in each upgrade script, when relevent.
	apply_sql("../db/update_SMAdmin_access.sql", $customerid, $db);
	
	return true;
}


?>
