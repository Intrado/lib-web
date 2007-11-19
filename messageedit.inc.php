<?
include_once("inc/common.inc.php");


////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

//get the message to edit from the request params or session
if (isset($_GET['id'])) {
	if($_GET['id'] == "new")
		$_SESSION['messageid'] = NULL;
	else
		setCurrentMessage($_GET['id']);

	redirect("message" . $MESSAGETYPE . ".php");
}

/****************** main message section ******************/

$dopreview = 0;
$form = "message";
$section = "main". $MESSAGETYPE;
$reloadform = 0;

if(CheckFormSubmit($form,$section) || CheckFormSubmit($form,"preview"))
{
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
		$name = DBSafe(GetFormData($form,$section,"name"));
		$existsid = QuickQuery("select id from message where name='$name' and type='$MESSAGETYPE' and userid='$USER->id' and deleted=0");

		//do check
		if( CheckFormSection($form, $section) ) {
			error('There was a problem trying to save your changes', 'Please verify that all required field information has been entered properly');
		} else if ($existsid && $existsid != $_SESSION['messageid']) {
			error('A message named \'' . GetFormData($form,$section,"name") . '\' already exists');
		} else if (strlen(GetFormData($form,$section,"body")) == 0) {
			error('The message body cannot be empty');
		} else {
			//check the parsing
			$message = new Message($_SESSION['messageid']);
			$errors = array();
			$parts = $message->parse(GetFormData($form,$section,"body"),$errors);
			$charcount = 0;
			if($MESSAGETYPE == "sms"){
				foreach($parts as $part){
					if($part->maxlen)
						$charcount += $part->maxlen;
					else
						$charcount += strlen($part->txt);
				}
			}
			
			if (count($errors) > 0) {
				error('There was an error parsing the message', implode("",$errors));
			} else if($MESSAGETYPE == "sms" && $charcount > 160){
				error("There are too many characters for an SMS message: " . $charcount, "You can only have 160 characters");
			} else {
				//submit changes
				$message->type = $MESSAGETYPE;

				//check that the message->userid == user->id so that there is no chance of hijacking
				if ($message->id && !userOwns("message",$message->id) || $message->deleted ) {
					exit("nope!"); //TODO
				}
				$fields = array("name","description","body");
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

				//update the parts
				QuickUpdate("delete from messagepart where messageid=$message->id");
				foreach ($parts as $part) {
					$part->voiceid = GetFormData($form,$section,"voiceid");
					$part->messageid = $message->id;
					$part->create();
				}


				$_SESSION['messageid'] = $message->id;

				if (CheckFormSubmit($form,"preview")) {
					$reloadform = 1;
					$dopreview = 1;
				} else {
					ClearFormData($form);
					redirect('messages.php');
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
$TITLE = format_delivery_type($MESSAGETYPE) . ' Message Editor: ' . (GetFormData($form,$section,"name") ? GetFormData($form,$section,"name") : "New Message" );
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
								<td><? print button('Insert', "sel = new getObj('audio').obj; if (sel.options[sel.selectedIndex].value > 0) {  insert('{{' + sel.options[sel.selectedIndex].text + '}}', new getObj('bodytext').obj);}"); ?></td>
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
								<td><? print button('Insert', "sel = new getObj('data').obj; def = new getObj('default').obj.value; insert('<<' + sel.options[sel.selectedIndex].text + (def ? ':' : '') + def + '>>', new getObj('bodytext').obj);"); ?></td>
							</tr>
						</table></td>
				</tr>
				<tr>
					<th align="right" class="windowRowHeader">Text-to-Speech:<br><? print help('MessagePhone_TextToSpeech'); ?></th>
					<td>
<?
		$fields = DBFindMany("Voice","from ttsvoice order by language, gender desc");
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
						<? NewFormItem($form, $section, 'fromname', 'text', 30, 50); ?>
					</td>
				</tr>
				<tr>
					<th align="right" class="windowRowHeader bottomBorder">From Email:</th>
					<td colspan="3" class="bottomBorder">
						<? NewFormItem($form, $section, 'fromemail', 'text', 30,100); ?>
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
								<td><? print button('Insert', "sel = new getObj('data').obj; def = new getObj('default').obj.value;insert('<<' + sel.options[sel.selectedIndex].text + (def ? ':' : '') + def + '>>', new getObj('bodytext').obj);"); ?></td>
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
								<td><? print button('Insert', "sel = new getObj('data').obj; def = new getObj('default').obj.value; insert('<<' + sel.options[sel.selectedIndex].text + (def ? ':' : '') + def + '>>', new getObj('bodytext').obj);"); ?></td>
							</tr>
						</table>
					</td>
				</tr>
			</table>
		<?
		break;
	case 'sms':
		startWindow('Message Content ' . help('MessageSms_Message') );
		?>
			<table>
				<tr>
					<th align="right" class="windowRowHeader" valign="top" style="padding-top: 6px;">Body:<br><? print help('MessageEmail_Body'); ?></th>
					<td>
						<? NewFormItem($form, $section,"body","textarea",60,NULL,'id="bodytext" onkeydown="limit_chars(this);" onkeyup="limit_chars(this);"'); ?>
					</td>
					<?
					/* TODO: Add message inserts later when supported, jjl */
					?>
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
ta = new getObj('bodytext').obj;
ta.focus();
ta.onmouseup = ta.onkeyup = function() { if(document.selection) this.sel = document.selection.createRange(); };
ta.onmouseup();

<?
//now focus on the correct field
switch($MESSAGETYPE) {
	case 'phone':
		echo "var field = new getObj('bodytext').obj; field.focus(); ";
		break;
	case 'email':
		echo "var field = new getObj('subject').obj; field.focus(); ";
		break;
	case 'print':
		echo "var field = new getObj('header1').obj; field.focus(); ";
		break;
}
?>
</script>
