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
require_once("obj/PreviewModal.obj.php");
require_once("obj/JobType.obj.php");


///////////////////////////////////////////////////////////////////////////////
// Authorization:
///////////////////////////////////////////////////////////////////////////////
// no messagegroup id

if (!isset($_GET['id']))
	redirect('unauthorized.php');

// check if the user can view this message group
if (!userCanSee("messagegroup", $_GET['id']))
	redirect("unauthorized.php");

$messagegroup = new MessageGroup($_GET['id'] + 0);

PreviewModal::HandleRequestWithId();

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$PAGE = "notifications:messages";
$TITLE = _L('Message Viewer');

include_once("nav.inc.php");
?>
<script src="script/niftyplayer.js.php" type="text/javascript"></script>
<script type="text/javascript" src="script/getMessageGroupPreviewGrid.js"></script>
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
			<?if (isset($_GET['redirect'])) {
				switch($_GET["redirect"]) {
					case "phone":
						$message = $messagegroup->getMessage("phone", "voice", $messagegroup->defaultlanguagecode);
						break;
					case "sms":
						$message = $messagegroup->getMessage("sms", "plain", "en");
						break;
					case "email":
						$message = $messagegroup->getMessage("email", "html", $messagegroup->defaultlanguagecode);
						if (!$message)
							$message = $messagegroup->getMessage("email", "plain", $messagegroup->defaultlanguagecode);
						break;
					case "facebook":
						$message = $messagegroup->getMessage("post", "facebook", "en");
						break;
					case "twitter":
						$message = $messagegroup->getMessage("post", "twitter", "en");
						break;
					case "feed":
						$message = $messagegroup->getMessage("post", "feed", "en");
						break;
					case "page":
						$message = $messagegroup->getMessage("post", "page", "en");
						break;
					case "voice":
						$message = $messagegroup->getMessage("post", "voice", "en");
						break;
				}
				if ($message)
					echo "showPreview(null,'previewid=$message->id')";
			}
			?>
		});
	</script>
<?
$fallbackUrl = "messages.php";
echo icon_button(_L("Done"),"tick","location.href='" . (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $fallbackUrl) . "'");
endWindow();
include_once("navbottom.inc.php");


