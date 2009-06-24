<?
require_once("inc/common.inc.php");


////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

$maxattachmentsize = 2 * 1024 * 1024; //2m
$unsafeext = array(".ade",".adp",".asx",".bas",".bat",".chm",".cmd",".com",".cpl",
	".crt",".dbx",".exe",".hlp",".hta",".inf",".ins",".isp",".js",".jse",".lnk",
	".mda",".mdb",".mde",".mdt",".mdw",".mdz",".mht",".msc",".msi",".msp",".mst",
	".nch",".ops",".pcd",".pif",".prf",".reg",".scf",".scr",".sct",".shb",".shs",
	".url",".vb",".vbe",".vbs",".wms",".wsc",".wsf",".wsh",".zip",".dmg",".app");

//get the message to edit from the request params or session
if (isset($_GET['id'])) {
	if($_GET['id'] == "new")
		$_SESSION['messageid'] = NULL;
	else
		setCurrentMessage($_GET['id']);

	//if we did have a temp uploaded file, delete it.
	if (isset($_SESSION['emailattachment'])) {
		QuickUpdate("delete from content where id=" . $_SESSION['emailattachment']['cmid']);
		unset($_SESSION['emailattachment']);
	}

	redirect("message" . $MESSAGETYPE . ".php");
}

$attachments = array(); //id -> obj map
if ($_SESSION['messageid'])
	$attachments = DBFindMany("messageattachment","from messageattachment where not deleted and messageid=" . DBSafe($_SESSION['messageid']));

/****************** main message section ******************/

$dopreview = 0;
$form = "message";
$section = "main". $MESSAGETYPE;
$reloadform = 0;


