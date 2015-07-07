<?

require_once("inc/common.inc.php");
require_once("inc/securityhelper.inc.php");
require_once("inc/table.inc.php");
require_once("inc/html.inc.php");
require_once("inc/utils.inc.php");
require_once("obj/Validator.obj.php");
require_once("obj/Form.obj.php");
require_once("obj/FormItem.obj.php");
require_once("obj/MessageGroup.obj.php");
require_once("obj/MessageAttachment.obj.php");
require_once("obj/ContentAttachment.obj.php");
require_once("obj/BurstAttachment.obj.php");
require_once("obj/Language.obj.php");
require_once("obj/ValDuplicateNameCheck.val.php");
require_once("obj/Message.obj.php");
require_once("obj/Voice.obj.php");


require_once("obj/MessagePart.obj.php");
require_once("obj/FieldMap.obj.php");
require_once("inc/previewfields.inc.php");
require_once("obj/PreviewModal.obj.php");
require_once("inc/appserver.inc.php");

///////////////////////////////////////////////////////////////////////////////
// Authorization:/
//////////////////////////////////////////////////////////////////////////////
$cansendphone = $USER->authorize('sendphone');
$cansendemail = $USER->authorize('sendemail');
$cansendsms = getSystemSetting('_hassms', false) && $USER->authorize('sendsms');

// Only kick the user out if he does not have permission to create any message at all (neither phone, email, nor sms).
// TODO: what about post only?
if (!$cansendphone && !$cansendemail && !$cansendsms) {
	if (isset($_REQUEST['api'])) {
		header("HTTP/1.1 403 Forbidden");
		exit();
	}

	redirect('unauthorized.php');
} 

if (isset($_GET['id'])) {
	if ($_GET['id'] !== "new" && !userOwns("messagegroup",$_GET['id'])) {
		if (isset($_REQUEST['api'])) {
			header("HTTP/1.1 403 Forbidden");
			exit();
		}

		redirect('unauthorized.php');
	}

	setCurrentMessageGroup($_GET['id']);
	
	if (isset($_GET["redirect"])) {
		$messagegroup = new MessageGroup(getCurrentMessageGroup());
		if($messagegroup->type != 'notification') {
			unset($_SESSION['messagegroupid']);
			redirect('unauthorized.php');
		}
		
		switch($_GET["redirect"]) {
			case "phone":
				$message = $messagegroup->getMessage("phone", "voice", $messagegroup->defaultlanguagecode);
				if ($USER->authorize('sendphone') && $message)
					redirect("editmessagephone.php?id=" . $message->id);
				break;
			case "sms":
				$message = $messagegroup->getMessage("sms", "plain", "en");
				if (getSystemSetting('_hassms', false) && $USER->authorize('sendsms') && $message)
					redirect("editmessagesms.php?id=" . $message->id);
				break;
			case "email":
				$message = $messagegroup->getMessage("email", "html", $messagegroup->defaultlanguagecode);
				if ($USER->authorize('sendemail') && $message)
					redirect("editmessageemail.php?id=" . $message->id);
				$message = $messagegroup->getMessage("email", "plain", $messagegroup->defaultlanguagecode);
				if ($message)
					redirect("editmessageemail.php?id=" . $message->id);
				break;
			case "facebook":
				$message = $messagegroup->getMessage("post", "facebook", "en");
				if (getSystemSetting('_hasfacebook', false) && $USER->authorize('facebookpost') && $message)
					redirect("editmessagefacebook.php?id=" . $message->id);
				break;
			case "twitter":
				$message = $messagegroup->getMessage("post", "twitter", "en");
				if (getSystemSetting('_hastwitter', false) && $USER->authorize('twitterpost') && $message)
					redirect("editmessagetwitter.php?id=" . $message->id);
				break;
			case "feed":
				$message = $messagegroup->getMessage("post", "feed", "en");
				if (getSystemSetting('_hasfeed', false) && $USER->authorize('feedpost'))
				redirect("editmessagefeed.php?id=" . $message->id);
					break;
			case "page":
				$message = $messagegroup->getMessage("post", "page", "en");
				if ((getSystemSetting('_hasfacebook', false) && $USER->authorize('facebookpost')) || (getSystemSetting('_hastwitter', false) && $USER->authorize('twitterpost')) &&
					$message)
					redirect("editmessagepage.php?id=" . $message->id);
				break;
			case "voice":
				$message = $messagegroup->getMessage("post", "voice", "en");
				if ((getSystemSetting('_hasfacebook', false) && $USER->authorize('facebookpost')) || (getSystemSetting('_hastwitter', false) && $USER->authorize('twitterpost')) &&
					$message)
					redirect("editmessagepostvoice.php?id=" . $message->id);
				break;
		}
	}

    // API clients don't support redirect/page-reload for caching session state.
    // For these clients, just continue with execution and don't redirect!
    //
    if (!isset($_GET['api'])) {
        redirect();
    }
}

