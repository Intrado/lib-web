<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("common.inc.php");
require_once("../obj/FieldMap.obj.php");
require_once("../inc/form.inc.php");
require_once("../inc/html.inc.php");
require_once("../inc/table.inc.php");
require_once("../inc/utils.inc.php");
require_once("../inc/date.inc.php");
require_once("../inc/securityhelper.inc.php");
require_once("../inc/formatters.inc.php");
require_once("../inc/reportutils.inc.php");
require_once("../obj/Phone.obj.php");
require_once("../obj/Message.obj.php");
require_once("../obj/MessageGroup.obj.php");
require_once("../obj/AudioFile.obj.php");
require_once("../obj/MessagePart.obj.php");
require_once("../obj/MessageAttachment.obj.php");
require_once("../obj/ContentAttachment.obj.php");
require_once("../obj/BurstAttachment.obj.php");
require_once("../obj/Voice.obj.php");
require_once("../obj/Language.obj.php");
require_once("../inc/appserver.inc.php");

// load the thrift api requirements.
$thriftdir = '../Thrift';
require_once("{$thriftdir}/Base/TBase.php");
require_once("{$thriftdir}/Protocol/TProtocol.php");
require_once("{$thriftdir}/Protocol/TBinaryProtocol.php");
require_once("{$thriftdir}/Protocol/TBinaryProtocolAccelerated.php");
require_once("{$thriftdir}/Transport/TTransport.php");
require_once("{$thriftdir}/Transport/TSocket.php");
require_once("{$thriftdir}/Transport/TBufferedTransport.php");
require_once("{$thriftdir}/Transport/TFramedTransport.php");
require_once("{$thriftdir}/Exception/TException.php");
require_once("{$thriftdir}/Exception/TProtocolException.php");
require_once("{$thriftdir}/Exception/TApplicationException.php");
require_once("{$thriftdir}/Type/TType.php");
require_once("{$thriftdir}/Type/TMessageType.php");
require_once("{$thriftdir}/StringFunc/TStringFunc.php");
require_once("{$thriftdir}/Factory/TStringFuncFactory.php");
require_once("{$thriftdir}/StringFunc/Core.php");
require_once("{$thriftdir}/packages/commsuite/Types.php");
require_once("{$thriftdir}/packages/commsuite/CommSuite.php");

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

$pid = $_SESSION['personid'];

if(isset($_GET['type'])){
	$_SESSION['type'] = $_GET['type'];
}

if(isset($_GET['jobid'])){
	$_SESSION['previewmessage_jobid'] = $_GET['jobid']+0;
	$_SESSION['previewmessage_personid'] = $pid;
	$_SESSION['previewmessagegroupid'] = QuickQuery("select messagegroupid from job where id = " . $_SESSION['previewmessage_jobid']);
	redirect();
}


$jobid = isset($_SESSION['previewmessage_jobid']) ? $_SESSION['previewmessage_jobid'] : 0;
$messagegroupid = isset($_SESSION['previewmessagegroupid']) ? $_SESSION['previewmessagegroupid'] : 0;
$personid = isset($_SESSION['previewmessage_personid']) ? $_SESSION['previewmessage_personid'] : 0;
$phone = isset($_SESSION['type']) && $_SESSION['type'] == "phone" ? true : false;
$email = isset($_SESSION['type']) && $_SESSION['type'] == "email" ? true : false;
$sms = isset($_SESSION['type']) && $_SESSION['type'] == "sms" ? true : false;
$dopreview = 0;
$fields = array();

// find messagegroup for job
$messagegroup = new MessageGroup($messagegroupid);
// list of available languages for this message type
$availableMessageLanguages = $messagegroup->getMessageLanguageCodesOfType($_SESSION['type']);
// now map languagecodes to language display name
foreach ($availableMessageLanguages as $langcode) {
	$availableMessageLanguages[$langcode] = Language::getName($langcode);
}


// language form
$f="popuppreviewmessage";
$s="main";
$reloadform = 0;
$_SESSION['previewmessage_langcode'] = $messagegroup->defaultlanguagecode;
$_SESSION['formdata'][$f]['timestamp'] = ""; // hack to avoid error_log

if (CheckFormSubmit($f,$s)) {
	//check to see if formdata is valid
	if (CheckFormInvalid($f)) {
		error('Form was edited in another window, reloading data');
		$reloadform = 1;
	} else {
		MergeSectionFormData($f, $s);

		if (CheckFormSection($f, $s)) {
			error('There was a problem trying to save your changes', 'Please verify that all required field information has been entered properly');
		}
		// all good, save data
		// nothing to save, just display message language
		$_SESSION['previewmessage_langcode'] = getFormData($f, $s, "language");
		
	}
} else {
	$reloadform = 1;
}

if ($reloadform) {
	PutFormData($f, $s, "language", $_SESSION['previewmessage_langcode'], "text", "nomin", "nomax");
}


