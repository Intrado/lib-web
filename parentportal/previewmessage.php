<?

//Preview Message from portal only

////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
include_once("common.inc.php");
include_once("../obj/FieldMap.obj.php");
include_once("../inc/form.inc.php");
include_once("../inc/html.inc.php");
require_once("../inc/table.inc.php");
require_once("../inc/utils.inc.php");
require_once("../inc/date.inc.php");
require_once("../inc/securityhelper.inc.php");
require_once("../inc/formatters.inc.php");
require_once("../inc/reportutils.inc.php");
require_once("../obj/Phone.obj.php");
require_once("../obj/Message.obj.php");
require_once("../obj/AudioFile.obj.php");
require_once("../obj/MessagePart.obj.php");
require_once("../obj/MessageAttachment.obj.php");
require_once("../obj/Voice.obj.php");
require_once("parentportalutils.inc.php");


////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

if(isset($_GET['personid'])){
	$ids = getContactIDs($_SESSION['portaluserid']);
	if(!in_array($_GET['personid'], $ids)){
		redirect("unauthorized.php");
	}
}

if(isset($_GET['type'])){
	$_SESSION['type'] = $_GET['type'];
}

if(isset($_GET['jobid']) && isset($_GET['personid'])){
	$_SESSION['previewmessage_jobid'] = $_GET['jobid']+0;
	$_SESSION['previewmessage_personid'] = $_GET['personid']+0;
	$query = "select messageid from reportperson where jobid = '" . $_SESSION['previewmessage_jobid'] . "'
												 and personid = '" . $_SESSION['previewmessage_personid'] . "'";
	if(isset($_SESSION['type'])){
		$query .= " and type = '" . DBSafe($_SESSION['type']) . "'";
	}
	$_SESSION['previewmessageid'] = QuickQuery($query);
	redirect();
}

$jobid = isset($_SESSION['previewmessage_jobid']) ? $_SESSION['previewmessage_jobid'] : 0;
$personid = isset($_SESSION['previewmessage_personid']) ? $_SESSION['previewmessage_personid'] : 0;
$messageid = isset($_SESSION['previewmessageid']) ? $_SESSION['previewmessageid'] : 0;
$subtype = QuickQuery("select subtype from message where id=?", false, array($messageid));
$phone = isset($_SESSION['type']) && $_SESSION['type'] == "phone" ? true : false;
$email = isset($_SESSION['type']) && $_SESSION['type'] == "email" ? true : false;
$sms = isset($_SESSION['type']) && $_SESSION['type'] == "sms" ? true : false;

$dopreview = 0;
$fields = array();

// if historic data should be used, fetch it now
if($jobid != 0 && $personid != 0){
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
if ($email || $sms) {
	$parts = DBFindMany('MessagePart', 'from messagepart where messageid=? order by sequence', false, array($messageid));
	if ($email) { //email
		$attachments = DBFindMany("messageattachment","from messageattachment where messageid=?", false, array($messageid));
		if ("html" == $subtype) { // html
			$messagetext = Message::renderEmailHtmlParts($parts, $historicdata);
		} else { // plain
			$messagetext = Message::renderEmailPlainParts($parts, $historicdata);
		}
	} else { // sms
		$messagetext = Message::renderSmsParts($parts);
	}
}



////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$TITLE = _L("Message");

include_once("popup.inc.php");

buttons(button(_L('Done'), 'window.close()'));


startWindow(_L('Details'), 'padding: 3px;');
?>

	<table width="100%" border="0" cellpadding="3" cellspacing="0">
		<tr>
			<th align="right" class="windowRowHeader bottomBorder"><?=_L("Message For")?>:</td>
			<td class="bottomBorder"><?=escapehtml($historicdata['f01']) . " " . escapehtml($historicdata['f02'])?></td>
		</tr>
		<tr>
			<th align="right" class="windowRowHeader bottomBorder"><?=_L("Job Name")?>:</td>
			<td class="bottomBorder"><?=escapehtml($historicdata['jobname'])?></td>
		</tr>
		<tr>
			<th align="right" class="windowRowHeader bottomBorder"><?=_L("Date")?>:</td>
			<td class="bottomBorder"><?=date("M j, Y", strtotime($historicdata['startdate']))?></td>
		</tr>
<?
	if($email && count($attachments)){
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
?>
					<tr>
						<td><a href="messageattachmentdownload.php?pid=<?= $personid ?>&mid=<?= $attachment->id ?>"><?= escapehtml($attachment->filename)?></a></td>
						<td><?= max(1,round($attachment->size/1024)) ?>K</td>
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

if($phone){
	startWindow(_L('Message'), 'padding: 3px;');
	?>

		<div align="center">

		<div id="player"></div>		
		<script type="text/javascript" language="javascript" src="script/niftyplayer.js.php"></script>
		<script language="JavaScript" type="text/javascript">
	 				embedPlayer("preview.wav.php/embed_preview.wav?jid=<?= $jobid ?>&pid=<?= $personid ?>","player");
		</script>
		<br><a href="preview.wav.php/download_preview.wav?jid=<?= $jobid ?>&pid=<?= $personid ?>&download=true"><?=_L("Click here to download")?></a>
		</div>
	<?
	endWindow();
} else if ($email || $sms){
	startWindow(_L('Message'), 'padding: 3px;');
?>
		<div><?= $messagetext ?></div>
<?
	endWindow();
}

include_once("popupbottom.inc.php");

?>