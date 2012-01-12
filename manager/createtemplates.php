<?
// NOTE assumes global $_dbcon already connected to customer database

function createDefaultTemplates($useSmsMessagelinkInboundnumber = false) {
	
	$templatedata = explode("$$$",file_get_contents("templatedata.txt"));
	
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
			$message = new Message();
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
			$messagepart = new MessagePart();
			$messagepart->messageid = $message->id;
			$messagepart->type = "T";
			$messagepart->txt = "\${displayname} sent a msg. To listen \${messagelink}" . $appendinbound . "\nFor info txt HELP";
			$messagepart->sequence = 0;
			if (!$messagepart->create())
				return false;
			
			
			////////////////////////
			// subscriber
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
			$data = "subject=" . urlencode("\${displayname} \${productname} Advertencia Cancelación de cuenta") . 
					"&fromname=" . urlencode("\${productname}") . 
					"&fromemail=" . urlencode("contactme@schoolmessenger.com");
			QuickUpdate("update message set data = ? where messagegroupid = ? and type = 'email' and languagecode = 'es'", null, array($data, $messagegroupid));
				
			////////////////////////
			// survey
			$survey_englishhtml = $templatedata[17];
			$survey_englishplain = $templatedata[18];
			$survey_spanishhtml = $templatedata[17]; // spanish is not used yet
			$survey_spanishplain = $templatedata[18];
			
			if (!createTemplate('survey', $survey_englishplain, $survey_englishhtml, $survey_spanishplain, $survey_spanishhtml))
				return false;
			
			$monitor_englishhtml = $templatedata[19];
			$monitor_englishplain = $templatedata[20];
			
			$messagegroupid = createTemplate('monitor', $monitor_englishplain, $monitor_englishhtml, $monitor_englishplain, $monitor_englishhtml);
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

function createTemplate($templatetype, $englishplain, $englishhtml, $spanishplain, $spanishhtml) {
	$messagegroup = new MessageGroup();
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
	$message = new Message();
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
	$messagepart = new MessagePart();
	$messagepart->messageid = $message->id;
	$messagepart->type = "T";
	$messagepart->txt = $englishplain;
	$messagepart->sequence = 0;
	if (!$messagepart->create())
		return false;
		
	//// English HTML
	// create message
	$message = new Message();
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
	$messagepart = new MessagePart();
	$messagepart->messageid = $message->id;
	$messagepart->type = "T";
	$messagepart->txt = $englishhtml;
	$messagepart->sequence = 0;
	if (!$messagepart->create())
		return false;
	
	//// Spanish Plain
	// create message
	$message = new Message();
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
	$messagepart = new MessagePart();
	$messagepart->messageid = $message->id;
	$messagepart->type = "T";
	$messagepart->txt = $spanishplain;
	$messagepart->sequence = 0;
	if (!$messagepart->create())
		return false;
			
	//// Spanish HTML
	// create message
	$message = new Message();
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
	$messagepart = new MessagePart();
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