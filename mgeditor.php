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
require_once("obj/Language.obj.php");
require_once("obj/ValDuplicateNameCheck.val.php");
require_once("obj/Message.obj.php");
require_once("obj/Voice.obj.php");


require_once("obj/MessagePart.obj.php");
require_once("obj/FieldMap.obj.php");
require_once("inc/previewfields.inc.php");
require_once("obj/PreviewModal.obj.php");
require_once("inc/appserver.inc.php");

require_once('thrift/Thrift.php');
require_once $GLOBALS['THRIFT_ROOT'].'/protocol/TBinaryProtocol.php';
require_once $GLOBALS['THRIFT_ROOT'].'/transport/TSocket.php';
require_once $GLOBALS['THRIFT_ROOT'].'/transport/TBufferedTransport.php';
require_once $GLOBALS['THRIFT_ROOT'].'/transport/TFramedTransport.php';
require_once("inc/thrift.inc.php");
require_once $GLOBALS['THRIFT_ROOT'].'/packages/commsuite/CommSuite.php';

///////////////////////////////////////////////////////////////////////////////
// Authorization:/
//////////////////////////////////////////////////////////////////////////////
$cansendphone = $USER->authorize('sendphone');
$cansendemail = $USER->authorize('sendemail');
$cansendsms = getSystemSetting('_hassms', false) && $USER->authorize('sendsms');

// Only kick the user out if he does not have permission to create any message at all (neither phone, email, nor sms).
if (!$cansendphone && !$cansendemail && !$cansendsms) {
	redirect('unauthorized.php');
} 

if (isset($_GET['id'])) {
	if ($_GET['id'] !== "new" && !userOwns("messagegroup",$_GET['id']))
		redirect('unauthorized.php');
	setCurrentMessageGroup($_GET['id']);
	redirect();
}

$messagegroup = new MessageGroup(getCurrentMessageGroup());
if($messagegroup->type != 'notification') {
	unset($_SESSION['messagegroupid']);
	redirect('unauthorized.php');
}


if (isset($_GET['delete'])) {
	QuickUpdate("delete from message where id=? and messagegroupid=?",false,array($_GET['delete'],$messagegroup->id));
}

PreviewModal::HandlePhoneMessageId();

////////////////////////////////////////////////////////////////////////////////
// Form Data
////////////////////////////////////////////////////////////////////////////////

