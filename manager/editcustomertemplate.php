<?
require_once("common.inc.php");
require_once("../inc/form.inc.php");
require_once("../inc/html.inc.php");
require_once("../inc/table.inc.php");
require_once("../inc/utils.inc.php");
require_once("../inc/themes.inc.php");
//require_once("../obj/Template.obj.php");
require_once("../obj/MessageGroup.obj.php");
require_once("../obj/Message.obj.php");
require_once("../obj/MessagePart.obj.php");
require_once("../obj/FieldMap.obj.php");
require_once("../obj/Voice.obj.php");
require_once("../obj/Language.obj.php");


if (!$MANAGERUSER->authorized("editcustomer"))
	exit("Not Authorized");

if (!isset($_GET['cid']))
	exit("Missing customer id");

if (!isset($_GET['id']))
	exit("Missing messagegroup id");
$messagegroupid = $_GET['id'] + 0;

$currentid = $_GET['cid'] + 0;
$custinfo = QuickQueryRow("select s.dbhost, c.dbusername, c.dbpassword, c.urlcomponent from customer c inner join shard s on (c.shardid = s.id) where c.id = '$currentid'");
$custdb = DBConnect($custinfo[0], $custinfo[1], $custinfo[2], "c_$currentid");
if (!$custdb) {
	exit("Connection failed for customer: $custinfo[0], db: c_$currentid");
}
$_dbcon = $custdb; // set global database connection

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

$templatetype = QuickQuery("select type from template where messagegroupid = ?", $custdb, array($messagegroupid));

// find all html email messages, per language - read headers used for subject
$messagesbylangcode = array();
$messagegroup = DBFind("MessageGroup", "from messagegroup where id = ?", false, array($messagegroupid));
if ($messagegroup) {
	$messages = DBFindMany("Message", "from message where messagegroupid = ? and type = 'email' and subtype = 'html'", false, array($messagegroupid));
	if ($messages) {
		foreach ($messages as $id => $message) {
			$messagesbylangcode[$message->languagecode] = $message;
			$messagesbylangcode[$message->languagecode]->readHeaders();
		}
	}
}

// some types require subject/fromname/fromaddr
if ($templatetype == "messagelink") {
	$showheaders = true;
	// there should be only one message
	$smsmessage = DBFind("Message", "from message where messagegroupid = ? and type = 'sms' and languagecode = 'en'", false, array($messagegroupid));
	// only one part
	$smsbody = QuickQuery("select txt from messagepart where messageid = ?", false, array($smsmessage->id));
} else if ($templatetype == "subscriber") {
	$showheaders = true;
} else {
	$showheaders = false;
}

// get the customer default language data
$defaultcode = Language::getDefaultLanguageCode();
$defaultlanguage = Language::getName(Language::getDefaultLanguageCode());
$languagemap = Language::getLanguageMap();
// default message from default language
$defaultmessage = $messagegroup->getMessage("email", "html", $defaultcode, "none");
$defaultmessage->readHeaders();

// read from default, but write to all in group
$fromname = $defaultmessage->fromname;
$fromemail = $defaultmessage->fromemail;

$languagedata = array();
// sorted by language name
foreach (array_keys($languagemap) as $langcode) {
	$languagedata[$langcode]['name'] = $languagemap[$langcode];
	if (isset($messagesbylangcode[$langcode]))
		$languagedata[$langcode]['subject'] = $messagesbylangcode[$langcode]->subject;
	else
		$languagedata[$langcode]['subject'] = "";
	$languagedata[$langcode]['plain'] = $messagegroup->getMessageText("email", "plain", $langcode, "none");
	$languagedata[$langcode]['html'] = $messagegroup->getMessageText("email", "html", $langcode, "none");
}



$f = "customertemplate";
$s = "edit";
$reloadform = 0;

