<?
//some db objects here, used to create message templates
require_once("upgrades/db_8-2_oldcode.php");
require_once("../manager/loadtemplatedata.php");


function upgrade_8_2 ($rev, $shardid, $customerid, $db) {
	global $authdb;
	
	switch($rev) {
		default:
		case 0:
			echo "|";
			apply_sql("upgrades/db_8-2_pre.sql", $customerid, $db, 1);
		case 1:
			echo "|";
			apply_sql("upgrades/db_8-2_pre.sql", $customerid, $db, 2);
		case 2:
			echo "|";
			apply_sql("upgrades/db_8-2_pre.sql", $customerid, $db, 3);
		case 3:
			echo "|";
			apply_sql("upgrades/db_8-2_pre.sql", $customerid, $db, 4);
		case 4:
			echo "|";
			apply_sql("upgrades/db_8-2_pre.sql", $customerid, $db, 5);
		case 5:
			echo "|";
			apply_sql("upgrades/db_8-2_pre.sql", $customerid, $db, 6);
		case 6:
			echo "|";
			apply_sql("upgrades/db_8-2_pre.sql", $customerid, $db, 7);
		case 7:
			echo "|";
			apply_sql("upgrades/db_8-2_pre.sql", $customerid, $db, 8);
		case 8:
			echo "|";
			apply_sql("upgrades/db_8-2_pre.sql", $customerid, $db, 9);
			// create any missing email templates
			// set global to customer db, restore after this section
			global $_dbcon;
			$savedbcon = $_dbcon;
			$_dbcon = $db;
				
			if (!createDefaultTemplates_8_2())
				return false;
			
			// restore global db connection
			$_dbcon = $savedbcon;
		case 9:
			echo "|";
			apply_sql("upgrades/db_8-2_pre.sql", $customerid, $db, 10);
					
		case 10:
			echo "|";
					
			// set global to customer db, restore after this section
			global $_dbcon;
			$savedbcon = $_dbcon;
			$_dbcon = $db;
			// if job index startdate is not a single column, drop and recreate
			// correcting mistake made in 8.2/10
			$query = "select max(seq_in_index) from information_schema.STATISTICS where  table_name = 'job' and index_name = 'startdate'";
			if (1 != QuickQuery($query)) {
				$query = "ALTER TABLE `job` DROP INDEX `startdate`";
				QuickUpdate($query);
				$query = "ALTER TABLE `job` ADD INDEX `startdate` ( `startdate` )";
				QuickUpdate($query);
			}
			// restore global db connection
			$_dbcon = $savedbcon;
			apply_sql("upgrades/db_8-2_pre.sql", $customerid, $db, 11);
				
	}

	// SM admin
	apply_sql("../db/update_SMAdmin_access.sql",$customerid,$db);
	
	return true;	
}