if(CheckFormSubmit($form,$section) || CheckFormSubmit($form,"upload") || CheckFormSubmit($form,"delete") !== false)
{
	//get any uploaded file and put in session queue (use session in case there is a form error)
	$uploaderror = false;
	if (isset($_FILES['emailattachment']['error']) && $_FILES['emailattachment']['error'] != UPLOAD_ERR_OK) {
		switch($_FILES['emailattachment']['error']) {
		case UPLOAD_ERR_INI_SIZE:
		case UPLOAD_ERR_FORM_SIZE:
			error('The file you uploaded exceeds the maximum email attachment limit of 2048K');
			$uploaderror = true;
			break;
		case UPLOAD_ERR_PARTIAL:
			error('The file upload did not complete','Please try again','If the problem persists, please check your network settings');
			$uploaderror = true;
			break;
		case UPLOAD_ERR_NO_FILE:
			if (CheckFormSubmit($form,"upload")) {
				error("Please select a file to upload");
				$uploaderror = true;
			}
			break;
		case UPLOAD_ERR_NO_TMP_DIR:
		case UPLOAD_ERR_CANT_WRITE:
		case UPLOAD_ERR_EXTENSION:
			error('Unable to complete file upload. Please try again');
			$uploaderror = true;
			break;
		}
	} else if(isset($_FILES['emailattachment']) && $_FILES['emailattachment']['tmp_name']) {

		$newname = secure_tmpname("emailattachment",".dat");

		$filename = $_FILES['emailattachment']['name'];
		$extdotpos = strrpos($filename,".");
		if ($extdotpos !== false)
			$ext = substr($filename,$extdotpos);

		$mimetype = $_FILES['emailattachment']['type'];

		$uploaderror = true;
		if(!move_uploaded_file($_FILES['emailattachment']['tmp_name'],$newname)) {
			error('Unable to complete file upload. Please try again');
		} else if (!is_file($newname) || !is_readable($newname)) {
			error('Unable to complete file upload. Please try again');
		} else if (array_search(strtolower($ext),$unsafeext) !== false) {
			error('The file you uploaded may pose a security risk and is not allowed' , 'Please check the help documentation for more information on safe and unsafe file types');
		} else if ($_FILES['emailattachment']['size'] >= $maxattachmentsize) {
			error('The file you uploaded exceeds the maximum email attachment limit of 2048K');
		} else if ($_FILES['emailattachment']['size'] <= 0) {
			error('The file you uploaded apears to be empty','Please check the file and try again');
		} else if ($extdotpos === false) {
			error('The file you uploaded does not have a file extension','Please make sure the file has the correct extension and try again');
		} else {

			$contentid = contentPut($newname,$mimetype);
			@unlink($dest);

			if ($contentid) {
				$_SESSION['emailattachment'] = array(
						"cmid" => $contentid,
						"filename" => $filename,
						"size" => $_FILES['emailattachment']['size'],
						"mimetype" => $_FILES['emailattachment']['type']
					);
				$uploaderror = false;
			} else {
				error_log("Unable to upload email attachment data, either the file was empty or there is a DB problem.");
				error('Unable to complete file upload. Please try again');
			}
		}
	}


	//check to see if formdata is valid
	if(CheckFormInvalid($form))
	{
		error('Form was edited in another window, reloading data');
		$reloadform = 1;
	}
	else
	{
		MergeSectionFormData($form, $section);

		//get the ID of any message with the same name and type
		$name = trim(GetFormData($form,$section,"name"));
		if ( empty($name) ) {
			PutFormData($form,$section,"name",'',"text",1,50,true);
		}
		$existsid = QuickQuery("select id from message where name='" . DBSafe($name) . "' and type='$MESSAGETYPE' and userid='$USER->id' and deleted=0");
		if($MESSAGETYPE == "email"){
			$emaildomain = getSystemSetting('emaildomain');
			$fromemaildomain = substr(GetFormData($form, $section, "fromemail"), strpos(GetFormData($form, $section, "fromemail"), "@")+1);
		}


		//do check
		if( CheckFormSection($form, $section) ) {
			error('There was a problem trying to save your changes', 'Please verify that all required field information has been entered properly');
		} else if ($existsid && $existsid != $_SESSION['messageid']) {
			error('A message named \'' . $name . '\' already exists');
		} else if (strlen(GetFormData($form,$section,"body")) == 0) {
			error('The message body cannot be empty');
		} else if ( ($MESSAGETYPE == "email") && $emaildomain && (strtolower($emaildomain) != strtolower($fromemaildomain))){
			error('That From Email address is not valid', 'You must use an email address at ' . $emaildomain);
		} else if (!$uploaderror) {
			//check the parsing
			$message = new Message($_SESSION['messageid']);
			$message->readHeaders();
			$errors = array();

			if($MESSAGETYPE != "sms"){
				$parts = $message->parse(GetFormData($form,$section,"body"),$errors, GetFormData($form,$section,"voiceid"));
			} else if(strlen(GetFormData($form,$section,"body")) > 160){
				error("There are too many characters for an SMS message: " . strlen(GetFormData($form,$section,"body")), "You can only have 160 characters");
			}

			if (count($errors) > 0) {
				error('There was an error parsing the message', $errors);
			} else {
				//submit changes
				$message->type = $MESSAGETYPE;

				//check that the message->userid == user->id so that there is no chance of hijacking
				if ($message->id && !userOwns("message",$message->id) || $message->deleted ) {
					exit("nope!"); //TODO
				}

				$message->name = $name;
				$message->description = trim(GetFormData($form,$section,"description"));

				$fields = array("body");
				if ($MESSAGETYPE == "email") {
					$fields[] = "subject";
					$fields[] = "fromname";
					$fields[] = "fromemail";
				}
				if ($MESSAGETYPE == "print") {
					$fields[] = "header1";
					$fields[] = "header2";
					$fields[] = "header3";
					$fields[] = "fromaddress";
				}

				PopulateObject($form,$section,$message,$fields);
				$message->userid = $USER->id;

				$message->stuffHeaders();
				$message->update();

				//check for deleted attachments
				if (CheckFormSubmit($form,"delete") !== false) {
					$deleteid = CheckFormSubmit($form,"delete");
					if (isset($attachments[$deleteid])) {
						$msgattachment = $attachments[$deleteid];
						$msgattachment->deleted=1;
						$msgattachment->update();
						unset($attachments[$deleteid]);
					} else {
						error_log("trying to delete nonexistant messageattachment");
					}
				}
				//see if there is an uploaded file and add it to this email
				if (isset($_SESSION['emailattachment'])) {
					$msgattachment = new MessageAttachment();
					$msgattachment->messageid = $message->id;
					$msgattachment->contentid = $_SESSION['emailattachment']['cmid'];
					$msgattachment->filename = $_SESSION['emailattachment']['filename'];
					$msgattachment->size = $_SESSION['emailattachment']['size'];
					$msgattachment->create();
					$attachments[$msgattachment->id] = $msgattachment;
					unset($_SESSION['emailattachment']);
				}


				//update the parts
				QuickUpdate("delete from messagepart where messageid=$message->id");
				if($MESSAGETYPE == "sms"){
					$part = new MessagePart();
					$part->messageid = $message->id;
					$part->type="T";
					$part->txt = GetFormData($form,$section,"body");
					$part->sequence = 0;
					$part->create();
				} else {
					foreach ($parts as $part) {
						if(!isset($part->voiceid))
							$part->voiceid = GetFormData($form,$section,"voiceid");
						$part->messageid = $message->id;
						$part->create();
					}
				}

				$_SESSION['messageid'] = $message->id;

				if (CheckFormSubmit($form,$section)) {
					ClearFormData($form);
					redirect('messages.php');
				} else {
					$reloadform = 1;
				}
			}
		}
	}
} else {
	$reloadform = 1;
}

