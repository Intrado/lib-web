<?
require_once("inc/common.inc.php");
require_once("inc/securityhelper.inc.php");
require_once("obj/Voice.obj.php");
require_once("obj/MessageGroup.obj.php");
require_once("obj/Message.obj.php");
require_once("obj/MessagePart.obj.php");
require_once("obj/FieldMap.obj.php");
require_once("inc/previewfields.inc.php");
require_once("obj/Validator.obj.php");
require_once("obj/Form.obj.php");
require_once("obj/FormItem.obj.php");
require_once("inc/html.inc.php");
require_once("inc/table.inc.php");
require_once("obj/Language.obj.php");
require_once("inc/appserver.inc.php");
require_once("inc/thrift.inc.php");
require_once("obj/MessageAttachment.obj.php");
require_once("obj/EmailAttach.fi.php");

require_once($GLOBALS['THRIFT_ROOT'].'/packages/commsuite/CommSuite.php');


// Generate a phone item from either message id or session data
function playFormItem($hasdata, $messageid = false) {
	$requestvaiables = ($messageid)?"id=$messageid":(isset($_SESSION['ttstext'])?"usetext=true":"blank=true");	
	return array(
		"label" => "",
		"control" => array("FormHtml","html" =>
			($hasdata?submit_button(_L('Play with Field(s)'),"submit","fugue/control"):'') . '
			<div id="messageresultdiv" name="messageresultdiv"></div>
			<div id="messagepreviewdiv" name="messagepreviewdiv">
				<div align="center" style="clear:left">
					<div id="player"></div>' .
					($hasdata?'':'<script language="JavaScript" type="text/javascript">
										embedPlayer("preview.wav.php/embed_preview.wav?' . $requestvaiables . '","player");
									</script>') .
					'<div id="download">' . ($hasdata?'':'<a href="preview.wav.php/download_preview.wav?' . $requestvaiables . '&download=true" onclick="sessiondata=false;">' . _L("Click here to download") . '</a>') .
					'</div>
				</div>
			</div>
		'),
		"fieldhelp" => "",
		"renderoptions" => array("icon" => false, "label" => false, "errormessage" => false),
		"helpstep" => 1
	);
}

if (isset($_GET['unloadsession'])) {
	unset($_SESSION['ttstext']);
	unset($_SESSION['ttsgender']);
	unset($_SESSION['ttslanguage']);
	exit();
}


$messageformdata = array();
$message = null;

if (isset($_GET['id'])) {	
	$message = new Message($_GET['id']);
	// Make sure that the user is autherized to view this message
	if(!($message && (userOwns("message", $message->id) || 
		$USER->authorize('managesystem') || 
		(isPublished("messagegroup", $message->messagegroupid) && userCanSubscribe("messagegroup", $message->messagegroupid))))) {
		redirect('unauthorized.php');
		exit();
	}
	$parts = DBFindMany('MessagePart', 'from messagepart where messageid=? order by sequence', false, array($message->id));
	//$messageformdata = array();
	$messagetext = "";
	if ($message->type == 'sms') {
		$messagetext = Message::renderSmsParts($parts);
		$messageformdata["message"] = array(
			"label" => null,
			"control" => array("FormHtml","html" => "<div class='MessageTextReadonly'>$messagetext</div>"),
			"renderoptions" => array("icon" => false, "label" => false, "errormessage" => false),
			"helpstep" => 1
		);
	} else {
		if ($message->type == 'email') {
			// Preview email with template will depend on the selected job priority, if not set use default defined by renderEmailWithTemplate
			if (isset($_GET['jobpriority']))
				$messagetext = $message->renderEmailWithTemplate($_GET['jobpriority'] + 0);
			else
				$messagetext = $message->renderEmailWithTemplate();
			// Leave html emails unescaped
			if ($message->subtype != 'html') {
				$messagetext = escapehtml($messagetext);
			}
			
			// get the attachments
			$attachments = array();
			$msgattachments = DBFindMany("MessageAttachment", "from messageattachment where not deleted and messageid = ?", false, array($message->id));
			foreach ($msgattachments as $msgattachment) {
				$attachments[] = "<a href='emailattachment.php?id=" . $msgattachment->contentid . "&name=" . urlencode($msgattachment->filename) . "'>$msgattachment->filename</a> (Size: " . ceil($msgattachment->size/1024) . "k)";
			}
			$messageformdata["attachments"] = array(
				"label" => null,
				"control" => array("FormHtml","html" => "<div class='MessageTextReadonly'>" . implode("<br />",$attachments) . "</div>"),
				"renderoptions" => array("icon" => false, "label" => false, "errormessage" => false),
				"helpstep" => 1
			);
			
		}
		
		$messagehtml = "";
		if ($message->type != 'phone') {
			$messageformdata["message"] = array(
				"label" => null,
				"control" => array("FormHtml","html" => "<div class='MessageTextReadonly'>$messagetext</div>"),
				"renderoptions" => array("icon" => false, "label" => false, "errormessage" => false),
				"helpstep" => 1
			);
		} else { // phone
			list($fields,$fielddata,$fielddefaults) = getpreviewfieldmapdata($message->id);
			
			$messageformdata += getpreviewformdata($fields,$fielddata,$fielddefaults,"phone");
			$hasdata = count($messageformdata) > 0;
			$messageformdata[] = playFormItem($hasdata,$message->id);
		}
		
		if ($message->type == 'translated') {
			$messageformdata[] = array(
				"label" => null,
				"control" => array("FormHtml",
					"html" => '<div id="branding" style="margin-top:20px">
								<div style="color: rgb(103, 103, 103);" class="gBranding"><span style="vertical-align: middle; font-family: arial,sans-serif; font-size: 11px;" class="gBrandingText">Translation powered by<img style="padding-left: 1px; vertical-align: middle;" alt="Google" src="' . (isset($_SERVER['HTTPS'])?"https":"http") . '://www.google.com/uds/css/small-logo.png"></span></div>
								</div>'),
				"renderoptions" => array("icon" => false, "label" => false, "errormessage" => false),
				"helpstep" => 1
			);
		}
	}
} else if($_SESSION['ttstext']) {
		//load all audiofileids for this messagegroup, should be OK since only way to preview via text parse is inside MG editor
	$audiofileids = null;
	if (isset($_SESSION['messagegroupid']))
		$audiofileids = MessageGroup::getReferencedAudioFileIDs($_SESSION['messagegroupid']);
	else
		error_log("ERROR: preview.wav.php called on text with no messagegroupid");
	
	$parts = Message::parse($_SESSION['ttstext'],$errors,1,$audiofileids);
	$fieldnums = array();
	$fielddefaults = array();
	
	foreach($parts as $part) {
		if(isset($part->fieldnum)) {
			$fieldnums[] = $part->fieldnum;
			$fielddefaults[$part->fieldnum] = $part->defaultvalue;
		}
	}	
	
	$messagefields = DBFindMany("FieldMap", "from fieldmap where fieldnum in ('" . implode("','",$fieldnums) .  "')");
	
	$fields = array();
	$fielddata = array();
	
	foreach ($messagefields as $fieldmap) {		
		$fields[$fieldmap->fieldnum] = $fieldmap;
		if ($fieldmap->isOptionEnabled("multisearch")) {
			$limit = DBFind('Rule', "from rule r inner join userassociation ua on r.id = ua.ruleid where ua.userid=? and type = 'rule' and r.fieldnum=?", "r", array($USER->id, $fieldmap->fieldnum));
			$limitsql = $limit ? $limit->toSQL(false, 'value', false, true) : '';
			$fielddata[$fieldmap->fieldnum] = QuickQueryList("select value,value from persondatavalues where fieldnum=? $limitsql order by value limit 5000", true, false, array($fieldmap->fieldnum));
		}
	}
	
	$messageformdata += getpreviewformdata($fields,$fielddata,$fielddefaults,"phone");
	$hasdata = count($messageformdata) > 0;
	$messageformdata[] = playFormItem($hasdata);
} else {
	redirect('unauthorized.php');
}


$form = new Form("messagegroupedit",$messageformdata,null,array(""));
////////////////////////////////////////////////////////////////////////////////
// Form Data Handling
////////////////////////////////////////////////////////////////////////////////

//check and handle an ajax request (will exit early)
//or merge in related post data
$form->handleRequest();
//check for form submission

if ($button = $form->getSubmit()) {
	$ajax = $form->isAjaxSubmit();
	if (!$form->checkForDataChange() && $form->validate() === false) {
		$postdata = $form->getData();
		$previewdata = "";
		foreach ($postdata as $field => $value) {
			$previewdata .= "&$field=" . urlencode($value);
		}
		
		$requestvaiables = (isset($_GET['id']))?"id=$message->id":(isset($_SESSION['ttstext'])?"usetext=true":"blank=true");	
		
		$form->modifyElement("messageresultdiv", '
				<script language="JavaScript" type="text/javascript">
					embedPlayer("preview.wav.php/embed_preview.wav?' . $requestvaiables . $previewdata. '","player");
					$("download").update(\'<a href="preview.wav.php/download_preview.wav?'  . $requestvaiables .  $previewdata . '&download=true" onclick="sessiondata=false;">' . _L("Click here to download") . '</a>\');
				</script>');
		return;
	}
}




$PAGE = "notifications:messages";
$TITLE = _L('Message Viewer');

include_once('popup.inc.php');
button_bar(button('Done', 'window.close()'));
echo '<br/>';

?>
<script type="text/javascript" language="javascript" src="script/niftyplayer.js.php"></script>

<?

if ($message) {
	$messagetypes = array("phone" => _L("Phone"), "email" => _L("Email"), "sms" => _L("SMS"));
	$windowtitle =_L('%s Message in %s', $messagetypes[$message->type], Language::getName($message->languagecode));
} else {
	$windowtitle =_L('Phone Message');
}
startWindow($windowtitle);
echo $form->render();
endWindow();


?>