$messagegroup = new MessageGroup(getCurrentMessageGroup());
if($USER->authorize('sendemail') && $messagegroup->type == 'stationery' && !$messagegroup->deleted) {
	$message = $messagegroup->getMessage("email", "html", $messagegroup->defaultlanguagecode);
	if ($message)
		redirect("editstationeryemail.php?id={$message->id}");
	else
		redirect("editstationeryemail.php?mgid={$messagegroup->id}&languagecode={$messagegroup->defaultlanguagecode}");
}
if($messagegroup->type != 'notification' || $messagegroup->deleted) {
	unset($_SESSION['messagegroupid']);

	if (isset($_REQUEST['api'])) {
		header("HTTP/1.1 403 Forbidden");
		exit();
	}

	redirect('unauthorized.php');
}


if (isset($_GET['delete'])) {
	$message = new Message($_GET['delete']);
	if ($message->messagegroupid == $messagegroup->id) {
		// Delete all messages and messageparts related to the delete request
		QuickUpdate("delete m.* ,mp.* 
						from message m,messagepart mp
						where 
						m.messagegroupid=? and m.languagecode = ? and 
						m.type =? and m.subtype=? and m.id = mp.messageid",
						false,
						array($messagegroup->id,$message->languagecode,$message->type,$message->subtype));
	}
	$messagegroup->updateDefaultLanguageCode();
}

if (isset($_GET['copyphonetovoice'])) {
	// does the phone (english) message have field inserts?
	$phonemessage = $messagegroup->getMessage("phone", "voice", Language::getDefaultLanguageCode());
	if ($phonemessage) {
		$parts = DBFindMany("MessagePart", "from messagepart where messageid=?", false, array($phonemessage->id));
		$hasfieldinsert = false;
		foreach ($parts as $part) {
			// is this a field insert?
			if ($part->type == "V") {
				$hasfieldinsert = true;
				break;
			}
		}
		if ($hasfieldinsert) {
			notice(_L("Operation failed. Cannot copy a phone message with field inserts."));
		} else {
			// remove existing post voice message
			$existingmessage = $messagegroup->getMessage("post", "voice", Language::getDefaultLanguageCode());
			if ($existingmessage) {
				Query("BEGIN");
				QuickUpdate("delete from message where id = ?", false, array($existingmessage->id));
				QuickUpdate("delete from messagepart where messageid = ?", false, array($existingmessage->id));
				Query("COMMIT");
			}
			// copy phone message
			$newmessage = $phonemessage->copy($messagegroup->id);
			$newmessage->type = "post";
			$newmessage->subtype = "voice";
			$newmessage->update();
			notice(_L("Copy of phone message to page media message completed successfully."));
		}
	} else {
		notice(_L("Operation failed. Phone message for language %s does not exist.", Language::getName(Language::getDefaultLanguageCode())));
	}
	redirect();
}

PreviewModal::HandleRequestWithId();

