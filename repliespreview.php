<?

include_once("inc/common.inc.php");
include_once("obj/Phone.obj.php");
include_once("inc/html.inc.php");
include_once("inc/table.inc.php");
include_once("inc/formatters.inc.php");
include_once("obj/FieldMap.obj.php");
include_once("obj/VoiceReply.obj.php");
include_once("obj/Message.obj.php");
include_once("inc/securityhelper.inc.php");

if(isset($_GET['delete'])){
	$delete = $_GET['delete']+0;
	if(!userOwns("voicereply", $delete)){
		redirect('unauthorized.php');
	}
	$vr = new VoiceReply($delete);
	$vr->destroy();
	?><script>
			window.opener.document.location.reload();
			window.close();
	</script><?
}

$id = 0;
if(isset($_GET['id'])){
	$id = $_GET['id']+0;
	if(!userOwns("voicereply", $id)){
		redirect('unauthorized.php');
	}
}
$vr = new VoiceReply($id);
$vr->listened = 1;
$vr->update();



$firstname = FieldMap::getFirstNameField();
$lastname = FieldMap::getLastNameField();
$query = "select rp.pkey, rp.$firstname, rp.$lastname, rc.phone, j.name, coalesce(m.name, s.name), vr.replytime, j.status
			from voicereply vr
			inner join job j on (vr.jobid = j.id)
			inner join reportperson rp on(vr.personid = rp.personid and vr.jobid = rp.jobid and rp.type ='phone')
			left join reportcontact rc on (rp.personid = rc.personid and rp.jobid = rc.jobid and rp.type = rc.type and vr.sequence = rc.sequence)
			left join message m on (m.id = rp.messageid)
			left join surveyquestionnaire s on (s.id = j.questionnaireid)
			where vr.id = '$vr->id'";
$responses = Query($query);
$responses = DBGetRow($responses);




////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$TITLE = "Reply";

include_once('popup.inc.php');
startWindow('Reply Info', 'padding: 3px;');

buttons(button("Done",isset($_GET['close']) ? "window.close()" : "window.history.go(-1)"), button("Delete", "if(confirmDelete()) window.location='repliespreview.php?delete=$vr->id'") );

?>
<table border="0" cellpadding="3" cellspacing="0">
	<tr>
		<th align="right" class="windowRowHeader bottomBorder">Job</td>
		<td class="bottomBorder"><?=$responses[4] ? $responses[4] : "&nbsp"?></td>
	</tr>
	<tr>
		<th align="right" class="windowRowHeader bottomBorder">Message</td>
		<td class="bottomBorder"><?=$responses[5] ? $responses[5] : "&nbsp"?></td>

	</tr>
	<tr>
		<th align="right" class="windowRowHeader bottomBorder">Person ID</td>
		<td class="bottomBorder"><?=$responses[0] ? $responses[0] : "&nbsp" ?></td>
	</tr>
	<tr>
		<th align="right" class="windowRowHeader bottomBorder">First Name</td>
		<td class="bottomBorder"><?=$responses[1] ? $responses[1] : "&nbsp"?></td>
	</tr>
	<tr>
		<th align="right" class="windowRowHeader bottomBorder">Last Name</td>
		<td class="bottomBorder"><?=$responses[2] ? $responses[2] : "&nbsp"?></td>
	</tr>
	<tr>
		<th align="right" class="windowRowHeader bottomBorder">Phone</td>
		<td class="bottomBorder"><?=Phone::format($responses[3])?></td>
	</tr>
	<tr>
		<th align="right" class="windowRowHeader bottomBorder">Reply Date</td>
		<td class="bottomBorder"><?=fmt_ms_timestamp($responses, 6)?></td>
	</tr>
</table>

<div align="center">

<OBJECT ID="MediaPlayer" WIDTH=320 HEIGHT=42
CLASSID="CLSID:22D6F312-B0F6-11D0-94AB-0080C74C7E95"
STANDBY="Loading Windows Media Player components..."
TYPE="application/x-oleobject">

<PARAM NAME="FileName" VALUE="repliesplay.wav.php/mediaplayer_preview.wav?id=<? print $_GET['id']; ?>">
<param name="controller" value="true">
<EMBED SRC="repliesplay.wav.php/embed_preview.wav?id=<? print $_GET['id']; ?>" AUTOSTART="TRUE"></EMBED>
</OBJECT>


<br><a href="repliesplay.wav.php/download_preview.wav?id=<? print $_GET['id']; ?>&download=true">Click here to download</a>
</div>
<?
endWindow();
include_once('popupbottom.inc.php');

?>
