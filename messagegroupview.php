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
<script type="text/javascript" src="script/getMessageGroupPreviewGrid.js.php"></script>
<?
PreviewModal::includePreviewScript();
startWindow(_L('Message Settings'));
?>
<table>
	<tr>
		<th style="text-align: right;padding-right: 15px;"><?=_L("Message Name") ?></th>
		<td><?=$messagegroup->name?></td>
	</tr>
	<tr>
		<th style="text-align: right;padding-right: 15px;"><?=_L("Description") ?></th>
		<td><?=$messagegroup->description?></td>
	</tr>
</table>
<? 

endWindow();

startWindow(_L('Message Content'));
?>
	<div id="preview"></div>
	<script type="text/javascript">
		document.observe('dom:loaded', function() {
			getMessageGroupPreviewGrid(<?=$messagegroup->id?>, 'preview', null);
		});
	</script>
<?
$fallbackUrl = "messages.php";
echo icon_button(_L("Done"),"tick","location.href='" . (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $fallbackUrl) . "'");
endWindow();
include_once("navbottom.inc.php");