////////////////////////////////////////////////////////////////////////////////
// Form Data
////////////////////////////////////////////////////////////////////////////////

// find out if we need to update the default language code or if we need to give them an option to choose one
// if there are multiple languages, none of which are the system default language, present the user with a selection. Prepopulated with the current setting
$showDefaultLanguageSelector = false;
$invalidMessageWarning = false;
if ($messagegroup->id) {
	$currentlangs = $messagegroup->getMessageLanguages();
	if (!isset($currentlangs[Language::getDefaultLanguageCode()]) && count($currentlangs) > 1) {
		$showDefaultLanguageSelector = true;
	}
	
	// is this message group valid? if not, does it have any messages?
	$messages = $messagegroup->getMessages();
	if (!$messagegroup->isValid() && count($messages)) {
		if (
			($cansendphone && $messagegroup->hasDefaultMessage("phone", "voice")) ||
			($cansendemail && 
			$messagegroup->hasDefaultMessage("email", "html") || $messagegroup->hasDefaultMessage("email", "plain"))
			||
			($cansendsms && $messagegroup->hasDefaultMessage("sms", "plain"))
			) {
			$invalidMessageWarning = _L("You must include a message in the default language (%s) for every message type that you intend to send.",Language::getName($messagegroup->defaultlanguagecode));
		} else {
			// Add up possible destination to plain text
			$desinations = array();
			if ($cansendphone)
				$desinations[] = _L('Phone');
			if ($cansendemail)
				$desinations[] = _L('Email');
			if ($cansendsms)
				$desinations[] = _L('SMS');
			$desttext = implode(", ", $desinations);
			if($pos = strrpos($desttext,','))
				$desttext = substr_replace($desttext,_L(" or"),$pos,1);
			
			if ($messagegroup->defaultlanguagecode) {
				$invalidMessageWarning = _L("The message must contain a %s message in your default language, %s.",$desttext, Language::getName($messagegroup->defaultlanguagecode));	
			} else {
				$invalidMessageWarning = _L("The message must contain a %s message in your default language, %s.",$desttext);
			}
		}
	}
}

$helpsteps = array();
$formdata = array();
$helpstepnum = 1;
$helpsteps[] = _L("Enter a name for your Message. " .
					"Using a descriptive name that indicates the message content will make it easier to find the message later. " .
					"You may also optionally enter a description of the the message.");
$formdata["name"] = array(
	"label" => _L('Message Name'),
	"fieldhelp" => _L('Enter a name for your message.'),
	"value" => isset($messagegroup->name)?$messagegroup->name:"",
	"validators" => array(
		array("ValRequired"),
		array("ValDuplicateNameCheck","type" => "messagegroup"),
		array("ValLength","max" => 50)
	),
	"control" => array("TextField","size" => 30, "maxlength" => 50),
	"helpstep" => 1
);
$formdata["description"] = array(
	"label" => _L('Description'),
	"fieldhelp" => _L('Enter a description of the message. This is optional, but can help identify the message later.'),
	"value" => isset($messagegroup->description)?$messagegroup->description:"",
	"validators" => array(
		array("ValLength","min" => 0,"max" => 50)
	),
	"control" => array("TextField","size" => 30, "maxlength" => 50),
	"helpstep" => $helpstepnum++
);

if ($showDefaultLanguageSelector) {
	$formdata["defaultlanguage"] = array(
		"label" => _L('Select Default Language'),
		"fieldhelp" => _L('Choose the language to use as the default.'),
		"value" => $messagegroup->defaultlanguagecode,
		"validators" => array(
			array("ValRequired"),
			array("ValInArray","values" => array_keys($currentlangs))
		),
		"control" => array("SelectMenu", "values" => array_merge(array("" => _L("- Select One -")), $currentlangs)),
		"helpstep" => $helpstepnum++
	);
	$helpsteps[] = _L("Select the default message language.");
}
if ($invalidMessageWarning) {
	$formdata["invalidmessagetip"] = array(
		"label" => _L('Message Is Currently Invalid'),
		"control" => array("FormHtml", "html" => '<div style="border: 2px solid red;padding: 4px;">'. $invalidMessageWarning .'</div>'),
		"helpstep" => $helpstepnum++
	);
	$helpsteps[] = _L("The default language for your message is missing a component which has been included in an alternate language. You must ensure that there is a version available for your default language for your receipients who do not receive messages in an alternate language.");
}

