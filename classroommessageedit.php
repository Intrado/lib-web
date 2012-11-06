<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("inc/common.inc.php");
require_once("inc/securityhelper.inc.php");
require_once("inc/table.inc.php");
require_once("inc/html.inc.php");
require_once("inc/utils.inc.php");
require_once("obj/Validator.obj.php");
require_once("obj/Form.obj.php");
require_once("obj/FormItem.obj.php");
require_once("obj/MessageGroup.obj.php");
require_once("obj/Message.obj.php");
require_once("obj/MessagePart.obj.php");
require_once("obj/TargetedMessage.obj.php");
require_once("obj/Language.obj.php");
require_once("obj/TargetedMessageCategory.obj.php");




////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!getSystemSetting('_hastargetedmessage', false) || !$USER->authorize('manageclassroommessaging')) {
	redirect('unauthorized.php');
}

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

if (isset($_GET['id'])) {
	if($_GET['id'] == "new") {
		$_SESSION["targetedmessageid"] = null;
		if (isset($_GET['categoryid'])) {
			$_SESSION["targetedmessagecategoryid"] = $_GET['categoryid'];
		}
	} else {
		$_SESSION["targetedmessageid"] = $_GET['id'] + 0;
	}
	redirect("classroommessageedit.php");
}


$targetedmessage = null;
$targetedmessagecategory = null;

if(isset($_SESSION["targetedmessageid"])) {
	$targetedmessage = DBFind("TargetedMessage", "from targetedmessage where id=?",false,array($_SESSION["targetedmessageid"]));
	if (!$targetedmessage) {
		redirect('unauthorized.php');
	}
	$targetedmessagecategory = new TargetedMessageCategory($targetedmessage->targetedmessagecategoryid);
} else {
	// New Custome Targeted Message, Create blank MessageGroup and Targeted Message
	if (isset($_SESSION["targetedmessagecategoryid"])) {
		$targetedmessagecategory = DBFind("targetedmessagecategory", "from targetedmessagecategory where id=? and not deleted",false,array($_SESSION["targetedmessagecategoryid"]));
	
		if(!$targetedmessagecategory) {
			redirect('unauthorized.php');
		}
	}
	
	$messagegroup = new MessageGroup();
	$messagegroup->userid = $USER->id;
	$messagegroup->name = "Custom Classroom";
	$messagegroup->description = '';
	$messagegroup->modified = date("Y-m-d H:i:s", time());
	$messagegroup->deleted = 1;
	$messagegroup->permanent = 1;
	$messagegroup->create();
	
	$targetedmessage = new TargetedMessage();
	$targetedmessage->messagekey = "custom-" .  $messagegroup->id;
	$targetedmessage->targetedmessagecategoryid = $targetedmessagecategory->id;
	$targetedmessage->overridemessagegroupid = $messagegroup->id;
	$targetedmessage->create();
}


if(isset($targetedmessage->overridemessagegroupid)) {
	$languagemessages = QuickQueryList("select m.languagecode, p.txt from message m, messagepart p
			where m.messagegroupid = ? and m.id = p.messageid and m.type='email'", true,false,array($targetedmessage->overridemessagegroupid));
}

if(!isset($messagedatacache)) { 
	$messagedatacache = array();
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$PAGE = "admin:settings";
$TITLE = _L('Classroom Message Edit');

include_once("nav.inc.php");

startWindow(_L('Language Variations for Classroom Message'));
$languages = Language::getLanguageMap();
error_log(json_encode($messagedatacache));
foreach($languages as $code => $languagename) {
	$value = "";

	if ($targetedmessage->overridemessagegroupid) {
		$editlink = "classroommessageeditlanguage.php?mgid={$targetedmessage->overridemessagegroupid}&languagecode=$code&targetmessagekey={$targetedmessage->messagekey}";
	} else {
		$editlink = "classroommessageoverride.php?languagecode=$code&targetmessagekey={$targetedmessage->messagekey}";
	}
	
	if(isset($languagemessages[$code]) && $languagemessages[$code] != "") {
		$value = $languagemessages[$code];
	} else {			
		// Try to find default value
		$filename = "messagedata/" . $code . "/targetedmessage.php";
		if(file_exists($filename))
			include_once($filename);
		
		if(isset($messagedatacache[$code]) && isset($messagedatacache[$code][$targetedmessage->messagekey])) {
			$value = $messagedatacache[$code][$targetedmessage->messagekey];
		}
	}
	echo $languagename  . '<p class="translate_text">'.escapehtml($value) . icon_button("Edit", "pencil",false,$editlink) . '</p> <br/>';
}

echo icon_button(_L('Done'),"tick",null,"classroommessagemanager.php");
endWindow();
include_once("navbottom.inc.php");
?>
