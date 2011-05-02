<?
/*
 * put required external objects in the db_7-8_oldcode.php file. DO NOT INCLUDE files from kona
 */

//some db objects here, used to create message templates
require_once("upgrades/db_7-8_oldcode.php");


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
			
			if (!createDefaultTemplates_7_8($useSmsMessagelinkInboundnumber))
				return false;
			
			// restore global db connection
			$_dbcon = $savedbcon;
		
		case 6:
			// case 6 is rev 7, 7.8/7			
			echo "|";
			apply_sql("upgrades/db_7-8_pre.sql", $customerid, $db, 7);

			// set global to customer db, restore after this section
			global $_dbcon;
			$savedbcon = $_dbcon;
			$_dbcon = $db;
			
			$templatedata = explode("$$$",file_get_contents("../manager/templatedata.txt"));
			
			// subscriber template
			$subscriber_englishhtml = $templatedata[13];
			$subscriber_englishplain = $templatedata[14];
			$subscriber_spanishhtml = $templatedata[15];
			$subscriber_spanishplain = $templatedata[16];
			
			$messagegroupid = createTemplate('subscriber', $subscriber_englishplain, $subscriber_englishhtml, $subscriber_spanishplain, $subscriber_spanishhtml);
			if (!$messagegroupid)
				return false;
				
			// set english headers
			$data = "subject=" . urlencode("\${displayname} \${productname} Account Termination Warning") . 
					"&fromname=" . urlencode("\${productname}") . 
					"&fromemail=" . urlencode("contactme@schoolmessenger.com");
			QuickUpdate("update message set data = ? where messagegroupid = ? and type = 'email' and languagecode = 'en'", null, array($data, $messagegroupid));
			// set spanish headers
			$data = "subject=" . urlencode("\${displayname} \${productname} Advertencia Cancelaci&oacute;n de cuenta") . 
					"&fromname=" . urlencode("\${productname}") . 
					"&fromemail=" . urlencode("contactme@schoolmessenger.com");
			QuickUpdate("update message set data = ? where messagegroupid = ? and type = 'email' and languagecode = 'es'", null, array($data, $messagegroupid));
						
			// restore global db connection
			$_dbcon = $savedbcon;
			
	}
	
	apply_sql("../db/update_SMAdmin_access.sql",$customerid,$db);
	
	return true;
}


function createDefaultTemplates_7_8($useSmsMessagelinkInboundnumber = false) {
	
	$templatedata = explode("$$$",file_get_contents("../manager/templatedata.txt"));
	
			////////////////////////
			// general notification
			$notification_englishhtml = $templatedata[1];
			$notification_englishplain = $templatedata[2];
			$notification_spanishhtml = $templatedata[3];
			$notification_spanishplain = $templatedata[4];
	
			if (!createTemplate('notification', $notification_englishplain, $notification_englishhtml, $notification_spanishplain, $notification_spanishhtml))
				return false;

			////////////////////////
			// emergency notification
			$notification_englishhtml = $templatedata[5];
			$notification_englishplain = $templatedata[6];
			$notification_spanishhtml = $templatedata[7];
			$notification_spanishplain = $templatedata[8];
			
			if (!createTemplate('emergency', $notification_englishplain, $notification_englishhtml, $notification_spanishplain, $notification_spanishhtml))
				return false;
				
			///////////////////////
			// messagelink
			$messagelink_englishhtml = $templatedata[9];
			$messagelink_englishplain = $templatedata[10];
			
			$messagelink_spanishhtml = $templatedata[11];
			$messagelink_spanishplain = $templatedata[12];
			
			$messagegroupid = createTemplate('messagelink', $messagelink_englishplain, $messagelink_englishhtml, $messagelink_spanishplain, $messagelink_spanishhtml);
			if (!$messagegroupid)
				return false;
				
			// set english headers
			$data = "subject=" . urlencode("\${displayname} sent a new message") . 
					"&fromname=" . urlencode("\${productname}") . 
					"&fromemail=" . urlencode("contactme@schoolmessenger.com");
			QuickUpdate("update message set data = ? where messagegroupid = ? and type = 'email' and languagecode = 'en'", null, array($data, $messagegroupid));
			// set spanish headers
			$data = "subject=" . urlencode("\${displayname} le envió un mensaje nuevo") . 
					"&fromname=" . urlencode("\${productname}") . 
					"&fromemail=" . urlencode("contactme@schoolmessenger.com");
			QuickUpdate("update message set data = ? where messagegroupid = ? and type = 'email' and languagecode = 'es'", null, array($data, $messagegroupid));
			
			//// SMS messagelink
			if ($useSmsMessagelinkInboundnumber) {
				$appendinbound = " or \${inboundnumber}.";
			} else {
				$appendinbound = "";
			}
			// create message
			$message = new Message_7_8_r2();
			$message->messagegroupid = $messagegroupid;
			$message->userid = null;
			$message->name = "messagelink Template";
			$message->description = "English SMS";
			$message->type = "sms";
			$message->subtype = "plain";
			$message->data = "";
			$message->modifydate = date('Y-m-d H:i:s');
			$message->deleted = 0;
			$message->autotranslate = "none";
			$message->languagecode = "en";
			if (!$message->create()) 
				return false;
			
			// create messagepart
			$messagepart = new MessagePart_7_8_r2();
			$messagepart->messageid = $message->id;
			$messagepart->type = "T";
			$messagepart->txt = "\${displayname} sent a msg. To listen \${messagelink}" . $appendinbound . "\nFor info txt HELP";
			$messagepart->sequence = 0;
			if (!$messagepart->create())
				return false;
			
			
				
			////////////////////////
			// survey
			$survey_englishhtml = $templatedata[17];
			$survey_englishplain = $templatedata[18];
			$survey_spanishhtml = $templatedata[17]; // spanish is not used yet
			$survey_spanishplain = $templatedata[18];
			
			if (!createTemplate('survey', $survey_englishplain, $survey_englishhtml, $survey_spanishplain, $survey_spanishhtml))
				return false;
			
			// SUCCESS	
			return true;
}

