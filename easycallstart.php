<?
//asks for user input (phone, name)
//posts to callme2


include_once('inc/common.inc.php');
include_once('obj/SpecialTask.obj.php');
include_once('obj/Message.obj.php');
include_once('obj/MessagePart.obj.php');
include_once('obj/AudioFile.obj.php');
include_once('obj/Phone.obj.php');
include_once("obj/JobType.obj.php");
include_once("obj/PeopleList.obj.php");
include_once("obj/Job.obj.php");
include_once('inc/html.inc.php');
include_once("inc/form.inc.php");
include_once('inc/table.inc.php');

// AUTHORIZATION //////////////////////////////////////////////////
if (!$USER->authorize("starteasy")) {
	redirect("unauthorized.php");
}

// DATA SECTION
$VALIDJOBTYPES = JobType::getUserJobTypes();

// FORM HANDLING
$f = "easycall";
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
		} else if (!Phone::validate($phone)) {
			if ($IS_COMMSUITE)
				error('The phone number must be 2-6 digits or exactly 10 digits long (including area code)','You do not need to include a 1 for long distance');
			else
				error('The phone number must be exactly 10 digits long (including area code)','You do not need to include a 1 for long distance');
		} else if (GetFormData($f,$s,"listid") <=0 ) {
			error('Please choose a list');
		} else {

			$task = new SpecialTask();
			$task->type = 'EasyCall';
			$task->setData('phonenumber', $phone);

			$testname = GetFormData($f, $s, 'name');
			if (QuickQuery("select * from audiofile where userid = {$USER->id} and deleted = 0 and name = '" .
				  DBSafe($testname) . "' and id != '" . $audio->id. "'")) {
				error('This audio file name is already in use, a unique one was generated');

				$testname = GetFormData($f, $s, 'name') . ' ' . date("F jS, Y h:i a");
				PutFormData($f, $s, 'name', $testname, 'text', 1, 50, true); // Repopulate the form/session data with the generated name
			}

			$task->setData('name', $testname);
			$task->setData('origin', "start");
			$task->setData('userid', $USER->id);
			$task->setData('listid', GetFormData($f,$s,"listid"));
			$task->setData('jobtypeid', GetFormData($f,$s,"jobtypeid"));
			$task->lastcheckin = date("Y-m-d H:i:s");
			$task->create();

			redirect('easycallrecord.php?taskid=' . $task->id);
		}
	}
} else {
	ClearFormData($f);

	if (isset($_GET['retry']))
		$specialtask = new SpecialTask(DBSafe($_GET['retry']));
	else
		$specialtask = false;

	PutFormData($f,$s,"name",$specialtask ? $specialtask->getData('name') : "" ,"text",1,50);



	$testname = DBSafe(GetFormData($f, $s, 'name'));
	if (QuickQuery("select * from audiofile where userid = {$USER->id} and deleted = 0 and name = '" .
		  $testname . "' and id != '" . $audio->id. "'")) {
		error('This audio file name is already in use, a unique one was generated');
		$testname = DBSafe(GetFormData($f, $s, 'name')) . ' ' . date("F jS, Y h:i a");
		PutFormData($f, $s, 'name', $testname, 'text', 1, 50, true); // Repopulate the form/session data with the generated name
	}

	PutFormData($f,$s,"listid",$specialtask ? $specialtask->getData('listid') : 0);
	PutFormData($f,$s,"jobtypeid",$specialtask ? $specialtask->getData('jobtypeid') : end($VALIDJOBTYPES)->id);

	if ($specialtask) {
		$phone = Phone::format($specialtask->getData('phonenumber'));
	} else {
		if ($USER->phone)
			$phone = Phone::format($USER->phone);
		else
			$phone = "";
	}
	PutFormData($f,$s,"phone",$phone,"text","2","20"); // 20 is the max to accomodate formatting chars
}


$TITLE = 'EasyCall';

include_once('popup.inc.php');

NewForm($f);

buttons(submit($f, $s, 'submit','callmetorecord'), button('cancel', 'window.close()'));
startWindow("EasyCall");

?>
	<table border="0" cellpadding="3" cellspacing="0" width="400">
		<tr>
			<th align="right" class="windowRowHeader bottomBorder" style="width: 100px;">Job&nbsp;Name:&nbsp;<?= help("EasyCall_Name", null, 'small'); ?></td>
			<td class="bottomBorder"><? NewFormItem($f,$s,"name","text",30); ?></td>
		</tr>
		<tr>
			<th align="right" class="windowRowHeader bottomBorder" style="width: 100px;">Priority:&nbsp;<?= help('EasyCall_Priority', NULL, "small"); ?></td>
			<td class="bottomBorder">

<?
				NewFormItem($f,$s,"jobtypeid", "selectstart");
				foreach ($VALIDJOBTYPES as $item) {
					NewFormItem($f,$s,"jobtypeid", "selectoption", $item->name, $item->id);
				}
				NewFormItem($f,$s,"jobtypeid", "selectend");
?>
			</td>
		</tr>

		<tr>
			<th align="right" class="windowRowHeader bottomBorder" style="width: 100px;">List: <?= help('EasyCall_List', NULL, "small"); ?></td>
			<td class="bottomBorder">

<?
				$peoplelists = DBFindMany("PeopleList",", (name +0) as foo from list where userid=$USER->id and deleted=0 order by foo,name");
				NewFormItem($f,$s,"listid", "selectstart");
				NewFormItem($f,$s,"listid", "selectoption", "-- Select a list --", NULL);
				foreach ($peoplelists as $plist) {
					NewFormItem($f,$s,"listid", "selectoption", $plist->name, $plist->id);
				}
				NewFormItem($f,$s,"listid", "selectend");
?>
			</td>
		</tr>

		<tr>
			<th align="right" class="windowRowHeader bottomBorder" style="width: 100px;">Call&nbsp;Me&nbsp;At:&nbsp;<?= help("EasyCall_PhoneNumber", null, 'small'); ?></td>
			<td class="bottomBorder"><? NewFormItem($f,$s,"phone","text",20); ?></td>
		</tr>
		<tr><td colspan=2 style="padding: 10px;">If dialing an outside line, please include the area code.</td><tr>
	</table>

<?
endWindow();
buttons();
include_once('popupbottom.inc.php');

?>