// if historic data should be used, fetch it now
if ($jobid != 0 && $personid != 0) {
	$query = "select
			rp." . FieldMap::GetFirstNameField() . ",
			rp." . FieldMap::GetLastNameField()
			. generateFields("rp")
			. ",
			j.name as jobname,
			j.startdate as startdate
			from reportperson rp
			left join job j on (j.id = rp.jobid)
			where j.id = '$jobid'
			and rp.personid = '$personid'";
	if(isset($_SESSION['type'])){
		$query .= " and rp.type = '" . DBSafe($_SESSION['type']) . "'";
	}
	$historicdata = QuickQueryRow($query, true);
}

if ($email) {
	$attachments = $messagegroup->getGlobalEmailAttachments(true);
	// find which message exists in the group, prefer html
	$message = $messagegroup->getMessage("email", "html", $_SESSION['previewmessage_langcode']);
	if ($message == null)
		$message = $messagegroup->getMessage("email", "plain", $_SESSION['previewmessage_langcode']);
	
	// call appserver to render email
	$view = messageViewForJobPerson($message->id, $jobid, $personid);
	$messagetext = $view->emailbody;
} else if ($sms) {
	$messagetext = $messagegroup->getMessageText("sms", "plain", "en", "none");
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$TITLE = _L("Message");

include_once("popup.inc.php");

NewForm($f);

buttons(button(_L('Done'), 'window.close()'));


startWindow(_L('Details'), 'padding: 3px;');
?>

	<table width="100%" border="0" cellpadding="3" cellspacing="0">
		<tr>
			<th align="right" class="windowRowHeader bottomBorder"><?=_L("Message For")?>:</th>
			<td class="bottomBorder"><?=escapehtml($historicdata['f01']) . " " . escapehtml($historicdata['f02'])?></td>
		</tr>
		<tr>
			<th align="right" class="windowRowHeader bottomBorder"><?=_L("Job Name")?>:</th>
			<td class="bottomBorder"><?=escapehtml($historicdata['jobname'])?></td>
		</tr>
		<tr>
			<th align="right" class="windowRowHeader bottomBorder"><?=_L("Date")?>:</th>
			<td class="bottomBorder"><?=date("M j, Y", strtotime($historicdata['startdate']))?></td>
		</tr>
<?
	if (count($availableMessageLanguages) > 1) {
?>
		<tr>
			<th align="right" class="windowRowHeader bottomBorder"><?=_L("Language")?>:</th>
			<td><table><tr><td>
			<?
				NewFormItem($f, $s, 'language', 'selectstart', null, null, "id='language'");
				foreach ($availableMessageLanguages as $langcode => $lang) {
					NewFormItem($f, $s, 'language', 'selectoption', $lang, $langcode);
				}
				NewFormItem($f, $s, 'language', 'selectend');
				
				$changebutton = submit($f, $s, _L('Change'));
				echo "</td><td>" . $changebutton;
			?>
			</td></tr></table>
		</tr>
<?
	}
?>
<?
	if ($email && count($attachments)) {
?>
			<tr>
				<th align="right" class="windowRowHeader bottomBorder"><?=_L("Attachments")?>:</th>
				<td colspan="3" class="bottomBorder">
					<table border="0" cellpadding="2" cellspacing="1" class="list" width="100%">
					<tr class="listHeader" align="left" valign="bottom">
						<th><?=_L("Name")?></th>
						<th><?=_L("Size")?></th>
					</tr>
<?
				foreach ($attachments as $attachment) {
					$filename = $attachment->getFilename();
					$size = $attachment->getSize();
					if ($size === false)
						$size = "";
					else
						$size = max(1,round($size/1024)) . "K";
?>
					<tr>
						<td><a href="messageattachmentdownload.php?pid=<?= $personid ?>&jid=<?= $jobid ?>&attid=<?= $attachment->id ?>">
								<?= escapehtml($filename)?></a>
						</td>
						<td><?= $size ?></td>
					</tr>
<?
				}
?>
				</table>
				</td>
			</tr>
<?
	}
?>
	</table>
<?
endWindow();

if ($phone) {
	startWindow(_L('Message'), 'padding: 3px;');
	?>

		<div align="center">

		<div id="player"></div>		
		<script type="text/javascript" language="javascript" src="script/niftyplayer.js.php"></script>
		<script language="JavaScript" type="text/javascript">
	 				embedPlayer("preview.wav.php/embed_preview.wav?jid=<?= $jobid ?>&pid=<?= $personid ?>&langcode=<?= $_SESSION['previewmessage_langcode'] ?>","player");
		</script>
		<br><a href="preview.wav.php/download_preview.wav?jid=<?= $jobid ?>&pid=<?= $personid ?>&langcode=<?= $_SESSION['previewmessage_langcode'] ?>&download=true"><?=_L("Click here to download")?></a>
		</div>
	<?
	endWindow();
} else if ($email || $sms) {
	startWindow(_L('Message'), 'padding: 3px;');
?>
		<div><?= $messagetext ?></div>
<?
	endWindow();
}

include_once("popupbottom.inc.php");

?>