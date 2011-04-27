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

////////////////////////////////////////////////////////////////////////////////
// Optional Form Items And Validators
////////////////////////////////////////////////////////////////////////////////

$helpsteps = array();
$formdata = array();

$helpsteps[] = _L("Enter a name for your Message. " .
					"Using a descriptive name that indicates the message content will make it easier to find the message later. " .
					"You may also optionally enter a description of the the message.");
$formdata[] = "Message Info";
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
	"helpstep" => 1
);


if ($messagegroup->id) {
	$buttons = array(submit_button(_L('Save'),"submit","tick"));
} else {
	$buttons = array(submit_button(_L('Next'),"submit","tick"));
	$buttons[] = icon_button(_L('Cancel'),"cross",null,(isset($_SESSION['origin']) && ($_SESSION['origin'] == 'start')?"start.php":"messages.php"));
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
		$messagegroup->name = $postdata['name'];
		$messagegroup->description = $postdata['description'];
		$messagegroup->defaultlanguagecode = Language::getDefaultLanguageCode();
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
	echo "<table><tr><th></th>";
	
	// Create column labels
	foreach ($columnlabels as $label) {
		echo "<th>$label</th>";
	}
	echo "</tr>";
	
	// Array containing javascript code for each icon to enable the action menues.
	$actionmenues = array();
	
	// Print status icons with row labels 
	for ($row = 0; $row < count($rowlabels); $row++) {
		echo "<tr><th>$rowlabels[$row]</th>";
		$rowlinks = $links[$row];
		for ($col = 0;$col < count($rowlinks);$col++) {
			$link = $rowlinks[$col];
			echo "<td class='StatusIcon'>
					<img class='StatusIcon' 
						id='gridmenu-$row-$col'src='img/icons/{$link->icon}.gif'
						title='{$link->title}'
						alt='{$link->title}'
					/>
				</td>";
			
			// Attach action menu script for this item
			if (isset($link->actions)) {
				$actionmenues[] = "createactionmenu('gridmenu-$row-$col','" . implode("<br />",$link->actions) . "');";
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
	
	// Get Default language
	$deflanguagecode = Language::getDefaultLanguageCode();
	$deflanguage = array($deflanguagecode => Language::getName($deflanguagecode));
	$systemdefaultlanguagecode = Language::getDefaultLanguageCode();
	
	$cansendmultilingual = $USER->authorize('sendmulti');
	$customerlanguages = $cansendmultilingual ? Language::getLanguageMap() : $deflanguage;
	
	// Setup destination types according to permissions
	$destinations = array();
	$cansendphone = $USER->authorize('sendphone');
	if ($cansendphone) {
		$destinations["phone_voice"] = 'Phone';
	}
	
	$cansendsms = getSystemSetting('_hassms', false) && $USER->authorize('sendsms');
	if ($cansendsms) {
		$destinations["sms_plain"] = 'SMS';
	}
	
	$cansendemail = $USER->authorize('sendemail');
	if ($cansendemail) {
		$destinations["email_plain"] = 'Email/Text';
		$destinations["email_html"] = 'Email/HMTL';
	}
	
	// set action usr link	
	$links = array(); 
	foreach ($customerlanguages as $languagecode => $languagename) {
		$linkrows = array();
		$rowlabels[] = $languagename;
		
		foreach ($destinations as $key => $destination) {
			list($type,$subtype) = split("_", $key);
			$link = null;
			
			// Only attach action menues to applicable icons
			$canhavemessage = $type != 'sms' || $languagecode == $systemdefaultlanguagecode;
			if ($canhavemessage) {
				$message = $messagegroup->getMessage($type, $subtype, $languagecode);
				$actions = array();
				
				// Set menues for icons with and without message
				if ($message) {
					$link->icon = "accept";
					
					// Phone message will need a more custom menu
					if ($type == "phone") {
						$actions[] = action_link("Play","fugue/control",null,"preview(\'$type\',\'$subtype\',\'$languagecode\'); return false;");
						$actions[] = action_link("Re-Record","diagona/16/151","recordmessage.php?id=$message->id");
						$actions[] = action_link("Edit Advanced","pencil","writemessage.php?id=$message->id");
					} else {
						$actions[] = action_link("Preview","email_open",null,"preview(\'$type\',\'$subtype\',\'$languagecode\'); return false;");
						$actions[] = action_link("Edit","pencil","writemessage.php?id=$message->id");
					}
					$actions[] = action_link("Delete","cross","mgeditor.php?action=delete&messageid=$message->id");
				} else {
					$link->icon = "diagona/16/160";
					
					// Phone message will need a more custom menu
					if ($type == "phone") {
						$actions[] = action_link("Record","diagona/16/151","recordmessage.php?id=new");
						$actions[] = action_link("New Advanced","pencil_add","writemessage.php?id=new");
					} else {
						$actions[] = action_link("Edit","pencil_add","writemessage.php?id=new");
					}
				}
				
				$link->title = _L("%s Message in %s",$destination,$languagename);
				$link->actions = $actions;
			} else {
				$link->icon = "fugue/slash_disabled";
				$link->title = _L("%s %s is Not Applicable",$languagename,$destination);
			}
			$linkrows[] = $link;
		}
		$links[] = $linkrows;
	}
	
	showActionGrid(array_values($destinations),array_values($customerlanguages),$links);
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
function createactionmenu(id, content) {
	new Tip($(id), content, {
		style: 'protogrey',
		radius: 4,
		border: 4,
		hideOn: false,
		hideAfter: 0.2,
		delay: 0.5,
		stem: 'leftTop',
		hook: {  target: 'leftMiddle', tip: 'topLeft'  },
		width: '110px',
		offset: { x: 16, y: 0 }
	});
//	$(id).observe('click', function(event) {
//		var e = event.element();
//		e.prototip.show();
//	});
}

function preview(type,subtype,languagecode) {
	<? if ($messagegroup) { ?>
	popup('messagegroupviewpopup.php?id=' + <?= $messagegroup->id ?> + '&type=' + type + '&subtype=' + subtype + '&languagecode=' + languagecode, 800, 500);
	<? }?>
}
</script>
<link href="css/messagegroup.css" type="text/css" rel="stylesheet">
<?
startWindow(_L('Message Settings'));
echo $form->render();
if ($messagegroup->id) {
	echo "<h2>Message Content</h2>";
	echo icon_button(_L('Add Content Wizard'),"add",null,"messagewizard.php?mgid=$messagegroup->id");
	echo "<br /><br />";
	makeMessageGrid($messagegroup);
	echo icon_button(_L('Done'),"tick",null,(isset($_SESSION['origin']) && ($_SESSION['origin'] == 'start')?"start.php":"messages.php"));
}
endWindow();

include_once("navbottom.inc.php");
?>