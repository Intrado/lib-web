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


////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

if(isset($_GET['jobid']) && isset($_GET['personid'])){
	$_SESSION['previewmessage_jobid'] = $_GET['jobid']+0;
	$_SESSION['previewmessage_personid'] = $_GET['personid']+0;
	$_SESSION['previewmessageid'] = QuickQuery("select messageid from reportperson where jobid = '" . $_SESSION['previewmessage_jobid'] . "'
												 and personid = '" . $_SESSION['previewmessage_personid'] . "'");
	redirect();
}

$jobid = isset($_SESSION['previewmessage_jobid']) ? $_SESSION['previewmessage_jobid'] : 0;
$personid = isset($_SESSION['previewmessage_personid']) ? $_SESSION['previewmessage_personid'] : 0;
$messageid = isset($_SESSION['previewmessageid']) ? $_SESSION['previewmessageid'] : 0;

//find all unique fields used in this message

$messagefields = DBFindMany("FieldMap", "from fieldmap where fieldnum in (select distinct fieldnum from messagepart where messageid='$messageid')");

$dopreview = 0;
$fields = array();

// if historic data should be used, fetch it now
if($jobid != 0 && $personid != 0){
	$query = "select rp." . FieldMap::GetFirstNameField() . ", "
			. "rp." . FieldMap::GetLastNameField() . " "
			. generateFields("rp")
			. " from reportperson rp
			where jobid = '$jobid'
			and personid = '$personid'";
	$historicdata = QuickQueryRow($query, true);
}

if (count($messagefields) > 0) {
	$previewdata = "";
	foreach ($messagefields as $fieldmap) {
		$fields[$fieldmap->fieldnum] = $fieldmap;
		$previewdata .= "&$fieldmap->fieldnum=" . urlencode($historicdata[$fieldmap->fieldnum]);
	}
} else {
	$previewdata = "&qt=";
}



////////////////////////////////////////////////////////////////////////////////
// Display Functions
////////////////////////////////////////////////////////////////////////////////


////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$TITLE = "Message Preview";

include_once("popup.inc.php");

buttons(button('Done', 'window.close()'));


if (count($fields) > 0) {
	startWindow('Preview Options', 'padding: 3px;');
?>
	<table border="0" cellpadding="3" cellspacing="0">
<?
	foreach ($fields as $fieldmap) {
		$fieldnum = $fieldmap->fieldnum;
?>
		<tr>
			<th align="right" class="windowRowHeader bottomBorder"><?= $fieldmap->name ?></td>
			<td class="bottomBorder">
<?
		if(isset($historicdata)){
?>
			<div><?=$historicdata[$fieldnum]?></div>
<?
		}
?>
		</td></tr>
<?
	}
?>
	</table>
<?
	endWindow();
}


startWindow('Message Preview', 'padding: 3px;');
?>

	<div align="center">

	<OBJECT ID="MediaPlayer" WIDTH=320 HEIGHT=42
	CLASSID="CLSID:22D6F312-B0F6-11D0-94AB-0080C74C7E95"
	STANDBY="Loading Windows Media Player components..."
	TYPE="application/x-oleobject">

	<PARAM NAME="FileName" VALUE="preview.wav.php/mediaplayer_preview.wav?id=<?= $messageid ?><?= $previewdata ?>">
	<param name="controller" value="true">
	<EMBED SRC="preview.wav.php/embed_preview.wav?id=<?= $messageid ?><?= $previewdata ?>" AUTOSTART="TRUE"></EMBED>
	</OBJECT>


	<br><a href="preview.wav.php/download_preview.wav?id=<?= $messageid ?>&download=true<?= $previewdata ?>">Click here to download</a>
	</div>
<?
endWindow();

include_once("popupbottom.inc.php");

?>