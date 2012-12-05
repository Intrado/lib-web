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
require_once("obj/Content.obj.php");
require_once("obj/AudioFile.obj.php");
require_once("obj/Voice.obj.php");
require_once("obj/Message.obj.php");
require_once("obj/MessagePart.obj.php");
require_once("obj/AudioFile.obj.php");
require_once("obj/Language.obj.php");
require_once("obj/MessageGroup.obj.php");


require_once("inc/previewfields.inc.php");

////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////
if (isset($_GET['unloadsession'])) {
	unset($_SESSION['ttstext']);
	unset($_SESSION['ttsgender']);
	unset($_SESSION['ttslanguage']);
	exit();
}

$id = false;
if (isset($_GET['id'])) {
	$_SESSION['ttstext'] = false;
	$id = $_GET['id'] + 0;
	// if its a valid message, and the user has rights to listen to it
	$message = DBFind("Message", "from message where id = ?", false, array($id));
	if($message && (userOwns("message", $id) || $USER->authorize('managesystem') || (isPublished("messagegroup", $message->messagegroupid) && userCanSubscribe("messagegroup", $message->messagegroupid)))) {
		//find all unique fields and values used in this message

		list($fields,$fielddata,$fielddefaults) = getpreviewfieldmapdata($id);
		$msgType = QuickQuery("select type from message where id=?", false, array($id));
	}
}

// First Text Request - See Display section

// Second Text Request - Set the session data parse text to find out fields and return and return the form  
if (isset($_POST['text'])) {
	if (isset($_POST['gender'])) {
		if(get_magic_quotes_gpc())
			$_SESSION['ttsgender'] = stripslashes($_POST['gender']);
		else
			$_SESSION['ttsgender'] = $_POST['gender'];
	} else
		$_SESSION['ttsgender'] = "female";
		
	if (isset($_POST['language'])) {
		if(get_magic_quotes_gpc())
			$_SESSION['ttslanguage'] = stripslashes($_POST['language']);
		else
			$_SESSION['ttslanguage'] = $_POST['language'];
	} else 
		$_SESSION['ttslanguage'] = "en";
			
	if(get_magic_quotes_gpc())
		$_SESSION['ttstext'] = stripslashes($_POST['text']);
	else
		$_SESSION['ttstext'] = $_POST['text'];	
} 

if(isset($_GET['parentfield']) || $id || isset($_GET['mediafile'])) {
	$_SESSION['ttstext'] = false;
}

// Third Text Request - Session data is already set but nee to parse again. Set fields and return form again 
if($_SESSION['ttstext']) {
	
	//load all audiofileids for this messagegroup, should be OK since only way to preview via text parse is inside MG editor
	$audiofileids = null;
	if (isset($_SESSION['messagegroupid']))
		$audiofileids = MessageGroup::getReferencedAudioFileIDs($_SESSION['messagegroupid']);
	else
		error_log("ERROR: preview.wav.php called on text with no messagegroupid");
	
	$message = new Message();
	$parts = $message->parse($_SESSION['ttstext'],$errors,1,$audiofileids);
	$fieldnums = array();
	$fielddefaults = array();
	
	foreach($parts as $part) {
		if(isset($part->fieldnum)) {
			$fieldnums[] = $part->fieldnum;
			$fielddefaults[$part->fieldnum] = $part->defaultvalue;
		}
	}	
	
	$messagefields = DBFindMany("FieldMap", "from fieldmap where fieldnum in ('" . implode("','",$fieldnums) .  "')");
	
	$fields = array();
	$fielddata = array();
	
	foreach ($messagefields as $fieldmap) {		
		$fields[$fieldmap->fieldnum] = $fieldmap;
		if ($fieldmap->isOptionEnabled("multisearch")) {
			$limit = DBFind('Rule', "from rule r inner join userassociation ua on r.id = ua.ruleid where ua.userid=? and type = 'rule' and r.fieldnum=?", "r", array($USER->id, $fieldmap->fieldnum));
			$limitsql = $limit ? $limit->toSQL(false, 'value', false, true) : '';
			$fielddata[$fieldmap->fieldnum] = QuickQueryList("select value,value from persondatavalues where fieldnum=? $limitsql order by value limit 5000", true, false, array($fieldmap->fieldnum));
		}
	}
	$msgType = 'phone';	
} 

if (!isset($msgType) || !$msgType)
	$msgType = 'phone';
	
	
if (!$id && !isset($_SESSION['ttstext']) && !isset($_GET['parentfield']) && !isset($_GET['mediafile']) ) {
	redirect("unauthorized.php");
}