// find out if we need to update the default language code or if we need to give them an option to choose one
// if the messagegroup has any instance of phone or email for the system default language, set it to that. 
// if not, and there are only messages for one other language, set it to that.
// if there are multiple languages, none of which are the system default language, present the user with a selection. Prepopulated with the current setting
// if no languages at all, set to empty string
$showDefaultLanguageSelector = false;
$showInvalidMessageWarning = false;
if ($messagegroup->id) {
	$currentlangs = $messagegroup->getMessageLanguages();
	if (isset($currentlangs[Language::getDefaultLanguageCode()])) {
		$messagegroup->defaultlanguagecode = Language::getDefaultLanguageCode();
		$messagegroup->update();
	} else if (count($currentlangs) == 1) {
		foreach ($currentlangs as $langcode => $lang)
			$messagegroup->defaultlanguagecode = $langcode;
		$messagegroup->update();
	} else if (count($currentlangs) > 1) {
		$showDefaultLanguageSelector = true;
	} else {
		$messagegroup->defaultlanguagecode = "";
		$messagegroup->update();
	}
	
	// is this message group valid?
	if (!$messagegroup->isValid())
		$showInvalidMessageWarning = true;
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
if ($showInvalidMessageWarning) {
	$formdata["invalidmessagetip"] = array(
		"label" => _L('Message Is Currently Invalid'),
		"control" => array("FormHtml", "html" => '<div style="border: 2px solid red;padding: 4px;">'. _L("Your default language, %s, is missing either phone or email.", Language::getName($messagegroup->defaultlanguagecode)) .'</div>'),
		"helpstep" => $helpstepnum++
	);
	$helpsteps[] = _L("TODO: Help.");
}

if ($messagegroup->id) {
	$buttons = array(submit_button(_L('Save'),"submit","tick"));
} else {
	$buttons = array(submit_button(_L('Next'),"submit","tick"));
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
		
		$messagegroup->name = $postdata['name'];
		$messagegroup->description = $postdata['description'];
		$messagegroup->userid = $USER->id;
		$messagegroup->modified = date("Y-m-d H:i:s", time());
		$messagegroup->update();
		Query("COMMIT");
		$_SESSION['messagegroupid'] = $messagegroup->id;
		if ($ajax)
			$form->sendTo("mgeditor.php");
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
							<div id='gridmenu-$row-$col' class='tinybutton'>
							<img src='img/icons/{$link["icon"]}.png'
								title=''
								alt='{$link["title"]}'
							/>
							</div>
						</td>";
					
					// Attach action menu script for this item
					if (isset($link["actions"])) {
						$actionmenues[] = "createactionmenu('gridmenu-$row-$col','" . implode("<br />",$link["actions"]) . "','{$link["title"]}');";
					}
				} elseif (isset($link['button'])) {
					echo "<td>
							{$link['button']}
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
	// Setup destination types according to permissions
	$columnlabels = array();
	
	if ($USER->authorize('sendphone')) {
		$columnlabels[] = "Phone";
	}
	
	if (getSystemSetting('_hassms', false) && $USER->authorize('sendsms')) {
		$columnlabels[] = "SMS";
	}
	
	if ($USER->authorize('sendemail')) {
		$columnlabels[] = "Email (HTML)";
		$columnlabels[] = "Email (Text)";
	}
	
	if ($USER->authorize('facebookpost'))
		$columnlabels[] = "Facebook";
	
	if ($USER->authorize('twitterpost'))
		$columnlabels[] = "Twitter";
	
	if ($USER->authorize('twitterpost', 'facebookpost'))
		$columnlabels[] = "Page";
		
	// set action usr link	
	$links = array(); 
	$ttslanguages = Voice::getTTSLanguageMap();
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
				$actions[] = action_link("Re-Record","diagona/16/151","editmessagerecord.php?id=new&languagecode=$languagecode&mgid=".$messagegroup->id);
				if (isset($ttslanguages[$languagecode]))
					$actions[] = action_link("Edit Advanced","pencil","editmessagephone.php?id=$message->id");
				$actions[] = action_link("Delete","cross","mgeditor.php?delete=$message->id","return confirmDelete();");
			} else {
				$icon = "diagona/16/160";
				$actions[] = action_link("Record","diagona/16/151","editmessagerecord.php?id=new&languagecode=$languagecode&mgid=".$messagegroup->id);
				if (isset($ttslanguages[$languagecode]))
					$actions[] = action_link("New Advanced","pencil_add","editmessagephone.php?id=new&languagecode=$languagecode&mgid=".$messagegroup->id);
			}
			$linkrow[] = array('icon' => $icon,'title' => _L(" %s Phone Message",$languagename), 'actions' => $actions);
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
				$linkrow[] = array('icon' => $icon,'title' => _L("%s SMS Message",$languagename), 'actions' => $actions);
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
				$actions[] = action_link("New","pencil_add","editmessageemail.php?id=new&subtype=html&languagecode=$languagecode&mgid=".$messagegroup->id);
			}
			$linkrow[] = array('icon' => $icon,'title' => _L("%s HTML Email Message",$languagename), 'actions' => $actions);

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
			$linkrow[] = array('icon' => $icon,'title' => _L("%s Text Email Message",$languagename), 'actions' => $actions);
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
				$linkrow[] = array('icon' => $icon,'title' => _L("%s Facebook Message",$languagename), 'actions' => $actions);
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
				$linkrow[] = array('icon' => $icon,'title' => _L("%s Twitter Message",$languagename), 'actions' => $actions);
			} else {
				$linkrow[] = false;
			}
		}
		// Page posting is allowed if EITHER twitter or facebook are allowed, currently
		if ((getSystemSetting('_hasfacebook', false) && $USER->authorize('facebookpost')) ||
				(getSystemSetting('_hastwitter', false) && $USER->authorize('twitterpost'))) {
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
				$linkrow[] = array('icon' => $icon,'title' => _L("%s Page Message",$languagename), 'actions' => $actions);
			} else {
				$linkrow[] = false;
			}
		}
		$links[] = $linkrow;
	}
	
	// add wizard/editor buttons
	$buttons = array();
	if ($USER->authorize("sendphone"))
		$buttons[] = array("button" => icon_button("New Phone", "telephone", "", "messagewizardphone.php?new&mgid=".$messagegroup->id));
	if ($USER->authorize("sendsms"))
		$buttons[] = array("button" => icon_button("New SMS", "fugue/mobile_phone", "", "editmessagesms.php?new&mgid=".$messagegroup->id));
	if ($USER->authorize("sendemail")) {
		$buttons[] = array("button" => icon_button("New Html Email", "email", "", "messagewizardemail.php?new&subtype=html&mgid=".$messagegroup->id));
		$buttons[] = array("button" => icon_button("New Plain Email", "html", "", "messagewizardemail.php?new&subtype=plain&mgid=".$messagegroup->id));
	}
	if ($USER->authorize('facebookpost'))
		$buttons[] = array("button" => icon_button("New Facebook", "custom/facebook", "", "editmessagefacebook.php?new&mgid=".$messagegroup->id));
	if ($USER->authorize('twitterpost'))
		$buttons[] = array("button" => icon_button("New Twitter", "custom/twitter", "", "editmessagetwitter.php?new&mgid=".$messagegroup->id));
	if ($USER->authorize('twitterpost', 'facebookpost'))
		$buttons[] = array("button" => icon_button("New Page", "layout_sidebar", "", "editmessagepage.php?new&mgid=".$messagegroup->id));
		
	$links[] = $buttons;
	
	$rowlabels = array_values($customerlanguages);
	$rowlabels[] = "";
	
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
function createactionmenu(id, content,title) {
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
		width: '220px',
	});
}

</script>
<script src="script/livepipe/livepipe.js" type="text/javascript"></script>
<script src="script/livepipe/window.js" type="text/javascript"></script>
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