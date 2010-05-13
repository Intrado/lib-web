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



////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

if(isset($_GET['customerid']) && isset($_GET['jobid'])){
	$_SESSION['previewmessage_jobid'] = $_GET['jobid']+0;
	$_SESSION['previewmessage_customerid'] = $_GET['customerid']+0;
	redirect();
}

$custinfo = QuickQueryRow("select s.dbhost, c.dbusername, c.dbpassword, c.urlcomponent, c.enabled from customer c inner join shard s on (c.shardid = s.id) where c.id = '" . $_SESSION['previewmessage_customerid'] ."'");
$_dbcon = DBConnect($custinfo[0], $custinfo[1], $custinfo[2], "c_" . $_SESSION['previewmessage_customerid']);
if (!$_dbcon) {
	exit("Connection failed for customer: $custinfo[0], db: c_" .  $_SESSION['previewmessage_customerid']);
}

$query = "select m.id from job j 
inner join messagegroup mg on (j.messagegroupid = mg.id)
inner join message m on (mg.id = m.messagegroupid and mg.defaultlanguagecode = m.languagecode and m.type = 'phone' and m.autotranslate != 'source')
where j.id =?";

$messageid = QuickQuery($query, false, array($_SESSION['previewmessage_jobid']));


//find all unique fields used in this message
$messagefields = array();
if($messageid)
	$messagefields = DBFindMany("FieldMap", "from fieldmap where fieldnum in (select distinct fieldnum from messagepart where messageid='$messageid')");
else
	$questionnaireid = QuickQuery("select questionnaireid from job j where id = '" . $_SESSION['previewmessage_jobid'] . "'");
	
$dopreview = 0;
$previewdata = "";
$fields = array();
if (count($messagefields) > 0) {
	foreach ($messagefields as $fieldmap){
		$fields[$fieldmap->fieldnum] = $fieldmap;
		$previewdata .= "&$fieldmap->fieldnum=$fieldmap->fieldnum";
	}
	//add something so that QT player doesn't barf if last char is a period
	$previewdata .= "&qt=" . urlencode(" ");
	$dopreview = 1;
	
} else {
	if($messageid){
		$dopreview = 1; //go ahead and preview w/o submiting form if there are no fields in the message
		$previewdata = "&qt=";
	}
}


////////////////////////////////////////////////////////////////////////////////
// Display Functions
////////////////////////////////////////////////////////////////////////////////


////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

if (count($fields) > 0) {
?>
	<div>Fields inserted into message</div>
	<table border="1">
<?
	foreach ($fields as $fieldmap) {
		$fieldnum = $fieldmap->fieldnum;
?>
		<tr>
			<td><?= $fieldmap->name ?>: </td>
			<td align="left"><?= $fieldmap->fieldnum ?></td>
		</tr>
<?
	}
?>
	</table>
<?
}

if ($dopreview) {

?>
<table>
	<tr><td>
	<div align="center">

	<OBJECT ID="MediaPlayer" WIDTH=320 HEIGHT=42
	CLASSID="CLSID:22D6F312-B0F6-11D0-94AB-0080C74C7E95"
	STANDBY="Loading Windows Media Player components..."
	TYPE="application/x-oleobject">

	<PARAM NAME="FileName" VALUE="preview.wav.php/mediaplayer_preview.wav?id=<?= $messageid ?><?= $previewdata ?>&customerid=<?=$_SESSION['previewmessage_customerid']?>">
	<param name="controller" value="true">
	<EMBED SRC="preview.wav.php/embed_preview.wav?id=<?= $messageid ?><?= $previewdata ?>&customerid=<?=$_SESSION['previewmessage_customerid']?>" AUTOSTART="TRUE"></EMBED>
	</OBJECT>


	<br><a href="preview.wav.php/download_preview.wav?id=<?= $messageid ?>&download=true<?= $previewdata ?>&customerid=<?=$_SESSION['previewmessage_customerid']?>">Click here to download</a>
	</div>
	</td></tr>
</table>
<?
} else if($questionnaireid){
	echo "This is a survey job";
}
?>