if( $reloadform )
{
	ClearFormData($form);

	//check for new message name/desc from messages.php
	$newmsg = false;

	if(!isset($_SESSION['messageid']))
		$newmsg = true;
	$body = "";
	if(!$newmsg){
		$message = new Message($_SESSION['messageid']);
		$message->readHeaders();
		$parts = DBFindMany("MessagePart","from messagepart where messageid=$message->id order by sequence");
		$body = $message->format($parts);
	} else {
		$message = new Message();
		if($MESSAGETYPE == "email"){
			$message->fromname = $USER->firstname . " " . $USER->lastname;
			$useremails = explode(";", $USER->email);
			$message->fromemail = $useremails[0];
		}
	}
	$fields = array(
			array("name","text",0,50,true),
			array("description","text",0,50)
			);

	switch($MESSAGETYPE)
	{
		case 'phone':
			//$fields[] = array("voiceid", "number", 0, 0, true);
			break;
		case 'email':
			$fields[] = array("subject", "text", 1, 50, true);
			$fields[] = array("fromname", "text", 1, 50, true);
			$fields[] = array("fromemail", "email", 1, 100, true);
			break;
		case 'print':
			$fields[] = array("header1", "text", 1, 50);
			$fields[] = array("header2", "text", 1, 50);
			$fields[] = array("header3", "text", 1, 50);
			$fields[] = array("fromaddress", "text", 0, 65536);
			break;
	}

	PutFormData($form,$section,"body",$body,'text');

	PutFormData($form,$section,"voiceid",$newmsg ? 0 : $message->firstVoiceID(),"nomin","nomax",true);



	PopulateForm($form,$section,$message,$fields);

	//do some custom stuff for the options
}

$fieldmap = FieldMap::getAuthorizedMapNames();


////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE = "notifications:messages";
$TITLE = format_delivery_type($MESSAGETYPE) . ' Message Editor: ' . (trim(GetFormData($form,$section,"name")) ? escapehtml(trim(GetFormData($form,$section,"name"))) : "New Message" );
$ICON = $MESSAGETYPE . ".gif";

include_once("nav.inc.php");