if (CheckFormSubmit($f, "Save")) {
	if (CheckFormInvalid($f)) {
		error('Form was edited in another window, reloading data');
		$reloadform = 1;
	} else {
		MergeSectionFormData($f, $s);

		if ( CheckFormSection($f, $s) ) {
			error('There was a problem trying to save your changes', 'Please verify that all required field information has been entered properly');
		} else {
			Query("BEGIN");
			$haserror = false;
			// we only support sms in english
			if (isset($smsmessage)) {
				$smsbody = trim(GetFormData($f, $s, "sms_en"));
				if (!strstr($smsbody, "\${messagelink}")) {
					error('Template must contain "${messagelink}" variable. SMS');
					$haserror = true;
				}
				// check length, assume max for variables
				$smsbodylength = strlen($smsbody);
				if (strstr($smsbody, "\${messagelink}") != false) {
					$smsbodylength -= 14; // variable text
					// TODO what max should we use? customerurl can be very long, asp.schoolmessenger.com may change, etc
					$smsbodylength += 50; // max length of value
				}
				if (strstr($smsbody, "\${displayname}") != false) {
					$smsbodylength -= 14; // variable text
					$smsbodylength += 50; // max length of value
				}
				if (strstr($smsbody, "\${inboundnumber}") != false) {
					$smsbodylength -= 16; // variable text
					$smsbodylength += 13; // max length of value
				}
				// US max 160 chars (ugh, do we care about Canada and 132 max?)
				if ($smsbodylength > 160) {
					error('Template exceeds 160 character limit, when variables filled with maximum lengths. SMS');
					$haserror = true;
				}
			}
			foreach (array_keys($languagemap) as $langcode) {
				// survey only supports English, all others in various languages
				if ($templatetype == "survey" && $langcode != "en")
					continue;
				
				if ($haserror)
					break; // exit loop
					
				foreach (array('plain', 'html') as $subtype) {
					
					// do not trim the body, maybe they want to start indented or something
					$body = GetFormData($f, $s, $subtype . "_" . $langcode);
					
					// English always required, optional for other languages - if spanish is blank then no template
					if (strlen(trim($body)) == 0) {
						if ($langcode == "en") {
							error('Template cannot be blank. ' . $subtype . ' ' . $langcode);
							$haserror = true;
						}
					} else {
						// verify required fields in template, based on type
						switch ($templatetype) {
							case "notification":
							case "emergency":
								if (!strstr($body, "\${body}")) {
									error('Template must contain "${body}" variable. ' . $subtype . ' ' . $langcode);
									$haserror = true;
								}
								break;
							case "messagelink":
								if (!strstr($body, "\${messagelink}")) {
									error('Template must contain "${messagelink}" variable. ' . $subtype . ' ' . $langcode);
									$haserror = true;
								}
								break;
						}
					}
					if ($haserror)
						break; // exit loop
					
					// if the message is already associated, reuse it. otherwise create a new one
					$message = $messagegroup->getMessage("email", $subtype, $langcode, "none");
					if ($message == null) {
						$message = new Message();
						$message->userid = $defaultmessage->userid;
						$message->messagegroupid = $defaultmessage->messagegroupid;
						$message->name = $defaultmessage->name;
						$message->description = $languagemap[$langcode] . " " . ucfirst($subtype);
						$message->type = "email";
						$message->subtype = $subtype;
						$message->languagecode = $langcode;
						$message->autotranslate = "none";
					}
					$message->recreateParts($body, null, null);
					if ($showheaders) {
						$message->fromname = GetFormData($f, $s, "fromname");
						$message->fromemail = GetFormData($f, $s, "fromemail");
						$message->subject = GetFormData($f, $s, "subject_" . $langcode);
						$message->stuffHeaders();
					}
					$message->modifydate = date("Y-m-d H:i:s");
					
					if ($message->id) {
						$message->update();
					} else {
						$message->create();
					}
					// TODO should delete languages that have empty body
				}
			}
			// single sms message for messagelink
			if (isset($smsmessage)) {
				$message = $messagegroup->getMessage("sms", "plain", "en", "none");
				$message->recreateParts($smsbody, null, null);
				$message->modifydate = date("Y-m-d H:i:s");
				if ($message->id) {
					$message->update();
				} else {
					$message->create();
				}
			}
			
			if (!$haserror) {
				Query("COMMIT");
			
				redirect("customertemplates.php?cid=" . $currentid);
			} else {
				Query("ROLLBACK");
			}
		}
	}
} else {
	$reloadform = 1;
}

