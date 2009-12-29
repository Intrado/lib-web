<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("inc/common.inc.php");
require_once("inc/securityhelper.inc.php");
require_once("obj/Message.obj.php");
require_once("obj/MessagePart.obj.php");
require_once("obj/AudioFile.obj.php");
require_once("obj/FieldMap.obj.php");
require_once("obj/SurveyQuestionnaire.obj.php");
require_once("obj/SurveyQuestion.obj.php");
require_once("inc/html.inc.php");
require_once("inc/form.inc.php");
require_once("inc/table.inc.php");
require_once("inc/utils.inc.php");
require_once("inc/formatters.inc.php");


////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize(array('sendmessage', 'sendemail', 'sendphone', 'sendsms'))) {
	redirect('unauthorized.php');
}

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

if (isset($_GET['delete'])) {
	$deleteid = DBSafe($_GET['delete']);
	if (isset($_SESSION['messageid']) && ($_SESSION['messageid']== $deleteid))
		$_SESSION['messageid'] = NULL;
	if (userOwns("message",$deleteid)) {
		$message = new Message($deleteid);
		QuickUpdate("update message set deleted=1 where id='$deleteid'");
		notice(_L("The message, %s, is now deleted.", escapehtml($message->name)));
		redirect();
	} else {
		notice(_L("You do not have permission to delete this message."));
	}
}


//preload audiofile information to determine simple/advanced phone messages
//save messageid => audiofileid
$query = "select m.id, mp.audiofileid, count(*) as cnt, mp.type
from message m inner join messagepart mp on (m.id=mp.messageid)
where m.type='phone' and m.userid=" . $USER->id . " and m.deleted=0
group by m.id
having cnt = 1 and mp.type='A' ";
$SIMPLEPHONEMESSAGES = QuickQueryList($query,true);

////////////////////////////////////////////////////////////////////////////////
// Display Functions
////////////////////////////////////////////////////////////////////////////////

function fmt_actions ($obj,$name) {
	global $SIMPLEPHONEMESSAGES;
/*
	$advancedplaybtn = button("Play", "popup('previewmessage.php?close=1&id=$obj->id', 400, 500);");
	$editbtn = '<a href="message' . $obj->type . '.php?id=' . $obj->id . '">Edit</a>';
	$deletebtn = '<a href="messages.php?delete=' . $obj->id . '" onclick="return confirmDelete();">Delete</a>';
	$renamebtn = '<a href="messagerename.php?id=' . $obj->id . '">Rename</a>';

	if ($obj->type == "phone" && isset($SIMPLEPHONEMESSAGES[$obj->id])) {
		return  "$advancedplaybtn&nbsp;|&nbsp;$renamebtn&nbsp;|&nbsp;$deletebtn";
	} else {
		if ($obj->type == "phone") {
			return "$advancedplaybtn&nbsp;|&nbsp;$editbtn&nbsp;|&nbsp;$renamebtn&nbsp;|&nbsp;$deletebtn";
		} else {
			return "$editbtn&nbsp;|&nbsp;$renamebtn&nbsp;|&nbsp;$deletebtn";
		}
	}
*/

	$advancedplaybtn = action_link("Play","diagona/16/131",null,"popup('previewmessage.php?close=1&id=$obj->id', 400, 500,'preview'); return false;");
	$editbtn = action_link("Edit", "pencil", 'message' . $obj->type . '.php?id=' . $obj->id);
	$deletebtn = action_link("Delete", "cross", 'messages.php?delete=' . $obj->id, "return confirmDelete();");
	$renamebtn = action_link("Rename", "textfield_rename", 'messagerename.php?id=' . $obj->id);

	if ($obj->type == "phone" && isset($SIMPLEPHONEMESSAGES[$obj->id])) {
		return  action_links($advancedplaybtn,$renamebtn,$deletebtn);
	} else {
		if ($obj->type == "phone") {
			return action_links($advancedplaybtn,$editbtn,$renamebtn,$deletebtn);
		} else {
			return action_links($editbtn,$renamebtn,$deletebtn);
		}
	}

}

function fmt_phonetype ($obj,$name) {
	global $SIMPLEPHONEMESSAGES;
	if (isset($SIMPLEPHONEMESSAGES[$obj->id])) {
		return "Simple";
	} else {
		return "Advanced";
	}
}



function fmt_creator ($obj,$name) {
	$creator = DBFind("User","from user where id=$obj->userid");
	return $creator->shortName();
}



////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE = "notifications:messages";
$TITLE = "Message Builder";

include_once("nav.inc.php");



$scrollThreshold = 8;

if($USER->authorize('sendphone')) {
	$data = DBFindMany("Message",", (name + 0) as foo from message where type='phone' and userid=$USER->id and deleted=0 order by foo, name");
	$scroll = false;
	if (count($data) > $scrollThreshold) {
		$scroll = true;
	}
	startWindow('My Phone Messages ' . help('Messages_MyPhoneMessages'), 'padding: 3px;', true, true);

	if ($USER->authorize('starteasy')) {
		button_bar(button('Call Me To Record', "document.location='callme.php?origin=messages'") . help('AudioFileEditor_CallMeToRecord'),
			button('Create Advanced Message', "document.location='messagephone.php?id=new'") . help('Messages_AddPhoneMessage'),
			button('Audio Library', "popup('audio.php',500,400);") . help('Messages_AudioFileEditor'));
	} else {
		button_bar(button('Create Advanced Message', "document.location='messagephone.php?id=new'") . help('Messages_AddPhoneMessage'),
			button('Audio Library', "popup('audio.php',500,400);") . help('Messages_AudioFileEditor'));
	}



	$phonetitles = array(	"name" => "#Name",
						"description" => "#Description",
						"Type" => "#Type",
						"Actions" => "Actions"
					);

	showObjects($data, $phonetitles, array("Type" => "fmt_phonetype", "Actions" => "fmt_actions", "userid" => "fmt_creator"), $scroll, true);
	endWindow();
	echo '<br>';
}


$titles = array(	"name" => "#Name",
					"description" => "#Description",
					"Actions" => "Actions"
					);


if($USER->authorize('sendemail')) {
	$data = DBFindMany("Message",", (name + 0) as foo from message where type='email' and userid=$USER->id and deleted=0 order by foo, name");
	$scroll = false;
	if (count($data) > $scrollThreshold) {
		$scroll = true;
	}
	startWindow('My Email Messages ' . help('Messages_MyEmailMessages'), 'padding: 3px;', true, true);

	button_bar(button('Create Email Message', NULL,'messageemail.php?id=new') . help('Messages_AddEmailMessage'));

	showObjects($data, $titles, array("Actions" => "fmt_actions", "userid" => "fmt_creator"), $scroll, true);
	endWindow();
	echo '<br>';
}

if(getSystemSetting('_hassms', false) && $USER->authorize('sendsms')) {
	$data = DBFindMany("Message",", (name + 0) as foo from message where type='sms' and userid=$USER->id and deleted=0 order by foo, name");
	$scroll = false;
	if (count($data) > $scrollThreshold) {
		$scroll = true;
	}
	startWindow('My SMS Messages ' . help('Messages_MySmsMessages'), 'padding: 3px;', true, true);

	button_bar(button('Create SMS Message', NULL,'messagesms.php?id=new') . help('Messages_AddSmsMessage'));

	showObjects($data, $titles, array("Actions" => "fmt_actions", "userid" => "fmt_creator"), $scroll, true);
	endWindow();
	echo '<br>';
}


include_once("navbottom.inc.php");
?>