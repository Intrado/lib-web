<?

//polls the specialtask until it's finished or timeout occurs

include_once('inc/common.inc.php');
include_once('obj/SpecialTask.obj.php');
include_once('obj/Message.obj.php');
include_once('obj/MessagePart.obj.php');
include_once('obj/AudioFile.obj.php');
include_once('obj/Phone.obj.php');
include_once('obj/AudioFile.obj.php');
include_once('inc/html.inc.php');
include_once("inc/form.inc.php");
include_once('inc/table.inc.php');

if (!$USER->authorize("starteasy")) {
	redirect("unauthorized.php");
}
$f = "callme";
$s = "main";

$specialtask = new SpecialTask($_SESSION['callmeid']);
$messages = array();
for($i = 1; $i < $specialtask->getData('count'); $i++){
	$messnum = "message".$i;
	$messages[$i] = $specialtask->getData($messnum);
}

$reloadform = 0;

if(CheckFormSubmit($f, $s)){
	if(CheckFormInvalid($f))
	{
		error('Form was edited in another window, reloading data');
		$reloadform = 1;
	}
	else
	{
		MergeSectionFormData($f, $s);
		
		$newnames = array();
		$existsnames = array();
		if($messages){
			if($specialtask->getData("origin") == "message"){
				foreach($messages as $key => $message) {
					$name = TrimFormData($f, $s, "message ".$key);
					if(0 != QuickQuery("select count(id) from message where name='" . DBSafe($name) . "' and id!=$message and type='phone' and userid='$USER->id' and deleted=0")){
						$existsnames[] = "'$name'";
					}
					$newnames[] = $name;
				}
			} else if($specialtask->getData("origin") == "audio") {
				foreach($messages as $key => $message) {
					$name = TrimFormData($f, $s, "message ".$key);
					if(0 != QuickQuery("select count(id) from audiofile where name='" . DBSafe($name) . "' and id!=$message and userid='$USER->id' and deleted=0")){
						$existsnames[] = "'$name'";
					}
					$newnames[] = $name;
				}
			}	
		}
		
		//do check		
		if( CheckFormSection($f, $s) ) {
			error('There was a problem trying to save your changes', 'Please verify that all required field information has been entered properly');
		} else if (!empty($existsnames)) {
			error('Message(s) named ' . implode(",",$existsnames) . ' already exists');
		} else if (sizeof(array_unique($newnames)) != sizeof($messages)) {
			error('Messages can not be named the same');	
		} else {
			if($specialtask->getData("origin") == "message"){
				if($messages){
					foreach($messages as $key => $message) {
						$mess = new Message($message);
						$mess->name = GetFormData($f,$s,"message ".$key);
						$mess->description = "Call Me - " . $mess->name;
						$mess->update();
						$messagepart = DBFind("MessagePart", "from messagepart where messageid = '$mess->id'");
						$audio = new AudioFile($messagepart->audiofileid);
						$audio->name = GetformData($f, $s, "message ".$key);
						$audio->update();
					}
				}
			} else if($specialtask->getData("origin") == "audio") {
				if($messages){
					foreach($messages as $key => $message) {
						$mess = new AudioFile($message);
						$mess->name = GetFormData($f,$s,"message ".$key);
						$mess->update();
					}
				}
			}
			
			if ($specialtask->getData('origin') == "audio") {
				redirect("audio.php");
			} else {
			?>
				<script language="javascript">
					alert("Your message has been saved.");
					<?
						if ($specialtask->getData('origin') == 'message') {
							print 'window.opener.document.location.reload(); window.close()';
						} else {
							print 'window.close()';
						}
					?>
				</script>
			<?	
				exit(); 
			}
		}
	}
} else {
	$reloadform = 1;
}

if ($reloadform) {
	ClearFormData($f);
	if($specialtask->getData("origin") == "message"){
		if($messages){
			foreach($messages as $key => $message){
				$mess = new Message($message);
				PutFormData($f, $s, "message ".$key, $mess->name,"text","1","50",true);
			}
		}
	} else if($specialtask->getData("origin") == "audio") {
		if($messages){
			foreach($messages as $key => $message){
				$mess = new AudioFile($message);
				PutFormData($f, $s, "message ".$key, $mess->name,"text","1","50",true);
			}
		}
	}
}

/////////////////////////
// Display



$TITLE = 'Call Me';

include_once('popup.inc.php');

NewForm($f);
buttons(submit($f, $s, 'Save'));
startWindow("Rename Files");

?>

	<table border="0" cellpadding="3" cellspacing="0" width="100%">
		<tr>
			<th colspan="2" align="left" class="windowRowHeader bottomBorder">Name</td>
		</tr>
			<?
			if($messages){
				foreach($messages as $key => $message){
					?>
					<tr>
						<td>
<?
								NewFormItem($f, $s, "message ".$key, "text");
?>
						</td>
						<td>
<?
							if($specialtask->getData("origin") == "message")
								echo button('Play', "popup('previewmessage.php?id=" . $message . "', 400, 400);");
							else
								echo button('Play', "popup('previewaudio.php?id=" . $message . "&close=1', 400, 400);");
?>
						</td>
					</tr>
					<?
				}
			}
		?>

	</table>

<?
endWindow();
buttons();
include_once('popupbottom.inc.php');
?>