function createDefaultTemplates_8_2() {

	// check if customer has callback to inbound number, used in sms messagelink
	if (getCustomerSystemSetting("_hascallback", "0") && 0 < strlen(trim(getCustomerSystemSetting("inboundnumber", ""))))
		$useSmsMessagelinkInboundnumber = true;
	else
		$useSmsMessagelinkInboundnumber = false;
	
	// load template data from file, used to populate database
	$templates = loadTemplateData($useSmsMessagelinkInboundnumber);
	
	// query existing templates, create any that are missing (release 7.8 used to create them, but after refactor of how to create templates moved logic here to 8.2)
	$existingTemplateTypes = QuickQueryList("select type, type from template", true);
	
	// emergency
	if (!isset($existingTemplateTypes['emergency'])) {
		$notification_englishhtml = $templates['emergency']['en']['html']['body'];
		$notification_englishplain = $templates['emergency']['en']['plain']['body'];
		$notification_spanishhtml = $templates['emergency']['es']['html']['body'];
		$notification_spanishplain = $templates['emergency']['es']['plain']['body'];
			
		if (!createTemplate_8_2('emergency', $notification_englishplain, $notification_englishhtml, $notification_spanishplain, $notification_spanishhtml))
			return false;
	}

	// messagelink
	if (!isset($existingTemplateTypes['messagelink'])) {
		$messagelink_englishhtml = $templates['messagelink']['en']['html']['body'];
		$messagelink_englishplain = $templates['messagelink']['en']['plain']['body'];
		$messagelink_spanishhtml = $templates['messagelink']['es']['html']['body'];
		$messagelink_spanishplain = $templates['messagelink']['es']['plain']['body'];
			
		$messagegroupid = createTemplate_8_2('messagelink', $messagelink_englishplain, $messagelink_englishhtml, $messagelink_spanishplain, $messagelink_spanishhtml);
		if (!$messagegroupid)
			return false;
		
		// set english headers
		$data = "subject=" . urlencode($templates['messagelink']['en']['html']['subject']) .
							"&fromname=" . urlencode($templates['messagelink']['en']['html']['fromname']) . 
							"&fromemail=" . urlencode($templates['messagelink']['en']['html']['fromaddr']);
		QuickUpdate("update message set data = ? where messagegroupid = ? and type = 'email' and languagecode = 'en'", null, array($data, $messagegroupid));
		// set spanish headers
		$data = "subject=" . urlencode($templates['messagelink']['es']['html']['subject']) .
							"&fromname=" . urlencode($templates['messagelink']['es']['html']['fromname']) . 
							"&fromemail=" . urlencode($templates['messagelink']['es']['html']['fromaddr']);
		QuickUpdate("update message set data = ? where messagegroupid = ? and type = 'email' and languagecode = 'es'", null, array($data, $messagegroupid));
			
		//// SMS messagelink
		// create message
		$message = new Message_8_2();
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
		$messagepart = new MessagePart_8_2();
		$messagepart->messageid = $message->id;
		$messagepart->type = "T";
		$messagepart->txt = $templates['messagelink']['en']['sms']['body'];
		$messagepart->sequence = 0;
		if (!$messagepart->create())
			return false;
	}
	
	// monitor
	if (!isset($existingTemplateTypes['monitor'])) {
		$monitor_englishhtml = $templates['monitor']['en']['html']['body'];
		$monitor_englishplain = $templates['monitor']['en']['plain']['body'];
			
		$messagegroupid = createTemplate_8_2('monitor', $monitor_englishplain, $monitor_englishhtml, $monitor_englishplain, $monitor_englishhtml);
		if (!$messagegroupid)
			return false;
		
		// set english and spanish headers, they are the same
		$data = "subject=" . urlencode($templates['monitor']['en']['html']['subject']) .
								"&fromname=" . urlencode($templates['monitor']['en']['html']['fromname']) . 
								"&fromemail=" . urlencode($templates['monitor']['en']['html']['fromaddr']);
		QuickUpdate("update message set data = ? where messagegroupid = ? and type = 'email'", null, array($data, $messagegroupid));
	}
	
	// notification
	if (!isset($existingTemplateTypes['notification'])) {
		$notification_englishhtml = $templates['notification']['en']['html']['body'];
		$notification_englishplain = $templates['notification']['en']['plain']['body'];
		$notification_spanishhtml = $templates['notification']['es']['html']['body'];
		$notification_spanishplain = $templates['notification']['es']['plain']['body'];
		
		if (!createTemplate_8_2('notification', $notification_englishplain, $notification_englishhtml, $notification_spanishplain, $notification_spanishhtml))
			return false;
	}
	
	// subscriber
	if (!isset($existingTemplateTypes['subscriber'])) {
		$subscriber_englishhtml = $templates['subscriber-accountexpire']['en']['html']['body'];
		$subscriber_englishplain = $templates['subscriber-accountexpire']['en']['plain']['body'];
		$subscriber_spanishhtml = $templates['subscriber-accountexpire']['es']['html']['body'];
		$subscriber_spanishplain = $templates['subscriber-accountexpire']['es']['plain']['body'];
			
		$messagegroupid = createTemplate_8_2('subscriber', $subscriber_englishplain, $subscriber_englishhtml, $subscriber_spanishplain, $subscriber_spanishhtml);
		if (!$messagegroupid)
			return false;
		
		// set english headers
		$data = "subject=" . urlencode($templates['subscriber-accountexpire']['en']['html']['subject']) .
							"&fromname=" . urlencode($templates['subscriber-accountexpire']['en']['html']['fromname']) . 
							"&fromemail=" . urlencode($templates['subscriber-accountexpire']['en']['html']['fromaddr']);
		QuickUpdate("update message set data = ? where messagegroupid = ? and type = 'email' and languagecode = 'en'", null, array($data, $messagegroupid));
		// set spanish headers
		$data = "subject=" . urlencode($templates['subscriber-accountexpire']['es']['html']['subject']) .
							"&fromname=" . urlencode($templates['subscriber-accountexpire']['es']['html']['fromname']) . 
							"&fromemail=" . urlencode($templates['subscriber-accountexpire']['es']['html']['fromaddr']);
		QuickUpdate("update message set data = ? where messagegroupid = ? and type = 'email' and languagecode = 'es'", null, array($data, $messagegroupid));
	}
	
	// survey
	if (!isset($existingTemplateTypes['survey'])) {
		$survey_englishhtml = $templates['survey']['en']['html']['body'];
		$survey_englishplain = $templates['survey']['en']['plain']['body'];
		$survey_spanishhtml = $templates['survey']['en']['html']['body']; // spanish is not used yet
		$survey_spanishplain = $templates['survey']['en']['plain']['body'];
			
		if (!createTemplate_8_2('survey', $survey_englishplain, $survey_englishhtml, $survey_spanishplain, $survey_spanishhtml))
			return false;
	}
	
	// SUCCESS
	return true;
}