if ($messagegroup->id) {
	$buttons = array(submit_button(_L('Save'),"submit","tick"));
} else {
	$buttons = array(submit_button(_L('Next'),"submit","arrow_right"));
}

$form = new Form("messagegroupedit",$formdata,$helpsteps,$buttons);

////////////////////////////////////////////////////////////////////////////////
// Form Data Handling
////////////////////////////////////////////////////////////////////////////////

//check and handle an ajax request (will exit early)
//or merge in related post data
$form->handleRequest();
//check for form submission
if ($button = $form->getSubmit()) { //checks for submit and merges in post data
	$ajax = $form->isAjaxSubmit(); //whether or not this requires an ajax response

	if ($form->checkForDataChange()) {
		$datachange = true;
	} else if (($errors = $form->validate()) === false) { //checks all of the items in this form
		$postdata = $form->getData(); //gets assoc array of all values {name:value,...}
		Query("BEGIN");
		
		if (isset($postdata['defaultlanguage']))
			$messagegroup->defaultlanguagecode = $postdata['defaultlanguage'];
		
		$messagegroup->name = removeIllegalXmlChars($postdata['name']);
		$messagegroup->description = $postdata['description'];
		$messagegroup->userid = $USER->id;
		$messagegroup->modified = date("Y-m-d H:i:s", time());
		
		// messages created via the message group editor are always permanent
		$messagegroup->permanent = 1;
		
		$messagegroup->update();
		Query("COMMIT");
		$_SESSION['messagegroupid'] = $messagegroup->id;

		if ($ajax)
			$form->sendTo("mgeditor.php", Array("messageGroup" => Array("id" => (int)$messagegroup->id)));
		else
			redirect("mgeditor.php");
	}
}
////////////////////////////////////////////////////////////////////////////////
// Display Functions
////////////////////////////////////////////////////////////////////////////////

/*
 * Creates a grid of status icons attached with menues containing action links
 */
function showActionGrid ($columnlabels, $rowlabels, $links) {
	
	// Top left cell is an empty cell
	echo "<table class='messagegrid'><tr><th></th>";
	
	// Create column labels
	foreach ($columnlabels as $label) {
		echo "<th class='messagegridheader'>$label</th>";
	}
	echo "</tr>";
	
	// Array containing javascript code for each icon to enable the action menues.
	$actionmenues = array();
	//$alt = 1;
	
	// Print status icons with row labels 
	for ($row = 0; $row < count($rowlabels); $row++) {
		
		//echo ++$alt % 2 ? '<tr>' : '<tr class="listAlt">';
		echo "<tr><th class='messagegridlanguage'>$rowlabels[$row]</th>";
		$rowlinks = $links[$row];
		for ($col = 0;$col < count($rowlinks);$col++) {
			$link = $rowlinks[$col];
			if ($link !== false) {
				if (isset($link['icon'])) {
					echo "<td>
							<div id='". $link["id"] ."' class='tinybutton'>
							<img src='img/icons/{$link["icon"]}.png'
								title=''
								alt='{$link["title"]}'
							/>
							</div>
						</td>";
					
					// Attach action menu script for this item
					if (isset($link["actions"])) {
						$actionmenues[] = "createactionmenu('". $link["id"] ."','" . action_links_vertical($link["actions"]) . "',
							'{$link["title"]}');";
					}
				} elseif (isset($link['button'])) {
					echo "<td>
							<div style='width:100%'>{$link['button']}</div>
						</td>";
				}
			} else {
				echo "<td>-</td>";
			}
		}
		echo "</tr>";
	}
	echo "</table>";
	
	// Print javascript for all status icons 
	echo "<script type='text/javascript' language='javascript'>";
	echo implode("\n",$actionmenues);
	echo "</script>";
}

