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

		$phone = Phone::parse(GetFormData($f,$s,"phone"));

		//do check
		if( CheckFormSection($f, $s) ) {
			error('There was a problem trying to save your changes', 'Please verify that all required field information has been entered properly');
		} else if ($phoneerror = Phone::validate($phone, true)){
			error($phoneerror);
		} else if (QuickQuery("select count(*) from audiofile where userid = '$USER->id' and deleted = 0 and name = '" .
				   DBSafe(GetFormData($f, $s, 'name')) . "'")) {
			error('This audio file name is already in use, so a unique one was generated');
			$testname = GetFormData($f, $s, 'name') . ' ' . date("Y-m-d H:i");
			PutFormData($f, $s, 'name', $testname, 'text', 1, 50); // Repopulate the form/session data with the generated name
		} else {
			$task = new SpecialTask();
			$task->type = 'CallMe';
			$task->setData('phonenumber', $phone);
			$name = GetFormData($f,$s,"name");
			if($name == "")
				$name = "Call Me - " . date("M d, Y G:i:s");
			$task->setData('name', $name);
			$task->setData('origin', GetFormData($f,$s,"origin"));
			$task->setData('userid', $USER->id);
			$task->setData('callerid', getSystemSetting('callerid'));
			$task->setData('progress', "Calling");
			$task->setData('count', 1);
			$task->lastcheckin = date("Y-m-d H:i:s");
			$task->status = "queued";
			$task->customerid=$USER->customerid;
			
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
	PutFormData($f,$s,"name","","text","1","50");
	PutFormData($f,$s,"size","","text");
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
		
<? 
		if($IS_COMMSUITE){
			?> <tr><td colspan=3 style="padding: 10px;">If dialing an outside line, please include the area code.</td><tr> <?
		} else {
			?> <tr><td colspan=3 style="padding: 10px;">Enter the 10-digit direct-dial phone number where you are currently located.</td><tr> <?
		}
?>
		</table>
		
<?
endWindow();
buttons();
include_once('popupbottom.inc.php');
?>