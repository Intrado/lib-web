<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("inc/common.inc.php");
require_once("inc/securityhelper.inc.php");
require_once("inc/table.inc.php");
require_once("inc/html.inc.php");
require_once("inc/utils.inc.php");
require_once("obj/Validator.obj.php");
require_once("obj/Form.obj.php");
require_once("obj/FormItem.obj.php");
require_once("inc/formatters.inc.php");
require_once("obj/FieldMap.obj.php");
require_once("obj/MessagePart.obj.php");
require_once("obj/AudioFile.obj.php");

////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize("sendphone")) {
	redirect("unauthorized.php");
}

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////
if (isset($_GET['id'])) {
	if (userOwns("message", $_GET['id'] + 0) || $USER->authorize('managesystem'))
		$id = $_GET['id'] + 0;
} else 
	$id = false;

if (isset($_GET['text'])) {
	if(get_magic_quotes_gpc())
		$ttstext = stripslashes($_GET['text']);
	else
		$ttstext = $_GET['text'];
} else
	$ttstext = false;

if (isset($_GET['language'])) {
	if(get_magic_quotes_gpc())
		$ttslanguage = stripslashes($_GET['language']);
	else
		$ttslanguage = $_GET['language'];
} else 
	$ttslanguage = "english";

if (isset($_GET['gender'])) {
	if(get_magic_quotes_gpc())
		$ttsgender = stripslashes($_GET['gender']);
	else
		$ttsgender = $_GET['gender'];
} else
	$ttsgender = "female";

if (!$id && !$ttstext)
	redirect("unauthorized.php");

if ($id) {
	//find all unique fields and values used in this message
	$messagefields = DBFindMany("FieldMap", "from fieldmap where fieldnum in (select distinct fieldnum from messagepart where messageid=?)", false, array($id));
	if (count($messagefields) > 0) {
		$fields = array();
		$fielddata = array();
		foreach ($messagefields as $fieldmap) {
			$fields[$fieldmap->fieldnum] = $fieldmap;
			if ($fieldmap->isOptionEnabled("multisearch")) {
				$limit = DBFind('Rule', 'from rule inner join userrule on rule.id = userrule.ruleid where userid=? and fieldnum=?', false, array($USER->id, $fieldmap->fieldnum));
				$limitsql = $limit ? $limit->toSQL(false, 'value', false, true) : '';
				$fielddata[$fieldmap->fieldnum] = QuickQueryList("select value from persondatavalues where fieldnum=? $limitsql order by value", false, false, array($fieldmap->fieldnum));
			}
		}
		// Get message parts so we can find the default values, if specified in the message
		$messageparts = DBFindMany("MessagePart", "from messagepart where messageid = ? and type = 'V'", false, array($id));
		$fielddefaults = array();
		foreach ($messageparts as $messagepart)
			$fielddefaults[$messagepart->fieldnum] = $messagepart->defaultvalue;
	}
}

class FormHtmlWithId extends FormItem {
	function render ($value) {
		return '<div id="'.$this->args['id'].'" name="'.$this->args['id'].'">'.$this->args['html'].'</div>';
	}
}

////////////////////////////////////////////////////////////////////////////////
// Form Data
////////////////////////////////////////////////////////////////////////////////
$formdata = array();

if ($id && isset($fields) && count($fields)) {
	foreach ($fields as $field => $fieldmap) {
		if ($fieldmap->isOptionEnabled("firstname")) {
			$formdata[$field] = array (
				"label" => $fieldmap->name,
				"value" => $USER->firstname,
				"validators" => array(),
				"control" => array("TextField", "maxlength" => 50, "size"=>20),
				"helpstep" => 1
			);
		} else if ($fieldmap->isOptionEnabled("lastname")) {
			$formdata[$field] = array (
				"label" => $fieldmap->name,
				"value" => $USER->lastname,
				"validators" => array(),
				"control" => array("TextField", "maxlength" => 50, "size"=>20),
				"helpstep" => 1
			);
		} else if ($fieldmap->isOptionEnabled("multisearch")) {
			$formdata[$field] = array (
				"label" => $fieldmap->name,
				"value" => $fielddefaults[$field],
				"validators" => array(),
				"control" => array("SelectMenu", "values" => $fielddata[$fieldmap->fieldnum]),
				"helpstep" => 1
			);
		} else if ($fieldmap->isOptionEnabled("reldate")) {
			$formdata[$field] = array (
				"label" => $fieldmap->name,
				"value" => $fielddefaults[$field],
				"validators" => array(),
				"control" => array("TextDate", "size"=>12),
				"helpstep" => 1
			);
		} else {
			$formdata[$field] = array (
				"label" => $fieldmap->name,
				"value" => $fielddefaults[$field],
				"validators" => array(),
				"control" => array("TextField", "maxlength" => 20, "size"=>20),
				"helpstep" => 1
			);
		}
	}
}

