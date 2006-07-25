<?
//asks for user input (phone, name)
//posts to callme2


include_once('inc/common.inc.php');
include_once('obj/SpecialTask.obj.php');
include_once('obj/Message.obj.php');
include_once('obj/MessagePart.obj.php');
include_once('obj/AudioFile.obj.php');
include_once('obj/Phone.obj.php');
include_once('inc/html.inc.php');
include_once("inc/form.inc.php");
include_once('inc/table.inc.php');

// AUTHORIZATION //////////////////////////////////////////////////
$origin = $_REQUEST['origin'];
if (!$USER->authorize("starteasy")) {
	redirect("unauthorized.php");
}

$f = "callme";
$s = "main";
$reloadform = 0;

if(CheckFormSubmit($f,$s))
{
	//check to see if formdata is valid
	if(CheckFormInvalid($f))
	{
		error('Form was edited in another window, reloading data');
		$reloadform = 1;
	}
	else
	{
		MergeSectionFormData($f, $s);

		$phone = preg_replace('/[^\\d]/', '', GetFormData($f,$s,"phone"));

		//do check
		if( CheckFormSection($f, $s) ) {
			error('There was a problem trying to save your changes', 'Please verify that all required field information has been entered properly');
		} else if (strlen($phone) < 2 || (strlen($phone) > 6 && strlen($phone) != 10)) {
			error('The phone number must be 2-6 digits or exactly 10 digits long (including area code)','You do not need to include a 1 for long distance');
		} else if (QuickQuery("select * from audiofile where userid = {$USER->id} and deleted = 0 and name = '" .
				   DBSafe(GetFormData($f, $s, 'name')) . "'")) {
			error('This audio file name is already in use, so a unique one was generated');
			$testname = DBSafe(GetFormData($f, $s, 'name')) . ' ' . date("Y-m-d_H:i:s");
			PutFormData($f, $s, 'name', $testname, 'text', 1, 50, true); // Repopulate the form/session data with the generated name
//		} else if (strlen(GetFormData($f,$s,"name")) <= 0) {
//			error ("Please name your message");
		} else {
			$task = new SpecialTask();
			$task->type = 'EasyCall';
			$task->setData('phonenumber', $phone);
			$task->setData('name', GetFormData($f,$s,"name"));
			$task->setData('origin', GetFormData($f,$s,"origin"));
			$task->setData('userid', $USER->id);
			$task->create();

			redirect('callme2.php?taskid=' . $task->id);
		}
	}
} else {
	$reloadform = 1;
}

if( $reloadform )
{
	ClearFormData($f);

	if ($USER->phone)
		$phone = Phone::format($USER->phone);
	else
		$phone = "";

	PutFormData($f,$s,"phone",$phone,"text","2","20"); // 20 is the max to accomodate formatting chars
	PutFormData($f,$s,"name","","text","1","50",false);
	PutFormData($f,$s,"origin",$_REQUEST['origin']);
}


$TITLE = 'Call Me';

include_once('popup.inc.php');

NewForm($f);
if (GetFormData($f,$s,"origin") == 'audio') {
	$onclick = "window.location = 'audio.php'";
} else {
	$onclick = 'window.close()';
}
buttons(submit($f, $s, 'submit','callmetorecord'), button('cancel', $onclick));
startWindow("Call Me to Record");

?>
	<table border="0" cellpadding="3" cellspacing="0" width="400">

		<tr>
			<th align="right" class="windowRowHeader bottomBorder"><?= (GetFormData($f,$s,"origin") == "message" ? "Message&nbsp;Name:" : "Audio&nbsp;File&nbsp;Name:") ?></td>
			<td class="bottomBorder"><? NewFormItem($f,$s,"name","text",30,50); ?></td>
		</tr>
		<tr>
			<th align="right" class="windowRowHeader bottomBorder">Phone&nbsp;Number:</td>
			<td class="bottomBorder"><? NewFormItem($f,$s,"phone","text",20); ?></td>
		</tr>
		<tr><td colspan=3 style="padding: 10px;">If dialing an outside line, please include the area code.</td><tr>	</table>

<?
endWindow();
buttons();
include_once('popupbottom.inc.php');
?>