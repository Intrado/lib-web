<?
// NOTE assumes global $_dbcon already connected to customer database

function createDefaultTemplates($useSmsMessagelinkInboundnumber = false) {
	
	$templates = loadTemplateData($useSmsMessagelinkInboundnumber);
			
			////////////////////////
			// general notification
			$notification_englishhtml = $templates['notification']['en']['html']['body'];
			$notification_englishplain = $templates['notification']['en']['plain']['body'];
			$notification_spanishhtml = $templates['notification']['es']['html']['body'];
			$notification_spanishplain = $templates['notification']['es']['plain']['body'];
	
			if (!createTemplate('notification', $notification_englishplain, $notification_englishhtml, $notification_spanishplain, $notification_spanishhtml))
				return false;

			////////////////////////
			// emergency notification
			$notification_englishhtml = $templates['emergency']['en']['html']['body'];
			$notification_englishplain = $templates['emergency']['en']['plain']['body'];
			$notification_spanishhtml = $templates['emergency']['es']['html']['body'];
			$notification_spanishplain = $templates['emergency']['es']['plain']['body'];
			
			if (!createTemplate('emergency', $notification_englishplain, $notification_englishhtml, $notification_spanishplain, $notification_spanishhtml))
				return false;
				
			///////////////////////
			// messagelink
			$messagelink_englishhtml = $templates['messagelink']['en']['html']['body'];
			$messagelink_englishplain = $templates['messagelink']['en']['plain']['body'];
			$messagelink_spanishhtml = $templates['messagelink']['es']['html']['body'];
			$messagelink_spanishplain = $templates['messagelink']['es']['plain']['body'];
			
			$messagegroupid = createTemplate('messagelink', $messagelink_englishplain, $messagelink_englishhtml, $messagelink_spanishplain, $messagelink_spanishhtml);
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
			$messagepart->txt = $templates['messagelink']['en']['sms']['body'];
			$messagepart->sequence = 0;
			if (!$messagepart->create())
				return false;
			
			
			////////////////////////
			// subscriber
			$subscriber_englishhtml = $templates['subscriber-accountexpire']['en']['html']['body'];
			$subscriber_englishplain = $templates['subscriber-accountexpire']['en']['plain']['body'];
			$subscriber_spanishhtml = $templates['subscriber-accountexpire']['es']['html']['body'];
			$subscriber_spanishplain = $templates['subscriber-accountexpire']['es']['plain']['body'];
			
			$messagegroupid = createTemplate('subscriber-accountexpire', $subscriber_englishplain, $subscriber_englishhtml, $subscriber_spanishplain, $subscriber_spanishhtml);
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
				
			////////////////////////
			// survey
			$survey_englishhtml = $templates['survey']['en']['html']['body'];
			$survey_englishplain = $templates['survey']['en']['plain']['body'];
			$survey_spanishhtml = $templates['survey']['en']['html']['body']; // spanish is not used yet
			$survey_spanishplain = $templates['survey']['en']['plain']['body'];
			
			if (!createTemplate('survey', $survey_englishplain, $survey_englishhtml, $survey_spanishplain, $survey_spanishhtml))
				return false;
			
			////////////////////////
			// monitor
			$monitor_englishhtml = $templates['monitor']['en']['html']['body'];
			$monitor_englishplain = $templates['monitor']['en']['plain']['body'];
			
			$messagegroupid = createTemplate('monitor', $monitor_englishplain, $monitor_englishhtml, $monitor_englishplain, $monitor_englishhtml);
			if (!$messagegroupid)
				return false;
			
			// set english and spanish headers, they are the same
			$data = "subject=" . urlencode($templates['monitor']['en']['html']['subject']) .
								"&fromname=" . urlencode($templates['monitor']['en']['html']['fromname']) . 
								"&fromemail=" . urlencode($templates['monitor']['en']['html']['fromaddr']);
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