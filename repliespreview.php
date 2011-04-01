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
$query = "select rp.pkey, rp.$firstname, rp.$lastname, rc.phone, j.name, coalesce(mg.name, s.name), vr.replytime, j.status
			from voicereply vr
			inner join job j on (vr.jobid = j.id)
			inner join reportperson rp on(vr.personid = rp.personid and vr.jobid = rp.jobid and rp.type ='phone')
			left join reportcontact rc on (rp.personid = rc.personid and rp.jobid = rc.jobid and rp.type = rc.type and vr.sequence = rc.sequence)
			left join messagegroup mg on (mg.id = j.messagegroupid)
			left join surveyquestionnaire s on (s.id = j.questionnaireid)
			where vr.id = '$vr->id'";
$responses = Query($query);
$responses = DBGetRow($responses);




////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$TITLE = "Reply";

include_once('popup.inc.php');
buttons(button("Done",isset($_GET['close']) ? "window.close()" : "window.history.go(-1)"), button("Delete", "if(confirmDelete()) window.location='repliespreview.php?delete=$vr->id'") );
startWindow('Reply Info', 'padding: 3px;');
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
		<th align="right" class="windowRowHeader bottomBorder">ID#</td>
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
	<div id="player"></div>	
	<script type="text/javascript" language="javascript" src="script/niftyplayer.js.php"></script>
	<script language="JavaScript" type="text/javascript">
		embedPlayer("repliesplay.wav.php/embed_preview.wav?id=<?=$id?>","player");
	</script>
<br><a href="repliesplay.wav.php?id=<?=$id?>&download=true">Click here to download</a>
</div>
<?
endWindow();
include_once('popupbottom.inc.php');

?>