if ($reloadform) {
	ClearFormData($f);

	if ($showheaders) {
		PutFormData($f, $s, 'fromname', $fromname, "text", 1, 50, true);
		PutFormData($f, $s, 'fromemail', $fromemail, "email", 1, 255, true);
	}
	if (isset($smsmessage)) {
		PutFormData($f, $s, 'sms_en', $smsbody, "text", 1, 160, true);
	}
	
	foreach ($languagedata as $langcode => $data) {
		if ($showheaders) {
			PutFormData($f, $s, 'subject_' . $langcode, $data['subject'], "text", 1, 255, false);
		}
		PutFormData($f, $s, 'plain_' . $langcode, $data['plain'], "text", "nomin", "nomax", false);
		PutFormData($f, $s, 'html_' . $langcode, $data['html'], "text", "nomin", "nomax", false);
	}
}


////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

include_once("nav.inc.php");
NewForm($f);
?>
<h3>Edit Email Template (<?= $templatetype?>) for Customer: <?= $custinfo[3]?></h3>
<br />
<?
if ($showheaders) {
?>
<table>
	<tr>
		<td>From Name:</td>
		<td><? NewFormItem($f, $s, "fromname", "text", 40); ?></td>
	</tr>
	<tr>
		<td>From Email:</td>
		<td><? NewFormItem($f, $s, "fromemail", "text", 40); ?></td>
	</tr>
<?
	// just so happens that smsmessage is always within showheaders
	if (isset($smsmessage)) {
?>
	<tr>
		<td>SMS English:</td>
		<td><? NewFormItem($f, $s, "sms_en", "textarea", 55); ?></td>
	</tr>
<?
	} 
?>
</table>
<?
}
?>
<h3>--------------------------------------------------------------------------</h3>
<h3><?= $defaultlanguage?></h3>
<table>
<?
if ($showheaders) {
?>
	<tr>
		<td>Subject:</td>
		<td><? NewFormItem($f, $s, "subject_" . $defaultcode, "text", 40); ?></td>
	</tr>
<?
}
?>
	<tr>
		<td>HTML:</td>
		<td><? NewFormItem($f, $s, "html_" . $defaultcode, "textarea", 120); ?></td>
	</tr>
	<tr>
		<td>Plain:</td>
		<td><? NewFormItem($f, $s, "plain_" . $defaultcode, "textarea", 120); ?></td>
	</tr>
</table>
<?
// survey only supports English, all others in various languages
if ($templatetype != "survey")
foreach ($languagedata as $langcode => $data) {
	if ($langcode != $defaultcode) {
?>
<h3>--------------------------------------------------------------------------</h3>
<h3><?= $data['name']?></h3>
<table>
<?
if ($showheaders) {
?>
	<tr>
		<td>Subject:</td>
		<td><? NewFormItem($f, $s, "subject_" . $langcode, "text", 40); ?></td>
	</tr>
<?
}
?>
	<tr>
		<td>HTML:</td>
		<td><? NewFormItem($f, $s, "html_" . $langcode, "textarea", 120); ?></td>
	</tr>
	<tr>
		<td>Plain:</td>
		<td><? NewFormItem($f, $s, "plain_" . $langcode, "textarea", 120); ?></td>
	</tr>
</table>

<?		
	}	
}
NewFormItem($f, "Save", "Save", 'submit');
?>

<br />
<?
EndForm();
include_once("navbottom.inc.php");
?>
