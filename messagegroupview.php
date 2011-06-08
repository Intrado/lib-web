<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("inc/common.inc.php");
require_once("inc/table.inc.php");
require_once("inc/securityhelper.inc.php");
require_once("inc/html.inc.php");
require_once("inc/utils.inc.php");
require_once("inc/date.inc.php");
require_once("obj/Form.obj.php");
require_once("obj/FormTabber.obj.php");
require_once("obj/FormSplitter.obj.php");
require_once("obj/FormItem.obj.php");
require_once("obj/Phone.obj.php");
require_once("obj/Message.obj.php");
require_once("obj/Voice.obj.php");
require_once("obj/FieldMap.obj.php");
require_once("obj/AudioFile.obj.php");
require_once("obj/MessagePart.obj.php");
require_once("obj/Content.obj.php");
require_once("obj/Validator.obj.php");
require_once("obj/MessageGroup.obj.php");
require_once("obj/MessageAttachment.obj.php");
require_once("obj/Language.obj.php");
require_once("inc/messagegroup.inc.php");
require_once("inc/previewfields.inc.php");
require_once("inc/appserver.inc.php");

require_once('thrift/Thrift.php');
require_once $GLOBALS['THRIFT_ROOT'].'/protocol/TBinaryProtocol.php';
require_once $GLOBALS['THRIFT_ROOT'].'/transport/TSocket.php';
require_once $GLOBALS['THRIFT_ROOT'].'/transport/TBufferedTransport.php';
require_once $GLOBALS['THRIFT_ROOT'].'/transport/TFramedTransport.php';
require_once("inc/thrift.inc.php");
require_once($GLOBALS['THRIFT_ROOT'].'/packages/commsuite/CommSuite.php');
require_once("obj/PreviewModal.obj.php");


//$popup = false;
//include_once('messagegroupview.inc.php');
//exit();
///////////////////////////////////////////////////////////////////////////////
// Authorization:
///////////////////////////////////////////////////////////////////////////////
// no messagegroup id

if (!isset($_GET['id']))
	redirect('unauthorized.php');
	
$messagegroup = new MessageGroup($_GET['id'] + 0);

// check publication and subscription restrictions on this message (or original if it is a copy)
// if the user owns this message, they can preview it. Otherwise, check the original message group id
if (!userOwns("messagegroup", $messagegroup->id) && $messagegroup->originalmessagegroupid) {
	if(!userOwns('messagegroup',$messagegroup->originalmessagegroupid) && 
			!isSubscribed("messagegroup",$messagegroup->originalmessagegroupid)) {
		redirect('unauthorized.php');
	}
} else if (!userOwns('messagegroup',$_GET['id'] + 0) && 
		!isPublished('messagegroup', $_GET['id']) && 
		!userCanSubscribe('messagegroup', $_GET['id'])) {
	redirect('unauthorized.php');
}

PreviewModal::HandlePhoneMessageId();

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$PAGE = "notifications:messages";
$TITLE = _L('Message Viewer');

include_once("nav.inc.php");
?>
<script src="script/livepipe/livepipe.js" type="text/javascript"></script>
<script src="script/livepipe/window.js" type="text/javascript"></script>
<script src="script/niftyplayer.js.php" type="text/javascript"></script>
<link href="css/messagegroup.css" type="text/css" rel="stylesheet">
<?
PreviewModal::includePreviewScript();

startWindow(_L('Message Viewer'));


if ($USER->authorize('sendmulti')) {
	$customerlanguages = Language::getLanguageMap();
	unset($customerlanguages["en"]);
	$customerlanguages = array_merge(array("en" => "English"),$customerlanguages);
} else {
	$customerlanguages = array(Language::getDefaultLanguageCode() => Language::getName(Language::getDefaultLanguageCode()));
}
// Setup destination types according to permissions
$columnlabels = array();

echo  "<table class='messagegrid'><tr><th></th>";

if ($USER->authorize('sendphone')) {
	echo "<th class='messagegridheader'>Phone</th>";
}

if (getSystemSetting('_hassms', false) && $USER->authorize('sendsms')) {
	echo "<th class='messagegridheader'>SMS</th>";
}

if ($USER->authorize('sendemail')) {
	echo "<th class='messagegridheader'>Email (HTML)</th>";
	echo "<th class='messagegridheader'>Email (Text)</th>";
}
echo  "</tr>";
foreach ($customerlanguages as $languagecode => $languagename) {
	echo "<tr><th class='messagegridlanguage'>$languagename</th>";

	if ($USER->authorize('sendphone')) {
		$message = $messagegroup->getMessage('phone', 'voice', $languagecode);
		echo "<td>";
		if ($message)
			echo "<a href=\"#\" onclick=\"showPreview(null,'jobpriority=$jobpriority&previewid=$message->id'); return false;\"><img src='img/icons/accept.gif' /></a>";
		else
			echo "<img src='img/icons/diagona/16/160.gif' />";
		echo "</td>";
	}
	
	if (getSystemSetting('_hassms', false) && $USER->authorize('sendsms')) {
		if ($languagecode == 'en') {
			$message = $messagegroup->getMessage('sms', 'plain', $languagecode);
			echo "<td>";
			if ($message) 
				echo "<a href=\"#\" onclick=\"showPreview(null,'jobpriority=$jobpriority&previewid=$message->id'); return false;\"><img src='img/icons/accept.gif' /></a>";
			else 
				echo "<img src='img/icons/diagona/16/160.gif' />";
			echo "</td>";
		} else {
			echo "<td>-</td>";
		}
		
	}
	
	if ($USER->authorize('sendemail')) {
		$message = $messagegroup->getMessage('email', 'html', $languagecode);
		echo "<td>";
		if ($message) 
			echo "<a href=\"#\" onclick=\"showPreview(null,'jobpriority=$jobpriority&previewid=$message->id'); return false;\"><img src='img/icons/accept.gif' /></a>";
		else 
			echo "<img src='img/icons/diagona/16/160.gif' />";
		echo "</td>";
		
		$message = $messagegroup->getMessage('email', 'plain', $languagecode);
		echo "<td>";
		if ($message) 
			echo "<a href=\"#\" onclick=\"showPreview(null,'jobpriority=$jobpriority&previewid=$message->id'); return false;\"><img src='img/icons/accept.gif' /></a>";
		else 
			echo "<img src='img/icons/diagona/16/160.gif' />";
		echo "</td>";
	}
	echo "</tr>";
}
echo "</table>";
//echo icon_button(_L("Done"),"tick", null, "messages.php");
endWindow();
include_once("navbottom.inc.php");


