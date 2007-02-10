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

$specialtask = new SpecialTask($_REQUEST['taskid']);
$messages = unserialize($specialtask->getData("messages"));
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
		if($specialtask->getData("origin") == "message"){
			foreach($messages as $key => $message) {
				$mess = new Message($message);
				$mess->name = GetFormData($f,$s,"message ".$key);
				$mess->update();
			}
		} else if($specialtask->getData("origin") == "audio") {
			foreach($messages as $key => $message) {
				$mess = new AudioFile($message);
				$mess->name = GetFormData($f,$s,"message ".$key);
				$mess->update();
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
		}
	}
} else {
	$reloadform = 1;
}

if ($reloadform) {
	if($specialtask->getData("origin") == "message"){
		foreach($messages as $key => $message){
			$mess = new Message($message);
			PutFormData($f, $s, "message ".$key, $mess->name);
		}
	} else if($specialtask->getData("origin") == "audio") {
		foreach($messages as $key => $message){
			$mess = new AudioFile($message);
			PutFormData($f, $s, "message ".$key, $mess->name);
		}
	}
}

/////////////////////////
// Display



$TITLE = 'Call Me';

include_once('popup.inc.php');

NewForm($f);
buttons(submit($f, $s, 'submit','Save'));
startWindow("Rename Files");

?>

	<table border="0" cellpadding="3" cellspacing="0" width="400">
		
			<th align="left" class="windowRowHeader bottomBorder">Name</td>
			<?
			foreach($messages as $key => $message){
				?>
				<tr>
					<td>
						<? 
							NewFormItem($f, $s, "message ".$key, "text");
							echo "&nbsp;" . button('play', "popup('previewmessage.php?id=" . $message . "', 400, 400);");
						?>
					</td>
				</tr>
				<?
			}
		?>
		
	</table>
		
<?
endWindow();
buttons();
include_once('popupbottom.inc.php');
?>