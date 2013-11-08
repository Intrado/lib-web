<?
//some db objects here, used to create message templates
require_once("taiupgrades/db_1-5_oldcode.php");
require_once("../manager/loadtaitemplatedata.php");

function tai_upgrade_1_5 ($rev, $shardid, $customerid, $db) {
	global $authdb;

	switch($rev) {
		default:
		case 0:
			echo "|";
			apply_sql("taiupgrades/db_1-5_pre.sql", $customerid, $db, 1);
		case 1:
			echo "|";
			apply_sql("taiupgrades/db_1-5_pre.sql", $customerid, $db, 2);
		case 2:
			echo "|";
			apply_sql("taiupgrades/db_1-5_pre.sql", $customerid, $db, 3);
			createTemplates_1_5_3();
		case 3:
			echo "|";
			apply_sql("taiupgrades/db_1-5_pre.sql", $customerid, $db, 4);
		case 4:
			echo "|";
			apply_sql("taiupgrades/db_1-5_pre.sql", $customerid, $db, 5);
		case 5:
			echo "|";
			apply_sql("taiupgrades/db_1-5_pre.sql", $customerid, $db, 6);

	}
	
	return true;
}

function createTemplates_1_5_3() {
	$templates = loadTaiTemplateData();

	// query existing templates,
	$existingTemplateTypes = QuickQueryList("select type, type from template", true);

	foreach ($templates as $template => $templateData) {
		if (!isset($existingTemplateTypes[$template])) {
			$mgid = createTemplateMessages_1_5_3($template, $templateData);
			if (!$mgid)
				return false;
			// create template record
			if (!QuickUpdate("insert into template (type, messagegroupid) values (?, ?)", null, array($template, $mgid)))
				return false;
		}
	}
}

function createTemplateMessages_1_5_3($template, $messages) {
	$messagegroup = new MessageGroup_1_5_3();
	// create messagegroup
	$messagegroup->userid = null;
	$messagegroup->type = "systemtemplate";
	$messagegroup->name = $template . " Template";
	$messagegroup->description = "";
	$messagegroup->modified = date('Y-m-d H:i:s');
	$messagegroup->permanent = 1;
	$messagegroup->deleted = 0;
	$messagegroup->defaultlanguagecode = "en";
	if (!$messagegroup->create())
		return false;

	// #################################################################
	// create a message for each type/subtype/languagecode
	foreach ($messages as $type => $msgdata) {
		// for each subtype
		foreach ($msgdata as $subtype => $msglang) {
			// for each language code
			foreach ($msglang as $langcode => $data) {
				if ($data["body"]) {
					$message = new Message_1_5_3();
					$message->messagegroupid = $messagegroup->id;
					$message->type = $type;
					$message->subtype = $subtype;
					$message->autotranslate = "none";
					$message->name = $messagegroup->name;
					$message->description = "Template: $template for language code: $langcode";
					$message->userid = null;
					$message->languagecode = $langcode;
					if ($type == "email") {
						$message->subject = $data["subject"];
						$message->fromname = $data["fromname"];
						$message->fromemail = $data["fromaddr"];
						$message->stuffHeaders();
					}
					if (!$message->create())
						return false;

					$messagepart = new MessagePart_1_5_3();
					$messagepart->messageid = $message->id;
					$messagepart->type = "T";
					$messagepart->txt = $data['body'];
					$messagepart->sequence = 0;
					if (!$messagepart->create())
						return false;

				} // end if this message has a body
			} // for each language code
		} // for each subtype
	} // for each type

	return $messagegroup->id;
}
?>