/*
 * Creates a actiongrid for messagegroup messages according to customer languages and types available to the user
 */
function makeMessageGrid($messagegroup) {
	global $USER;
	
	if ($USER->authorize('sendmulti')) {
		$customerlanguages = Language::getLanguageMap();
		unset($customerlanguages["en"]);
		$customerlanguages = array_merge(array("en" => "English"),$customerlanguages);
	} else {
		$customerlanguages = array(Language::getDefaultLanguageCode() => Language::getName(Language::getDefaultLanguageCode()));
	}
	// Setup destination and wizard/editor buttons types according to permissions
	$columnlabels = array();
	$buttons = array();

	if ($USER->authorize('sendphone')) {
		$columnlabels[] = 'Phone <a href="messagewizardphone.php?new&mgid=' . $messagegroup->id . '"><img src="img/icons/add.png" alt="Add" title="Add New Phone Message" /></a>';
	}
	
	if (getSystemSetting('_hassms', false) && $USER->authorize('sendsms')) {
		$columnlabels[] = 'SMS <a href="editmessagesms.php?new&mgid=' . $messagegroup->id . '"><img src="img/icons/add.png" alt="Add" title="Add New SMS Message"/></a>';
	}
	
	if ($USER->authorize('sendemail')) {
		if (!$USER->authorize('forcestationery'))
			$columnlabels[] = 'HTML Email <a href="messagewizardemail.php?new&subtype=html&mgid=' . $messagegroup->id . '"><img src="img/icons/add.png" alt="Add" title="Add New HTML Email Message"/></a>';
		else {
			$columnlabels[] = 'HTML Email';
		}
		$columnlabels[] = 'Plain Email <a href="messagewizardemail.php?new&subtype=plain&mgid=' .$messagegroup->id . '"><img src="img/icons/add.png" alt="Add" title="Add New Plain Email Message" /></a>';
	}
	
	if (getSystemSetting('_hasfacebook', false) && $USER->authorize('facebookpost')) {
		$columnlabels[] = 'Facebook <a href="editmessagefacebook.php?new&mgid=' . $messagegroup->id . '"><img src="img/icons/add.png" alt="Add" title="Add New Facebook Message" /></a>';
	}
	
	if (getSystemSetting('_hastwitter', false) && $USER->authorize('twitterpost')) {
		$columnlabels[] = 'Twitter <a href="editmessagetwitter.php?new&mgid=' .$messagegroup->id . '"><img src="img/icons/add.png" alt="Add" title="Add New Twitter Message" /></a>';
	}
	
	if (getSystemSetting('_hasfeed', false) && $USER->authorize('feedpost')) {
		$columnlabels[] = 'Feed <a href="editmessagefeed.php?new&mgid=' .$messagegroup->id . '"><img src="img/icons/add.png" alt="Add" title="Add New Feed Message" /></a>';
	}
	
	// Page post items
	if ((getSystemSetting('_hasfacebook', false) && $USER->authorize('facebookpost')) || 
			(getSystemSetting('_hastwitter', false) && $USER->authorize('twitterpost')) || 
			(getSystemSetting('_hasfeed', false) && $USER->authorize('feedpost'))) {
		$columnlabels[] = 'Page <a href="editmessagepage.php?new&mgid=' .$messagegroup->id . '"><img src="img/icons/add.png" alt="Add" title="Add New Page Message" /></a>';
		$columnlabels[] = 'Page Media <a href="editmessagepostvoice.php?new&mgid=' .$messagegroup->id . '"><img src="img/icons/add.png" alt="Add" title="Add New Page Media Message" /></a>';
	}
	
	// set action usr link	
	$links = array(); 
	foreach ($customerlanguages as $languagecode => $languagename) {
		$linkrow = array();
		
		// Print phone message actions if phone is available 
		if ($USER->authorize('sendphone')) {
			$link =
			$message = $messagegroup->getMessage('phone', 'voice', $languagecode);
			$actions = array();
			if ($message) {
				$icon = "accept";
				$actions[] = action_link("Play","fugue/control",null,"showPreview(null,\'previewid=$message->id\');return false;");
				$actions[] = action_link("Record","diagona/16/151","editmessagerecord.php?id=new&languagecode=$languagecode&mgid=".$messagegroup->id);
				$actions[] = action_link("Edit","pencil","editmessagephone.php?id=$message->id");
				$actions[] = action_link("Delete","cross","mgeditor.php?delete=$message->id","return confirmDelete();");
			} else {
				$icon = "diagona/16/160";
				$actions[] = action_link("Record","diagona/16/151","editmessagerecord.php?id=new&languagecode=$languagecode&mgid=".$messagegroup->id);
				$actions[] = action_link("Write","pencil_add","editmessagephone.php?id=new&languagecode=$languagecode&mgid=".$messagegroup->id);
			}
			$linkrow[] = array('icon' => $icon,'title' => _L(" %s Phone Message",$languagename), 'actions' => $actions,
				'id' => "phone-voice-" . $languagecode);
		}
		
		// Print SMS message actions if SMS is available 
		if (getSystemSetting('_hassms', false) && $USER->authorize('sendsms')) {
			if ($languagecode == Language::getDefaultLanguageCode()) {
				$actions = array();
				$message = $messagegroup->getMessage('sms', 'plain', $languagecode);
				if ($message) {
					$icon = "accept";
					$actions[] = action_link("Preview","email_open",null,"showPreview(null,\'previewid=$message->id\');return false;");
					$actions[] = action_link("Edit","pencil","editmessagesms.php?id=$message->id");
					$actions[] = action_link("Delete","cross","mgeditor.php?delete=$message->id","return confirmDelete();");
				} else {
					$icon = "diagona/16/160";
					$actions[] = action_link("New","pencil_add","editmessagesms.php?id=new&mgid=$messagegroup->id");
				}
				$linkrow[] = array('icon' => $icon,'title' => _L("%s SMS Message",$languagename), 'actions' => $actions,
					'id' => "sms-plain-" . $languagecode);
			} else {
				$linkrow[] = false;
			}
		}

		// Print email message actions if email is available 
		if ($USER->authorize('sendemail')) {
			$actions = array();
			$message = $messagegroup->getMessage('email', 'html', $languagecode);
			
			if ($message) {
				$icon = "accept";
				$actions[] = action_link("Preview","email_open",null,"showPreview(null,\'previewid=$message->id\');return false;");
				$actions[] = action_link("Edit","pencil","editmessageemail.php?id=$message->id");
				$actions[] = action_link("Delete","cross","mgeditor.php?delete=$message->id","return confirmDelete();");
			} else {
				$icon = "diagona/16/160";
				if (!$USER->authorize('forcestationery'))
					$actions[] = action_link("New","pencil_add","editmessageemail.php?id=new&subtype=html&languagecode=$languagecode&mgid=".$messagegroup->id);
				$actions[] = action_link("New from stationery","pencil_add","mgstationeryselector.php?type=email&subtype=html&languagecode=$languagecode&mgid=".$messagegroup->id);
				
			}
			$linkrow[] = array('icon' => $icon,'title' => _L("%s HTML Email Message",$languagename), 'actions' => $actions,
				'id' => "email-html-" . $languagecode);

			$actions = array();
			$message = $messagegroup->getMessage('email', 'plain', $languagecode);
			if ($message) {
				$icon = "accept";
				$actions[] = action_link("Preview","email_open",null,"showPreview(null,\'previewid=$message->id\');return false;");
				$actions[] = action_link("Edit","pencil","editmessageemail.php?id=$message->id");
				$actions[] = action_link("Delete","cross","mgeditor.php?delete=$message->id","return confirmDelete();");
			} else {
				$icon = "diagona/16/160";
				$actions[] = action_link("New","pencil_add","editmessageemail.php?id=new&subtype=plain&languagecode=$languagecode&mgid=".$messagegroup->id);
			}
			$linkrow[] = array('icon' => $icon,'title' => _L("%s Plain Email Message",$languagename), 'actions' => $actions,
				'id' => "email-plain-" . $languagecode);
		}
		
		// Facebook actions
		if (getSystemSetting('_hasfacebook', false) && $USER->authorize('facebookpost')) {
			if ($languagecode == Language::getDefaultLanguageCode()) {
				$actions = array();
				$message = $messagegroup->getMessage('post', 'facebook', $languagecode);
				
				if ($message) {
					$icon = "accept";
					$actions[] = action_link("Preview","custom/facebook",null,"showPreview(null,\'previewid=$message->id\');return false;");
					$actions[] = action_link("Edit","pencil","editmessagefacebook.php?id=$message->id");
					$actions[] = action_link("Delete","cross","mgeditor.php?delete=$message->id","return confirmDelete();");
				} else {
					$icon = "diagona/16/160";
					$actions[] = action_link("New","pencil_add","editmessagefacebook.php?id=new&mgid=".$messagegroup->id);
				}
				$linkrow[] = array('icon' => $icon,'title' => _L("%s Facebook Message",$languagename), 'actions' => $actions,
					'id' => "post-facebook-" . $languagecode);
			} else {
				$linkrow[] = false;
			}
		}
		
		// Twitter actions
		if (getSystemSetting('_hastwitter', false) && $USER->authorize('twitterpost')) {
			if ($languagecode == Language::getDefaultLanguageCode()) {
				$actions = array();
				$message = $messagegroup->getMessage('post', 'twitter', $languagecode);
				
				if ($message) {
					$icon = "accept";
					$actions[] = action_link("Preview","custom/twitter",null,"showPreview(null,\'previewid=$message->id\');return false;");
					$actions[] = action_link("Edit","pencil","editmessagetwitter.php?id=$message->id");
					$actions[] = action_link("Delete","cross","mgeditor.php?delete=$message->id","return confirmDelete();");
				} else {
					$icon = "diagona/16/160";
					$actions[] = action_link("New","pencil_add","editmessagetwitter.php?id=new&mgid=".$messagegroup->id);
				}
				$linkrow[] = array('icon' => $icon,'title' => _L("%s Twitter Message",$languagename), 'actions' => $actions,
					'id' => "post-twitter-" . $languagecode);
			} else {
				$linkrow[] = false;
			}
		}
		
		// Feed actions
		if (getSystemSetting('_hasfeed', false) && $USER->authorize('feedpost')) {
			if ($languagecode == Language::getDefaultLanguageCode()) {
				$actions = array();
				$message = $messagegroup->getMessage('post', 'feed', $languagecode);
		
				if ($message) {
					$icon = "accept";
					$actions[] = action_link("Preview","rss",null,"showPreview(null,\'previewid=$message->id\');return false;");
					$actions[] = action_link("Edit","pencil","editmessagefeed.php?id=$message->id");
					$actions[] = action_link("Delete","cross","mgeditor.php?delete=$message->id","return confirmDelete();");
				} else {
					$icon = "diagona/16/160";
					$actions[] = action_link("New","pencil_add","editmessagefeed.php?id=new&mgid=".$messagegroup->id);
				}
				$linkrow[] = array('icon' => $icon,'title' => _L("%s Feed Message",$languagename), 'actions' => $actions,
					'id' => "post-feed-" . $languagecode);
			} else {
				$linkrow[] = false;
			}
		}
		
		// Page/Voice posting is allowed if twitter, facebook or feed are allowed, currently
		if ((getSystemSetting('_hasfacebook', false) && $USER->authorize('facebookpost')) ||
				(getSystemSetting('_hastwitter', false) && $USER->authorize('twitterpost')) ||
				(getSystemSetting('_hasfeed', false) && $USER->authorize('feedpost'))) {
			if ($languagecode == Language::getDefaultLanguageCode()) {
				$actions = array();
				$message = $messagegroup->getMessage('post', 'page', $languagecode);
				
				if ($message) {
					$icon = "accept";
					$actions[] = action_link("Preview","layout_sidebar",null,"showPreview(null,\'previewid=$message->id\');return false;");
					$actions[] = action_link("Edit","pencil","editmessagepage.php?id=$message->id");
					$actions[] = action_link("Delete","cross","mgeditor.php?delete=$message->id","return confirmDelete();");
				} else {
					$icon = "diagona/16/160";
					$actions[] = action_link("New","pencil_add","editmessagepage.php?id=new&mgid=".$messagegroup->id);
				}
				$linkrow[] = array('icon' => $icon,'title' => _L("%s Page Message",$languagename), 'actions' => $actions,
					'id' => "post-page-" . $languagecode);
				
				$actions = array();
				$message = $messagegroup->getMessage('post', 'voice', $languagecode);
				
				if ($message) {
					$icon = "accept";
					$actions[] = action_link("Preview","fugue/control",null,"showPreview(null,\'previewid=$message->id\');return false;");
					$actions[] = action_link("Edit","pencil","editmessagepostvoice.php?id=$message->id");
					$actions[] = action_link("Copy From Phone Message","page_copy","mgeditor.php?copyphonetovoice");
					$actions[] = action_link("Delete","cross","mgeditor.php?delete=$message->id","return confirmDelete();");
				} else {
					$icon = "diagona/16/160";
					$actions[] = action_link("New","pencil_add","editmessagepostvoice.php?id=new&mgid=".$messagegroup->id);
					$actions[] = action_link("Copy From Phone Message","page_copy","mgeditor.php?copyphonetovoice");
				}
				$linkrow[] = array('icon' => $icon,'title' => _L("%s Page Media Message",$languagename), 'actions' => $actions,
					'id' => "post-voice-" . $languagecode);
				
			} else {
				$linkrow[] = false;
				$linkrow[] = false;
			}
		}
		

		$links[] = $linkrow;
	}
	
	
	$rowlabels = array_values($customerlanguages);
	
	showActionGrid($columnlabels,$rowlabels,$links);
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$PAGE = "notifications:messages";
$TITLE = _L('Message Editor');

include_once("nav.inc.php");
?>
<script type="text/javascript">
<? Validator::load_validators(array("ValDuplicateNameCheck"));?>
function createactionmenu(id, content, title) {
	new Tip($(id), content, {
		title: title,
		style: 'protogrey',
		radius: 4,
		border: 4,
		showOn: 'click',
		hideOn: false,
		hideAfter: 0.5,
		stem: 'leftMiddle',
		hook: {  target: 'rightMiddle', tip: 'leftMiddle' },
		width: '220px'
	});
}

</script>
<script src="script/niftyplayer.js.php" type="text/javascript"></script>
<link href="css/messagegroup.css" type="text/css" rel="stylesheet">
<?
PreviewModal::includePreviewScript();


if ($messagegroup->id) {
	buttons(icon_button(_L('Done'),"tick",null,(isset($_SESSION['origin']) && ($_SESSION['origin'] == 'start')?"start.php":"messages.php")));
}
startWindow(_L('Message Settings'));
echo $form->render();
endWindow();
if ($messagegroup->id) {
	startWindow(_L('Message Content'));
	makeMessageGrid($messagegroup);
	endWindow();
}
if (isset($_GET['preview']) && $preview) {
	echo $preview->includeModal();
}

include_once("navbottom.inc.php");
?>