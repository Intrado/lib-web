<?

function encodeImage($fileName) {
	return base64_encode(file_get_contents($fileName));
}

/**
 * Backup an existing setting
 * 
 * @param type $settingName setting name
 * @param type $backupSettingName new setting name
 * @param type $db db connection
 */
function backupSetting($settingName, $backupSettingName, $db) {
	QuickUpdate("insert into setting (organizationid, name, value) select organizationid, '".$backupSettingName."', value from setting where name = ?", $db, array($settingName));
}

/**
 * Insert an image in content table and update setting table.
 * @param type $fileName file anme
 * @param type $contentType content type
 * @param type $settingName setting name
 * @param type $db connection
 */
function insertImage($fileName, $contentType, $settingName, $backupSetting, $db) {
	//first backup setting 
	backupSetting($settingName, $backupSetting, $db);
	$data = encodeImage($fileName);
	$q = "insert into content (contenttype, data) values (? , ?)";
	QuickUpdate($q, $db, array($contentType, $data));
	$contentid = $db->lastInsertId();
	$q = "update setting set value = ? where name = ?";
	QuickUpdate($q, $db, array($contentid, $settingName));
	return $contentid;
}

function upgrade_10_3($rev, $shardid, $customerid, $db) {
	global $authdb;

	switch ($rev) {
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

		case 7:
			echo "|";
			apply_sql("upgrades/db_10-3_pre.sql", $customerid, $db, 8);

			// smartcall customers should not have any neospeech voices enabled
			$q = "update ttsvoice set enabled = 0 where provider = 'neospeech' and (select 1 from setting where name = '_dmmethod' and value != 'asp')";
			QuickUpdate($q, $db);
			$q = "update ttsvoice set enabled = 0 where provider = 'neospeech' and (select 1 from custdm where enablestate != 'disabled')";
			QuickUpdate($q, $db);
	
			Query("BEGIN", $db);
			//update login image for the customer
			$product = QuickQuery("select value from setting where name = ?", $db, array("_productname"));
			//file is based on product selected
			$file = $product == "AutoMessenger" ? "./upgrades/img/login_am_10-3.jpg" : "./upgrades/img/login_sm_10-3.jpg";
			insertImage($file, "image/jpg", "_loginpicturecontentid", "_backuploginpicturecontentid", $db);
			//update login image subscriber
			insertImage("./upgrades/img/login_subscriber_10-3.jpg", "image/jpg", "_subscriberloginpicturecontentid", "_backupsubscriberloginpicturecontentid", $db);
			Query("COMMIT", $db);

		case 8:
			echo "|";
			apply_sql("upgrades/db_10-3_pre.sql", $customerid, $db, 9);

		case 9:
			echo "|";
			apply_sql("upgrades/db_10-3_pre.sql", $customerid, $db, 10);

	}
	//This statement should appear in each upgrade script, when relevent.
	apply_sql("../db/update_SMAdmin_access.sql", $customerid, $db);

	return true;
}

?>