$buttons = array(submit_button(_L('Play'),"submit","play"), icon_button(_L('Close'),"cross","window.close()",null,null));

// Only display and handle form elements if there are form elements.
if (count($formdata)) {
	$form = new Form("messagepreview",$formdata,array(),$buttons);


	////////////////////////////////////////////////////////////////////////////////
	// Data Handling
	////////////////////////////////////////////////////////////////////////////////

	//check and handle an ajax request (will exit early)
	//or merge in related post data
	$form->handleRequest();

	$datachange = false;
	$errors = false;
	//check for form submission
	if ($button = $form->getSubmit()) { //checks for submit and merges in post data
		if ($form->checkForDataChange()) {
			$datachange = true;
		} else if (($errors = $form->validate()) === false) { //checks all of the items in this form
			$postdata = $form->getData(); //gets assoc array of all values {name:value,...}
			$previewdata = "";
			foreach ($postdata as $field => $value) {
				$previewdata .= "&$field=" . urlencode($value);
			}
			$form->modifyElement("messagepreviewdiv", '
				<div align="center">
					<OBJECT ID="MediaPlayer" WIDTH=320 HEIGHT=42
					CLASSID="CLSID:22D6F312-B0F6-11D0-94AB-0080C74C7E95"
					STANDBY="Loading Windows Media Player components..."
					TYPE="application/x-oleobject">
					<PARAM NAME="FileName" VALUE="preview.wav.php/mediaplayer_preview.wav?id='.$id.$previewdata.'">
					<param name="controller" value="true">
					<EMBED SRC="preview.wav.php/embed_preview.wav?id='.$id.$previewdata.'" AUTOSTART="TRUE"></EMBED>
					</OBJECT>
					<br><a href="preview.wav.php/download_preview.wav?id='.$id.$previewdata.'&download=true">'._L("Click here to download").'</a>
				</div>'
			);
			</script>
			<?
			return;
		}
	}
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
require_once("popup.inc.php");
// TODO: This script should be included on the form item. Currently that breaks it though. We need a new calender, maybe one that is written in prototype syntax.
?><script SRC="script/calendar.js"></script><?

startWindow(_L("Message Preview"));
if (count($formdata)) echo $form->render();
?><div id="messagepreviewdiv" name="messagepreviewdiv"><?
// If there is no formdata (no field inserts) then just play the message
if (!count($formdata)) {?>
	<div style="float:left; margin: 5px">
		<?=icon_button(_L('Close'),"cross","window.close()",null,null)?>
	</div>
	<div align="center" style="clear:left">
		<OBJECT ID="MediaPlayer" WIDTH=320 HEIGHT=42
		CLASSID="CLSID:22D6F312-B0F6-11D0-94AB-0080C74C7E95"
		STANDBY="Loading Windows Media Player components..."
		TYPE="application/x-oleobject">
		<PARAM NAME="FileName" VALUE="preview.wav.php/mediaplayer_preview.wav?<?=($id)?"id=$id":"text=".urlencode($ttstext)."&language=$ttslanguage&gender=$ttsgender"?>">
		<param name="controller" value="true">
		<EMBED SRC="preview.wav.php/embed_preview.wav?<?=($id)?"id=$id":"text=".urlencode($ttstext)."&language=$ttslanguage&gender=$ttsgender"?>" AUTOSTART="TRUE"></EMBED>
		</OBJECT>
		<br><a href="preview.wav.php/download_preview.wav?<?=($id)?"id=$id":"text=".urlencode($ttstext)."&language=$ttslanguage&gender=$ttsgender"?>&download=true"><?=_L("Click here to download")?></a>
	</div>
<?}
?></div><?
endWindow();

require_once("popupbottom.inc.php");
?>
