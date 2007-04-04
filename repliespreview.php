<?
include_once("inc/common.inc.php");
include_once("inc/html.inc.php");
include_once("inc/table.inc.php");
include_once("inc/formatters.inc.php");
include_once("obj/FieldMap.obj.php");
include_once("obj/VoiceReply.obj.php");
include_once("obj/Message.obj.php");

$id = $_GET['id']+0;
$vr = new VoiceReply($id);
$vr->listened = 1;
$vr->update();

$firstname = FieldMap::getFirstNameField();
$lastname = FieldMap::getLastNameField();
$query = "select p.pkey, pd.$firstname, pd.$lastname, jt.phone, j.name, coalesce(m.name, s.name), vr.replytime, j.status
			from job j 
			inner join voicereply vr on (vr.jobid = j.id)
			left join jobtask jt on (jt.id = vr.jobtaskid)
			left join persondata pd on (pd.personid = vr.personid)
			left join person p on (p.id = vr.personid)
			left join message m on (m.id = j.phonemessageid)
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

button("done",isset($_GET['close']) ? "window.close()" : "window.history.go(-1)"); 

?>
<table border="0" cellpadding="3" cellspacing="0">
	<tr>
		<th align="right" class="windowRowHeader bottomBorder">Job</td>
		<td class="bottomBorder"><?=$responses[4]?></td>
	</tr>
	<tr>
		<th align="right" class="windowRowHeader bottomBorder">Message</td>
		<td class="bottomBorder"><?=$responses[5]?></td>

	</tr>
	<tr>
		<th align="right" class="windowRowHeader bottomBorder">Person ID</td>
		<td class="bottomBorder"><?=$responses[0]?></td>
	</tr>
	<tr>
		<th align="right" class="windowRowHeader bottomBorder">First Name</td>
		<td class="bottomBorder"><?=$responses[1]?></td>
	</tr>
	<tr>
		<th align="right" class="windowRowHeader bottomBorder">Last Name</td>
		<td class="bottomBorder"><?=$responses[2]?></td>
	</tr>
	<tr>
		<th align="right" class="windowRowHeader bottomBorder">Phone</td>
		<td class="bottomBorder"><?=fmt_phone($responses, 3)?></td>
	</tr>
	<tr>
		<th align="right" class="windowRowHeader bottomBorder">Reply Date</td>
		<td class="bottomBorder"><?=fmt_unix_ms_timestamp($responses, 6)?></td>
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
