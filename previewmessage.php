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
require_once("obj/AudioFile.obj.php");
require_once("obj/Voice.obj.php");
require_once("obj/Message.obj.php");
require_once("obj/MessagePart.obj.php");
require_once("obj/AudioFile.obj.php");
require_once("obj/FormSelectMessage.fi.php");

////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

if (isset($_GET['id'])) {
	if (userOwns("message", $_GET['id'] + 0) || $USER->authorize('managesystem'))
		$id = $_GET['id'] + 0;
} else 
	$id = false;

if (isset($_POST['text'])) {
	if(get_magic_quotes_gpc())
		$_SESSION['ttstext'] = stripslashes($_POST['text']);
	else
		$_SESSION['ttstext'] = $_POST['text'];
} else
	$_SESSION['ttstext'] = false;

if (isset($_POST['language'])) {
	if(get_magic_quotes_gpc())
		$_SESSION['ttslanguage'] = stripslashes($_POST['language']);
	else
		$_SESSION['ttslanguage'] = $_POST['language'];
} else 
	$_SESSION['$ttslanguage'] = "english";

if (isset($_POST['gender'])) {
	if(get_magic_quotes_gpc())
		$_SESSION['ttsgender'] = stripslashes($_POST['gender']);
	else
		$_SESSION['ttsgender'] = $_POST['gender'];
} else
	$_SESSION['ttsgender'] = "female";

	
if (!$id && !$_SESSION['ttstext']) {
	redirect("unauthorized.php");
}
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
				$fielddata[$fieldmap->fieldnum] = QuickQueryList("select value,value from persondatavalues where fieldnum=? $limitsql order by value", true, false, array($fieldmap->fieldnum));
			}
		}
		// Get message parts so we can find the default values, if specified in the message
		$messageparts = DBFindMany("MessagePart", "from messagepart where messageid = ? and type = 'V'", false, array($id));
		$fielddefaults = array();
		foreach ($messageparts as $messagepart)
			$fielddefaults[$messagepart->fieldnum] = $messagepart->defaultvalue;
	}
	$msgType = QuickQuery("select type from message where id=?", false, array($id));
} else if($_SESSION['ttstext']) {
	$voiceid = false;
	if($_SESSION['ttsgender'] == "Female") {
		$voiceid = QuickQuery("select id from ttsvoice where language=? and gender='Male'",false,array($language));
	} else if($_SESSION['ttsgender'] == "Male") {
		$voiceid = QuickQuery("select id from ttsvoice where language=? and gender='Female'",false,array($language));	
	}
	if($voiceid	=== false)
		$voiceid = 2; // default to english	female
	$message = new Message();
	$parts = $message->parse($_SESSION['ttstext'],$errors,$voiceid);
	$fieldnums = array();
	$fielddefaults = array();
	
	foreach($parts as $part) {
		if(isset($part->fieldnum)) {
			$fieldnums[] = $part->fieldnum;
			$fielddefaults[$part->fieldnum] = $part->defaultvalue;
		}
	}	
	$messagefields = DBFindMany("FieldMap", "from fieldmap where fieldnum in (" . implode(",",$fieldnums) .  ")");
	$fields = array();
	$fielddata = array();
	foreach ($messagefields as $fieldmap) {
		$fields[$fieldmap->fieldnum] = $fieldmap;
		if ($fieldmap->isOptionEnabled("multisearch")) {
			$limit = DBFind('Rule', 'from rule inner join userrule on rule.id = userrule.ruleid where userid=? and fieldnum=?', false, array($USER->id, $fieldmap->fieldnum));
			$limitsql = $limit ? $limit->toSQL(false, 'value', false, true) : '';
			$fielddata[$fieldmap->fieldnum] = QuickQueryList("select value,value from persondatavalues where fieldnum=? $limitsql order by value", true, false, array($fieldmap->fieldnum));
		}
	}
	$msgType = 'phone';	
}


if (!isset($msgType) || !$msgType)
	$msgType = 'phone';
	
class FormHtmlWithId extends FormItem {
	function render ($value) {
		return '<div id="'.$this->args['id'].'" name="'.$this->args['id'].'">'.$this->args['html'].'</div>';
	}
}

////////////////////////////////////////////////////////////////////////////////
// Form Data
////////////////////////////////////////////////////////////////////////////////
$formdata = array();

if ($id && isset($fields) && count($fields) && $msgType == 'phone') {
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

if ($msgType == 'email' || $msgType == 'sms')
	$formdata['preview'] = array(
		"label" => 'Preview',
		"value" => $id,
		"validators" => array(),
		"control" => array("SelectMessage", "type"=>$msgType, "width"=>"100%", "readonly"=>true, "values"=>array($id => array("name" => ""))),
		"helpstep" => 1
	);

$buttons = array();
if ($msgType == 'phone')
	$buttons[] = submit_button(_L('Refresh Field(s)'),"submit","fugue/arrow_circle_double_135");
	
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
			if ($msgType == 'phone') {
				$request = ($id)?"id=$id":(isset($_POST['text'])?"usetext=true":"blank=true");
				$form->modifyElement("messageresultdiv", '
						<script language="JavaScript" type="text/javascript">
							embedPlayer("preview.wav.php/embed_preview.wav?' . $request . $previewdata. '","player",true);
							$("download").update(\'<a href="_preview.wav.php/download_preview.wav?' . $request.$previewdata . '&download=true">' . _L("Click here to download") . '</a>\');
						</script>
						'
				);
			}
			return;
		}
	}
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

require_once("popup.inc.php");

startWindow(_L("Message Preview"));
if (count($formdata)) 
	echo $form->render();
if ($msgType == 'phone') {
	$request = ($id)?"id=$id":(isset($_POST['text'])?"usetext=true":"blank=true");

?>
<script type="text/javascript" language="javascript" src="script/niftyplayer.js"></script>

<div id="messagepreviewdiv" name="messagepreviewdiv"><?
// If there is no formdata (no field inserts) then just play the message
//if (!count($formdata)) {?>
		<div align="center" style="clear:left">
			<div id="player"></div>		
			<script language="JavaScript" type="text/javascript">
  				embedPlayer("preview.wav.php/embed_preview.wav?<?=$request?>","player",false); 
			</script>
			<div id='download'><a href="preview.wav.php/download_preview.wav?<?=$request?>&download=true"><?=_L("Click here to download")?></a></div>
		</div>
<?
//}
?></div>
<div id="messageresultdiv" name="messageresultdiv">
</div>
<?
}
endWindow();

require_once("popupbottom.inc.php");
?>