////////////////////////////////////////////////////////////////////////////////
// Form Data
////////////////////////////////////////////////////////////////////////////////
$formdata = (isset($fields) && count($fields) && $msgType == 'phone')?getpreviewformdata($fields,$fielddata,$fielddefaults,$msgType):array();

$buttons = array();
if ($msgType == 'phone')
	$buttons[] = submit_button(_L('Play with Field(s)'),"submit","fugue/control");
	
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
				$request = ($id)?"id=$id":(isset($_SESSION['ttstext'])?"usetext=true":"blank=true");	
				$form->modifyElement("messageresultdiv", '
						<script language="JavaScript" type="text/javascript">
							embedPlayer("preview.wav.php/embed_preview.wav?' . $request . $previewdata. '","player");
							$("download").update(\'<a href="preview.wav.php/download_preview.wav?'  . $request .  $previewdata . '&download=true" onclick="sessiondata=false;">' . _L("Click here to download") . '</a>\');
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

// First Request - Initial request for text preview - grab parent window field text and execute a post request to set session data and get field form data
if (isset($_GET['parentfield'])) {
	$parentfield = $_GET['parentfield'];
	if (isset($_GET['gender']) && $_GET['gender'] != "") {
		$gender = $_GET['gender'];
	} else {
		$gender = "female";
	}
	if (isset($_GET['language']) && $_GET['language'] != "") {
		$language = $_GET['language'];
	} else {
		$language = "en";
	}
	
	require_once("popup.inc.php");
	startWindow(_L("Message Preview"));?>
	<script language="JavaScript" type="text/javascript">
		var sessiondata = true;
	</script>
	<div id="previewcontainer"></div>
	<script type="text/javascript" language="javascript" src="script/datepicker.js"></script>
	<script type="text/javascript" language="javascript" src="script/niftyplayer.js.php"></script>
	<script language="JavaScript" type="text/javascript">
				function unloadsession(){
					if(sessiondata == true){
						window.location = 'previewmessage.php?unloadsession=true'; // sends a request to unload session. cannot use ajax during unload
					}
					sessiondata = true;
				}

				var gender = "<?=$gender ?>";
				var language = "<?=$language ?>";					
				var parentfield = '<?= $parentfield?>';
				var textobj = null; // Cannot get prototype element accross window opener 
				if (window.opener.document.getElementById) {
					textobj = window.opener.document.getElementById(parentfield);
				} else if (window.opener.document.all) {
					textobj = window.opener.document.all[parentfield];
  				} else if (window.opener.document.layers) {
  					textobj = window.opener.document.layers[parentfield];
 				} 
 				if(!textobj || !textobj.value) {
 					$('previewcontainer').update("Unable to playback. Please try again later."); 				
 				} else {
					new Ajax.Request('previewmessage.php', {
						method:'post',
					    parameters: {text: textobj.value, gender: gender, language: language},
						onSuccess: function (result) {							
					    	$('previewcontainer').update(result.responseText);
					    },
					    onFailure: function(){
					    	$('previewcontainer').update("Unable to playback. Please try again later.");
						}
					});			
 				}
	</script>
	<? 
	endWindow();
	require_once("popupbottom.inc.php");
	exit();
} else {
	if($_SESSION['ttstext']) {
		$request = "usetext=true";
	} else {
		if($id) {
			$request = "id=$id";
		} else if (isset($_GET['mediafile'])) {
			$request = "mediafile=" . $_GET['mediafile'];
		} else {
			$request = "blank=true";
		}
		require_once("popup.inc.php");
		?>
		<script language="JavaScript" type="text/javascript">
			var sessiondata = true;
		</script>
		<script type="text/javascript" language="javascript" src="script/niftyplayer.js.php"></script>
		<?
		startWindow(_L("Message Preview"));	
	}

	if (count($formdata)) 
		echo $form->render();
	
	$hasdata = count($formdata);	
	?>
	<div id="messagepreviewdiv" name="messagepreviewdiv">
			<div align="center" style="clear:left">
				<div id="player"></div>		
	<? 
	// If there is no formdata (no field inserts) then just play the message
	if (!$hasdata) {?>
				<script language="JavaScript" type="text/javascript">
	 				embedPlayer("preview.wav.php/embed_preview.wav?<?= $request ?>","player");
				</script>
<?	} ?>
				<div id='download'>
<?	if (!$hasdata) {?> 		
				<a href="preview.wav.php/download_preview.wav?<?= $request ?>&download=true" onclick="sessiondata=false;"><?=_L("Click here to download")?></a>
<?	} ?>		
				</div>
			</div>
	</div>
	<div id="messageresultdiv" name="messageresultdiv"></div>
<? 
	if(!$_SESSION['ttstext']) {
		endWindow();
		require_once("popupbottom.inc.php");
	} 

}
?>
	