function createTemplate($templatetype, $englishplain, $englishhtml, $spanishplain, $spanishhtml) {
	$messagegroup = new MessageGroup_7_8_r2();
	// create messagegroup
	$messagegroup->userid = null;
	$messagegroup->type = "systemtemplate";
	$messagegroup->name = $templatetype . " Template";
	$messagegroup->description = "";
	$messagegroup->modified = date('Y-m-d H:i:s');
	$messagegroup->permanent = 1;
	$messagegroup->deleted = 0;
	$messagegroup->defaultlanguagecode = "en";
	if (!$messagegroup->create())
		return false;
			
	//// English Plain
	// create message
	$message = new Message_7_8_r2();
	$message->messagegroupid = $messagegroup->id;
	$message->userid = null;
	$message->name = $templatetype . " Template";
	$message->description = "English Plain";
	$message->type = "email";
	$message->subtype = "plain";
	$message->data = "";
	$message->modifydate = date('Y-m-d H:i:s');
	$message->deleted = 0;
	$message->autotranslate = "none";
	$message->languagecode = "en";
	if (!$message->create()) 
		return false;
			
	// create messagepart
	$messagepart = new MessagePart_7_8_r2();
	$messagepart->messageid = $message->id;
	$messagepart->type = "T";
	$messagepart->txt = $englishplain;
	$messagepart->sequence = 0;
	if (!$messagepart->create())
		return false;
		
	//// English HTML
	// create message
	$message = new Message_7_8_r2();
	$message->messagegroupid = $messagegroup->id;
	$message->userid = null;
	$message->name = $templatetype . " Template";
	$message->description = "English Html";
	$message->type = "email";
	$message->subtype = "html";
	$message->data = "";
	$message->modifydate = date('Y-m-d H:i:s');
	$message->deleted = 0;
	$message->autotranslate = "none";
	$message->languagecode = "en";
	if (!$message->create()) 
		return false;
			
	// create messagepart
	$messagepart = new MessagePart_7_8_r2();
	$messagepart->messageid = $message->id;
	$messagepart->type = "T";
	$messagepart->txt = $englishhtml;
	$messagepart->sequence = 0;
	if (!$messagepart->create())
		return false;
	
	//// Spanish Plain
	// create message
	$message = new Message_7_8_r2();
	$message->messagegroupid = $messagegroup->id;
	$message->userid = null;
	$message->name = $templatetype . " Template";
	$message->description = "Spanish Plain";
	$message->type = "email";
	$message->subtype = "plain";
	$message->data = "";
	$message->modifydate = date('Y-m-d H:i:s');
	$message->deleted = 0;
	$message->autotranslate = "none";
	$message->languagecode = "es";
	if (!$message->create()) 
		return false;
			
	// create messagepart
	$messagepart = new MessagePart_7_8_r2();
	$messagepart->messageid = $message->id;
	$messagepart->type = "T";
	$messagepart->txt = $spanishplain;
	$messagepart->sequence = 0;
	if (!$messagepart->create())
		return false;
			
	//// Spanish HTML
	// create message
	$message = new Message_7_8_r2();
	$message->messagegroupid = $messagegroup->id;
	$message->userid = null;
	$message->name = $templatetype . " Template";
	$message->description = "Spanish Html";
	$message->type = "email";
	$message->subtype = "html";
	$message->data = "";
	$message->modifydate = date('Y-m-d H:i:s');
	$message->deleted = 0;
	$message->autotranslate = "none";
	$message->languagecode = "es";
	if (!$message->create()) 
		return false;
			
	// create messagepart
	$messagepart = new MessagePart_7_8_r2();
	$messagepart->messageid = $message->id;
	$messagepart->type = "T";
	$messagepart->txt = $spanishhtml;
	$messagepart->sequence = 0;
	if (!$messagepart->create())
		return false;
	
	// find if template already exists, and delete all messages for it
	$oldmgid = QuickQuery("select messagegroupid from template where type = ?", null, array($templatetype));
	if ($oldmgid) {
		// hard delete old template messagegroup and messages
		QuickUpdate("delete from messagepart where messageid in (select id from message where messagegroupid = ?)", null, array($oldmgid));
		QuickUpdate("delete from message where messagegroupid = ?", null, array($oldmgid));
		QuickUpdate("delete from messagegroup where id = ?", null, array($oldmgid));
		
		//hard delete old template record
		QuickUpdate("delete from template where type = ?", null, array($templatetype));
	}
	
	// create template record for messagegroup
	if (!QuickUpdate("insert into template (type, messagegroupid) values (?, ?)", null, array($templatetype, $messagegroup->id)))
		return false;
				
	// success			
	return $messagegroup->id;
}


?>
