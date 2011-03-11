<?
/*
 * put required external objects in the db_7-8_oldcode.php file. DO NOT INCLUDE files from kona
 */

//some db objects here, used to create message templates
require_once("upgrades/db_7-8_oldcode.php");

function createTemplate($templatetype, $userid, $englishplain, $englishhtml, $spanishplain, $spanishhtml) {
	// create messagegroup
	$messagegroup = new MessageGroup_7_8_r2();
	$messagegroup->userid = $userid;
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
	$message->userid = $userid;
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
	$message->userid = $userid;
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
	$message->userid = $userid;
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
	$message->userid = $userid;
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
			
	QuickUpdate("delete from template where type = ?", null, array($templatetype));
	if (!QuickUpdate("insert into template (type, messagegroupid) values (?, ?)", null, array($templatetype, $messagegroup->id)))
		return false;
				
	// success			
	return $messagegroup->id;
}

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
			
			// set global to customer db, restore after this section
			global $_dbcon;
			$savedbcon = $_dbcon;
			$_dbcon = $db;
			
			// schoolmessenger user to own the template messages
			$schoolmessengeruserid = QuickQuery("select id from user where login = 'schoolmessenger'");
			
			// general notification
			$notification_englishplain = "\${body}\n\nTo stop receiving all email messages distributed through this system on behalf of \${displayname}, follow this link and confirm: \${unsubscribelink}\n";
			$notification_englishhtml = "\${body}<br><p>To stop receiving all email messages distributed through this system on behalf of \${displayname}, follow this link and confirm: <a href=\"\${unsubscribelink}\">Unsubscribe</a></p>";
			$notification_spanishplain = $notification_englishplain; // TODO
			$notification_spanishhtml = $notification_englishhtml;
			
			if (!createTemplate('notification', $schoolmessengeruserid, $notification_englishplain, $notification_englishhtml, $notification_spanishplain, $notification_spanishhtml))
				return false;

			// messagelink
			$messagelink_englishplain = "Important Message Regarding \${f01} \${f02}\n\n" .
				"A new message from \${displayname} regarding \${f01} \${f02} was sent to you using the \${productname} notification service.\n\n" .
				"Follow the link below to play the message.\n\n" .
				"\${messagelink}\n\n" .
				"Thank you,\n" .
				"\${displayname}\n\n" .
				"\${displayname} would like to continue connecting with you via email.  " .
				"If you prefer to be removed from our list, please contact \${displayname} directly.  " .
				"To stop receiving all email messages distributed through our " .
				"\${productname} service, follow this link: \${unsubscribelink}\n\n" .
				"\${productname} is a notification service used by the nation's leading school systems to connect with parents, students and staff through voice, SMS text and email messages.\n" .
				"\${logoclickurl}\n\n";

			$messagelink_englishhtml = "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" " .
					"\"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">" .
					"<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en\" lang=\"en\">" .
					"<head>" .
					"<meta http-equiv=\"Content-Type\" content=\"text/html; charset=iso-8859-1\" />" .
					"<title>\${displayname} sent a new message</title>" .
					"</head>" .
					"<body>" .
					"<div>" .
					"<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" style=\"background-color: rgb(248, 241, 233);\" width=\"100%\">" .
					"<tbody>" .
					"<tr>" .
					"<td align=\"left\" width=\"100%\">" .
					"<div class=\"custname\"><span style=\"font-size: 26px;\">" .
					"\${displayname}</span></div>" .
					"</td>" .
					"</tr>" .
					"</tbody>" .
					"</table>" .
					"</div>" .
					"<div style=\"width: 100%; height: 6px; background: none repeat scroll 0% 0% rgb(62, 105, 63); clear: both;\">	&nbsp;</div>" .
					"<div style=\"width: 100%; height: 2px; background: none repeat scroll 0% 0% rgb(180, 119, 39); clear: both; margin-bottom: 3px;\">	&nbsp;</div>" .
					"<br />Important Message Regarding \${f01} \${f02}" .
					"<br /><br />" .
					"A new message from " .
					"\${displayname} regarding " .
					"\${f01} \${f02} was sent to you using the " .
					"\${productname} notification service." .
					"<br /><br />Listen to the message by <a href=\"" .
					"\${messagelink}\">clicking here</a>.<br /><br />" .
					"Thank you,<br />" .
					"\${displayname}<br /><br />" .
					"\${displayname} would like to continue connecting with you via email.  If you prefer to be removed from our list, please contact " .
					"\${displayname} directly.  " .
					"To stop receiving all email messages distributed through our " .
					"\${productname} service, <a href=\"" .
					"\${unsubscribelink}\">click here to unsubscribe</a>.<br />" .
					"<br />" .
					"<div style=\"width: 100%; height: 6px; background: none repeat scroll 0% 0% rgb(62, 105, 63); clear: both;\">&nbsp;</div>" .
					"<div style=\"width: 100%; height: 2px; background: none repeat scroll 0% 0% rgb(180, 119, 39); clear: both; margin-bottom: 3px;\">	&nbsp;</div><div>" .
					"<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" style=\"background-color: rgb(248, 241, 233);\" width=\"100%\">" .
					"<tbody>" .
					"<tr>" .
					"<td>" .
					"<span style=\"font-size: 12px;\">" .
					"\${productname} is a notification service used by the nation's leading school systems to connect with parents, students and staff through voice, SMS text and email messages.  <br />" .
					"<a href=\"" .
					"\${logoclickurl}\">" .
					"\${logoclickurl}</a><br /><br />" .
					"</span>" .
					"</td>" .
					"</tr>" .
					"</tbody>" .
					"</table>" .
					"</div>" .
					"</body>" .
					"</html>";
			
			$messagelink_spanishplain = $messagelink_englishplain; // TODO
			$messagelink_spanishhtml = $messagelink_englishhtml;
			
			$messagegroupid = createTemplate('messagelink', $schoolmessengeruserid, $messagelink_englishplain, $messagelink_englishhtml, $messagelink_spanishplain, $messagelink_spanishhtml);
			if (!$messagegroupid)
				return false;
				
			// set english headers
			$data = "subject=" . urlencode("\${displayname} sent a new message") . 
					"&fromname=" . urlencode("\${productname}") . 
					"&fromemail=" . urlencode("contactme@schoolmessenger.com");
			QuickUpdate("update message set data = ? where messagegroupid = ? and type = 'email' and languagecode = 'en'", null, array($data, $messagegroupid));
			// set spanish headers
			// TODO spanish subject in $data
			QuickUpdate("update message set data = ? where messagegroupid = ? and type = 'email' and languagecode = 'es'", null, array($data, $messagegroupid));
			
			// restore global db connection
			$_dbcon = $savedbcon;
			
		case 3:
			echo "|";
			
			// set global to customer db, restore after this section
			global $_dbcon;
			$savedbcon = $_dbcon;
			$_dbcon = $db;
			
			// schoolmessenger user to own the template messages
			$schoolmessengeruserid = QuickQuery("select id from user where login = 'schoolmessenger'");
			
			// emergency notification
			$notification_englishplain = "\${body}\n\nTo stop receiving all email messages distributed through this system on behalf of \${displayname}, follow this link and confirm: \${unsubscribelink}\n";
			$notification_englishhtml = "\${body}<br><p>To stop receiving all email messages distributed through this system on behalf of \${displayname}, follow this link and confirm: <a href=\"\${unsubscribelink}\">Unsubscribe</a></p>";
			$notification_spanishplain = $notification_englishplain; // TODO
			$notification_spanishhtml = $notification_englishhtml;
			
			if (!createTemplate('emergency', $schoolmessengeruserid, $notification_englishplain, $notification_englishhtml, $notification_spanishplain, $notification_spanishhtml))
				return false;

			// survey
			$survey_englishplain = "\${body}\n\n\${surveylink}\n\nTo stop receiving all email messages distributed through this system on behalf of \${displayname}, follow this link and confirm: \${unsubscribelink}\n";
			$survey_englishhtml = "\${body}<br><br>\${surveylink}<br><p>To stop receiving all email messages distributed through this system on behalf of \${displayname}, follow this link and confirm: <a href=\"\${unsubscribelink}\">Unsubscribe</a></p>";
			$survey_spanishplain = $survey_englishplain; // TODO
			$survey_spanishhtml = $survey_englishhtml;
			
			if (!createTemplate('survey', $schoolmessengeruserid, $survey_englishplain, $survey_englishhtml, $survey_spanishplain, $survey_spanishhtml))
				return false;
		
			// restore global db connection
			$_dbcon = $savedbcon;
			
	}
	
	apply_sql("../db/update_SMAdmin_access.sql",$customerid,$db);
	
	return true;
}
			
?>