function createTemplate_8_2($templatetype, $englishplain, $englishhtml, $spanishplain, $spanishhtml) {
	$messagegroup = new MessageGroup_8_2();
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
	$message = new Message_8_2();
	$message->messagegroupid = $messagegroup->id;
	$message->userid = null;
	$message->name = $templatetype . " Template";
	$message->description = "English Plain";
	$message->type = "email";
	$message->subtype = "plain";
	$message->data = "";
	$message->modifydate = date('Y-m-d H:i:s');
	$message->autotranslate = "none";
	$message->languagecode = "en";
	if (!$message->create())
		return false;
		
	// create messagepart
	$messagepart = new MessagePart_8_2();
	$messagepart->messageid = $message->id;
	$messagepart->type = "T";
	$messagepart->txt = $englishplain;
	$messagepart->sequence = 0;
	if (!$messagepart->create())
		return false;

	//// English HTML
	// create message
	$message = new Message_8_2();
	$message->messagegroupid = $messagegroup->id;
	$message->userid = null;
	$message->name = $templatetype . " Template";
	$message->description = "English Html";
	$message->type = "email";
	$message->subtype = "html";
	$message->data = "";
	$message->modifydate = date('Y-m-d H:i:s');
	$message->autotranslate = "none";
	$message->languagecode = "en";
	if (!$message->create())
		return false;
		
	// create messagepart
	$messagepart = new MessagePart_8_2();
	$messagepart->messageid = $message->id;
	$messagepart->type = "T";
	$messagepart->txt = $englishhtml;
	$messagepart->sequence = 0;
	if (!$messagepart->create())
		return false;

	//// Spanish Plain
	// create message
	$message = new Message_8_2();
	$message->messagegroupid = $messagegroup->id;
	$message->userid = null;
	$message->name = $templatetype . " Template";
	$message->description = "Spanish Plain";
	$message->type = "email";
	$message->subtype = "plain";
	$message->data = "";
	$message->modifydate = date('Y-m-d H:i:s');
	$message->autotranslate = "none";
	$message->languagecode = "es";
	if (!$message->create())
		return false;
		
	// create messagepart
	$messagepart = new MessagePart_8_2();
	$messagepart->messageid = $message->id;
	$messagepart->type = "T";
	$messagepart->txt = $spanishplain;
	$messagepart->sequence = 0;
	if (!$messagepart->create())
		return false;
		
	//// Spanish HTML
	// create message
	$message = new Message_8_2();
	$message->messagegroupid = $messagegroup->id;
	$message->userid = null;
	$message->name = $templatetype . " Template";
	$message->description = "Spanish Html";
	$message->type = "email";
	$message->subtype = "html";
	$message->data = "";
	$message->modifydate = date('Y-m-d H:i:s');
	$message->autotranslate = "none";
	$message->languagecode = "es";
	if (!$message->create())
		return false;
		
	// create messagepart
	$messagepart = new MessagePart_8_2();
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