<?
//some db objects here, used to create message templates
require_once("upgrades/db_8-2_oldcode.php");

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
			
			// set global to customer db, restore after this section
			global $_dbcon;
			$savedbcon = $_dbcon;
			$_dbcon = $db;
			
			if (!createDefaultTemplates_8_2())
				return false;
				
			// restore global db connection
			$_dbcon = $savedbcon;
		case 6:
			echo "|";
			apply_sql("upgrades/db_8-2_pre.sql", $customerid, $db, 7);
		case 7:
			echo "|";
			apply_sql("upgrades/db_8-2_pre.sql", $customerid, $db, 8);
	
	}
	
	apply_sql("../db/update_SMAdmin_access.sql",$customerid,$db);
	
	return true;	
}

function createDefaultTemplates_8_2() {

	$templatedata = explode("$$$",file_get_contents("../manager/templatedata.txt"));

	///////////////////////
	// monitor
	$monitor_englishhtml = $templatedata[19];
	$monitor_englishplain = $templatedata[20];
		
	$monitor_spanishhtml = $templatedata[19];	// No spanish tamplate created
	$monitor_spanishplain = $templatedata[20];
		
	$messagegroupid = createTemplate_8_2('monitor', $monitor_englishplain, $monitor_englishhtml, $monitor_spanishplain, $monitor_spanishhtml);
	if (!$messagegroupid)
		return false;

	// set english and spanish headers, they are the same  
	$data = "subject=" . urlencode("Monitor Alert: \${monitoralert}") .
					"&fromname=" . urlencode("\${productname}") . 
					"&fromemail=" . urlencode("noreply@schoolmessenger.com");
	QuickUpdate("update message set data = ? where messagegroupid = ? and type = 'email'", null, array($data, $messagegroupid));
	
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
	$message->deleted = 0;
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
	$message->deleted = 0;
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
	$message = new Message_8_2();
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