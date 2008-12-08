<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
include_once("inc/common.inc.php");
include_once("obj/FieldMap.obj.php");
include_once("inc/form.inc.php");
include_once("inc/html.inc.php");
require_once("inc/table.inc.php");
require_once("inc/utils.inc.php");
require_once("inc/date.inc.php");
require_once("inc/securityhelper.inc.php");
require_once("inc/formatters.inc.php");


////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize("sendphone")) {
	redirect("unauthorized.php");
}

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////
$_SESSION['textpreview'] = false;

if (isset($_GET['id'])) {
	$id = DBSafe($_GET['id']);
	if (userOwns("message", $id)) {
		$_SESSION['previewmessageid'] = $id;
	}
	$_SESSION['ttstext'] = NULL;
	$_SESSION['ttslanguage'] = NULL;
	$_SESSION['ttsgender'] = NULL;
	redirect();
} else if (isset($_GET['text'])&&isset($_GET['language'])&&isset($_GET['gender'])) {
	if(get_magic_quotes_gpc()) {
		$_SESSION['ttstext'] = stripslashes($_GET['text']);
	} else {
		$_SESSION['ttstext'] = $_GET['text'];
	}
	
	$_SESSION['ttslanguage'] = $_GET['language'];
	$_SESSION['ttsgender'] = $_GET['gender'];
	$_SESSION['previewmessageid'] = NULL;
	redirect();
}


$messageid = $_SESSION['previewmessageid'];

$fields = array();
$dopreview = 1; //go ahead and preview w/o submiting form if there are no fields in the message
$previewdata = "&qt=";

if($messageid) {	
	//find all unique fields used in this message
	$messagefields = DBFindMany("FieldMap", "from fieldmap where fieldnum in (select distinct fieldnum from messagepart where messageid='$messageid')");
	if (count($messagefields) > 0) {
		$dopreview = 0;
		foreach ($messagefields as $fieldmap)
			$fields[$fieldmap->fieldnum] = $fieldmap;
	}
}
/****************** main message section ******************/

$f = "messagepreview";
$s = "preview";
$reloadform = 0;

if(CheckFormSubmit($f,$s)) {

	//check to see if formdata is valid
	if(CheckFormInvalid($f))
	{
		error('Form was edited in another window, reloading data.');
		$reloadform = 1;
	}
	else
	{
		MergeSectionFormData($f, $s);

		$previewdata = "";
		foreach ($fields as $fieldmap) {
			$previewdata .= "&$fieldmap->fieldnum=" . urlencode(GetFormData($f,$s,$fieldmap->fieldnum));
		}

		//add something so that QT player doesn't barf if last char is a period
		$previewdata .= "&qt=" . urlencode(" ");
		$dopreview = 1;
	}
} else {
	$reloadform = 1;
}

if ($reloadform) {
	ClearFormData($f);

	if (isset($fields['f01']))
		PutFormData($f,$s,"f01",$USER->firstname);
	if (isset($fields['f02']))
		PutFormData($f,$s,"f02",$USER->lastname);
	if (isset($fields['f03']))
		PutFormData($f,$s,"f03","English");
}


////////////////////////////////////////////////////////////////////////////////
// Display Functions
////////////////////////////////////////////////////////////////////////////////






////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$TITLE = "Message Preview";

include_once("popup.inc.php");

NewForm($f);

buttons(submit($f, $s, $dopreview ? 'Refresh' : 'Play'), button('Done', 'window.close()'));


if (count($fields) > 0) {
	startWindow('Preview Options', 'padding: 3px;');


?>
	<table border="0" cellpadding="3" cellspacing="0" width="100%">
<?
	foreach ($fields as $fieldmap) {
		$fieldnum = $fieldmap->fieldnum;
?>
		<tr>
			<th align="right" class="windowRowHeader bottomBorder"><?= $fieldmap->name ?></td>
			<td class="bottomBorder">
<?
		if($fieldmap->isOptionEnabled("text")) {

			NewFormItem($f,$s,$fieldnum,"text",20,255);

		} elseif($fieldmap->isOptionEnabled("reldate")) {
			NewFormItem($f, $s,$fieldnum, "selectstart");
			foreach($RELDATE_OPTIONS as $name => $value) {
				NewFormItem($f, $s, $fieldnum, "selectoption",$value,reldate($name));
			}
			NewFormItem($f, $s, $fieldnum, "selectend");

		} elseif($fieldmap->isOptionEnabled("multisearch")) {

			$limit = DBFind('Rule', "from rule inner join userrule on rule.id = userrule.ruleid where userid = $USER->id and fieldnum = '$fieldnum'");
			//FIXME whats wrong with the SQL generating code in Rule.obj?
			$limitsql = $limit ? "and value in ('" . implode("','", explode('|', $limit->val)) . "')" : NULL;
			$query = "select value from persondatavalues
						where fieldnum='$fieldnum' $limitsql order by value";
			$values = QuickQueryList($query);
			if (count($values) > 1) {
				if (!GetFormData($f,$s,$fieldnum))
					PutFormData($f,$s,$fieldnum,$values[0],"array");
				NewFormSelect($f,$s, $fieldnum,@array_combine($values,$values));

			} else {
				NewFormSelect($f,$s, $fieldnum,@array_combine($values,$values));
			}
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

if ($dopreview) {
	startWindow('Message Preview', 'padding: 3px;');
?>

	<div align="center">

	<OBJECT ID="MediaPlayer" WIDTH=320 HEIGHT=42
	CLASSID="CLSID:22D6F312-B0F6-11D0-94AB-0080C74C7E95"
	STANDBY="Loading Windows Media Player components..."
	TYPE="application/x-oleobject">

	<PARAM NAME="FileName" VALUE="preview.wav.php/mediaplayer_preview.wav?id=<?= $messageid ?><?= $previewdata ?>">
	<param name="controller" value="true">
	
	<? if ($_SESSION['ttstext']) { ?>
		<EMBED SRC="preview.wav.php/embed_preview.wav?text=<?= $_SESSION['ttstext'] ?>&language=<?= $_SESSION['ttslanguage']  ?>&gender=<?=	$_SESSION['ttsgender'] ?><?= $previewdata ?>" AUTOSTART="TRUE"></EMBED>	
	<? } else {?>
		<EMBED SRC="preview.wav.php/embed_preview.wav?id=<?= $messageid ?><?= $previewdata ?>" AUTOSTART="TRUE"></EMBED>
	<? } ?>
	</OBJECT>

	<? if ($_SESSION['ttstext']) { ?>
		<br><a href="preview.wav.php/download_preview.wav?text=<?= $_SESSION['ttstext'] ?>&language=<?= $_SESSION['ttslanguage'] ?>&gender=<?= $_SESSION['ttsgender'] ?>&download=true<?= $previewdata ?>">Click here to download</a>	
	<? } else {?>
		<br><a href="preview.wav.php/download_preview.wav?id=<?= $messageid ?>&download=true<?= $previewdata ?>">Click here to download</a>
	<? } ?>
	</div>
<?
	endWindow();
}



EndForm($f);

include_once("popupbottom.inc.php");