NewForm($form);
buttons( submit($form, $section, 'Save'));
startWindow('Message Information', 'padding: 3px;');
print 'Name: ';
NewFormItem($form,$section,"name","text", 30,50);
print '&nbsp;&nbsp;Description: ';
NewFormItem($form,$section,"description","text", 30,50);
print '&nbsp;';
endWindow();

print '<br>';

switch($MESSAGETYPE)
{
	case 'phone':
		startWindow('Message Content ' . help('MessagePhone_Message') );
		?>
			<table border="0" cellpadding="3" cellspacing="0">
				<tr>
					<td rowspan="4">
						<? NewFormItem($form, $section,"body","textarea",60,NULL,'id="bodytext"'); ?>
					</td>
					<th align="right" class="windowRowHeader bottomBorder" width="70">Audio Recording:<br><? print help('MessagePhone_AudioRecording'); ?></th>
					<td class="bottomBorder">
						<table border="0" cellpadding="1" cellspacing="0">
							<tr>
								<td>
									<? audio('audio'); ?>
								</td>
								<td><? print button('Insert', "sel = new getObj('audio').obj; if (sel.options[sel.selectedIndex].value > 0) {  textInsert('{{' + sel.options[sel.selectedIndex].text + '}}', new getObj('bodytext').obj);}"); ?></td>
								<td><? print button('Play', "var audio = new getObj('audio').obj; if(audio.selectedIndex >= 1) popup('previewaudio.php?close=1&id=' + audio.options[audio.selectedIndex].value, 400, 400);"); ?></td>
							</tr>
						</table>
					</td>
				</tr>
				<tr>
					<th align="right" class="windowRowHeader bottomBorder">Data Field:<br><? print help('MessagePhone_DataField'); ?></th>
					<td class="bottomBorder"><select id="data" name="data">
<?

		foreach($fieldmap as $name)
		{
			print "<option value=\"$name\">$name</option>\n";
		}
?>
						</select>
						<br>
						<table border="0" cellpadding="1" cellspacing="0" style="font-size: 9px; margin-top: 5px;">
							<tr>
								<td>Default&nbsp;Value:</td>
								<td><input type="text" size="10" id="default"></td>
								<td><? print button('Insert', "sel = new getObj('data').obj; def = new getObj('default').obj.value; textInsert('<<' + sel.options[sel.selectedIndex].text + (def ? ':' : '') + def + '>>', new getObj('bodytext').obj);"); ?></td>
							</tr>
						</table></td>
				</tr>
				<tr>
					<th align="right" class="windowRowHeader">Text-to-Speech:<br><? print help('MessagePhone_TextToSpeech'); ?></th>
					<td>
<?
		$fields = Voice::getTTSVoices();
		NewFormItem($form,$section, 'voiceid', 'selectstart');
		foreach($fields as $file)
		{
			$name = ucfirst($file->language) . ' - ' . ucfirst($file->gender);
			NewFormItem($form,$section, 'voiceid', 'selectoption', $name, $file->id);
		}
		NewFormItem($form,$section, 'voiceid', 'selectend');
?>
					</td>
				</tr>
				<tr>
					<th align="right" class="windowRowHeader">&nbsp;</th>
					<td>&nbsp;</td>
				</tr>
			</table>
		<?
		break;
	case 'email':
		startWindow('Message Content ' . help('MessageEmail_Message') );
		?>
			<table border="0" cellpadding="3" cellspacing="0">
				<tr>
					<th width="70" class="windowRowHeader" align="right">Subject:<br><? print help('MessageEmail_Subject'); ?></th>
					<td colspan="3">
						<? NewFormItem($form, $section, 'subject', 'text', 30, 50,'id="subject"'); ?>
					</td>
				</tr>
				<tr>
					<th align="right" class="windowRowHeader">From Name:</th>
					<td colspan="3">
						<? NewFormItem($form, $section, 'fromname', 'text', 30, 50); ?> (ex: Joe Smith)
					</td>
				</tr>
				<tr>
					<th align="right" class="windowRowHeader bottomBorder">From Email:</th>
					<td colspan="3" class="bottomBorder">
						<? NewFormItem($form, $section, 'fromemail', 'text', 50,100); ?> (ex: joe.smith@<?= getSystemSetting('emaildomain') ? getSystemSetting('emaildomain') : "example.com" ?>)
					</td>
				</tr>


				<tr>
					<th align="right" class="windowRowHeader bottomBorder">Attachments:<br>(Max 3)<br><? print help('MessageEmail_Attachments') ?></th>
					<td colspan="3" class="bottomBorder">
						<table border="0" cellpadding="2" cellspacing="1" class="list" width="50%">
						<tr class="listHeader" align="left" valign="bottom">
							<th>Name</th>
							<th>Size</th>
							<th>Actions</th>
						</tr>
<?
		foreach ($attachments as $attachment) {
?>
						<tr>
							<td><a href="messageattachmentdownload.php?id=<?= $attachment->id ?>"><?= escapehtml($attachment->filename)?></a></td>
							<td><?= max(1,round($attachment->size/1024)) ?>K</td>
							<td><?= submit($form,'delete',"Delete",$attachment->id); ?></td>
						</tr>
<?
		}
		//if the user uploaded a file, but needs to correct form errors, don't show the upload box again
		if (isset($_SESSION['emailattachment'])) {
?>
						<tr>
							<td><?= escapehtml($_SESSION['emailattachment']['filename']) ?></td>
							<td><?= max(1,round($_SESSION['emailattachment']['size']/1024)) ?>K</td>
							<td>&nbsp;</td>
						</tr>
<?
		} else if (count($attachments) < 3) {
?>
						<tr>
							<td><input type="hidden" name="MAX_FILE_SIZE" value="<?= $maxattachmentsize ?>"><input type="file" name="emailattachment" size="30"></td>
							<td>(Max 2048K)</td>
							<td><?= submit($form, "upload", 'Upload') ?></td>
						</tr>
<?
		}
?>
						</table>
					</td>
				</tr>

				<tr>
					<th align="right" class="windowRowHeader" valign="top" style="padding-top: 6px;">Body:<br><? print help('MessageEmail_Body'); ?></th>
					<td>
						<? NewFormItem($form, $section,"body","textarea",60,NULL,'id="bodytext"'); ?>
					</td>
					<th align="right" class="windowRowHeader" valign="top">Data Field:<br><? print help('MessageEmail_DataField'); ?></th>
					<td valign="top">
						<select id="data" name="data">
<?
		foreach($fieldmap as $name)
		{
			print "<option value=\"$name\">$name</option>\n";
		}
?>
						</select>
						<br>
						<table border="0" cellpadding="1" cellspacing="0" style="font-size: 9px; margin-top: 5px;">
							<tr>
								<td>Default&nbsp;Value:</td>
								<td><input type="text" size="10" id="default"></td>
								<td><? print button('Insert', "sel = new getObj('data').obj; def = new getObj('default').obj.value;textInsert('<<' + sel.options[sel.selectedIndex].text + (def ? ':' : '') + def + '>>', new getObj('bodytext').obj);"); ?></td>
							</tr>
						</table>
					</td>
				</tr>
			</table>
		<?
		break;
	case 'print':
		startWindow('Message Content ' . help('MessagePhone_Message') );
		?>
			<table border="0" cellpadding="3" cellspacing="0">
				<tr>
					<th width="70" align="right" class="windowRowHeader bottomBorder" valign="top">Header:<br><? print help('MessagePrint_Header'); ?></th>
					<td colspan="3" class="bottomBorder">
						<table border="0" cellpadding="2" cellspacing="0">
							<tr><td>Line 1:</td><td><? NewFormItem($form, $section, 'header1', 'text', 30, NULL,'id="header1"'); ?></td></tr>
							<tr><td>Line 2:</td><td><? NewFormItem($form, $section, 'header2', 'text', 30); ?></td></tr>
							<tr><td>Line 3:</td><td><? NewFormItem($form, $section, 'header3', 'text', 30); ?></td></tr>
						</table>
					</td>
				</tr>
				<tr>
					<th align="right" class="windowRowHeader bottomBorder">From Address:<br><? print help('MessagePrint_FromAddress'); ?></th>
					<td colspan="3" class="bottomBorder">
						<? NewFormItem($form, $section, 'fromaddress', 'textarea', 35); ?>
					</td>
				</tr>
				<tr>
					<th align="right" class="windowRowHeader" valign="top" style="padding-top: 6px;">Body:<br><? print help('MessagePrint_Body'); ?></th>
					<td>
						<? NewFormItem($form, $section,"body","textarea",60,NULL,'id="bodytext"'); ?>
						<!--<textarea cols="75" rows="20" id="body" name="body" onChange="if(document.selection) this.sel = document.selection.createRange();"><? print $message->body; ?></textarea>-->
					</td>
					<th align="right" valign="top" class="windowRowHeader" width="70" style="padding-top: 6px;">Data Field:<br><? print help('MessagePrint_DataField'); ?></th>
					<td align="left" valign="top">
						<select id="data" name="data">
<?
		foreach($fieldmap as $name)
		{
			print "<option value=\"$name\">$name</option>\n";
		}
?>
						</select>
						<br>
						<table border="0" cellpadding="1" cellspacing="0" style="font-size: 9px; margin-top: 5px;">
							<tr>
								<td>Default&nbsp;Value:</td>
								<td><input type="text" size="10" id="default"></td>
								<td><? print button('Insert', "sel = new getObj('data').obj; def = new getObj('default').obj.value; textInsert('<<' + sel.options[sel.selectedIndex].text + (def ? ':' : '') + def + '>>', new getObj('bodytext').obj);"); ?></td>
							</tr>
						</table>
					</td>
				</tr>
			</table>
		<?
		break;
	case 'sms':
		startWindow('Message Content ' . help('MessageSms_MessageContent') );
		?>
			<table>
				<tr>
					<th align="right" class="windowRowHeader" valign="top" style="padding-top: 6px;">Body:<br><? print help('MessageSms_Body'); ?></th>
					<td>
						<? NewFormItem($form, $section,"body","textarea",60,NULL,'id="bodytext" onkeydown="limit_chars(this);" onkeyup="limit_chars(this);"'); ?>
					</td>
				</tr>
			</table>
			<script>

				function limit_chars(field) {
					if (field.value.length > 160)
						field.value = field.value.substring(0,160);
					var status = new getObj('charsleft');
					var remaining = 160 - field.value.length;
					if (remaining <= 0)
						status.obj.innerHTML="<b style='color:red;'>0</b>";
					else if (remaining <= 20)
						status.obj.innerHTML="<b style='color:orange;'>" + remaining + "</b>";
					else
						status.obj.innerHTML=remaining;
				}
			</script>
			<span id="charsleft"><?= 160 - strlen(GetFormData($form,$section,"body")) ?></span> characters remaining.
		<?
		break;
}
endWindow();
buttons();
EndForm();
include_once("navbottom.inc.php");

?>

<script language="javascript">
<?
//only execute this code if its not sms
//sms does not allow message inserts
if($MESSAGETYPE != "sms"){
?>
	ta = new getObj('bodytext').obj;
	ta.focus();
	ta.onmouseup = ta.onkeyup = function() { if(document.selection) this.sel = document.selection.createRange(); };
	ta.onmouseup();
<?
}
?>
<?
//now focus on the correct field
switch($MESSAGETYPE) {
	case 'email':
		echo "var field = new getObj('subject').obj; field.focus(); ";
		break;
	case 'print':
		echo "var field = new getObj('header1').obj; field.focus(); ";
		break;
	case 'phone':
	case 'sms':
	default:
		echo "var field = new getObj('bodytext').obj; field.focus(); ";
		break;
}
?>
</script>
