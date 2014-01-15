<?

require_once("upgrades/db_10-1_oldcode.php");

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
	}
	
	//This statement should appear in each upgrade script, when relevent.
	apply_sql("../db/update_SMAdmin_access.sql", $customerid, $db);
	
	return true;
}


?>
