<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("inc/common.inc.php");
require_once("obj/SpecialTask.obj.php");
require_once("obj/Phone.obj.php");

global $USER;

// Check user authorization and bail out if not auth
if (!$USER->authorize("starteasy"))
	exit();

// set the header for the return data
header("Content-Type: application/json");


function taskNew($phone) {
	// Parse the phone to remove invalid junk and validate that it's a phone number
	$phone = Phone::parse($phone);
	if (!$phone)
		return array("error"=>"badphone");

	// create a new special task
	$task = new SpecialTask();
	$task->status = 'new';
	$task->lastcheckin = date("Y-m-d H:i:s");

	$task->setData('phonenumber', $phone);
	$task->setData('progress', _L("Creating Call"));
	$task->setData('callerid', getSystemSetting('callerid'));

	// change status to queued so it gets picked up
	$task->status = 'queued';
	$task->create();
	QuickUpdate("call start_specialtask(" . $task->id . ")");

	// return the task id that was created
	return array("id"=>$task->id);
}

function taskStatus($id) {
	if (!$id)
		return false;

	// get the task data, if its an empty task (no status) return an error
	$task = new SpecialTask($id);
	if (!$task->status)
		return array("error"=>"notask");

	// Parse and return the task data
	return array(
		"id"=>$task->id,
		"status"=>$task->status,
		"contentid"=>$task->getData('contentid'),
		"progress"=>$task->getData('progress'),
		"error"=>$task->getData('error')
		);
}

//////////////////////////////////////////////////////////
// request should include an ACTION with the desired
// behavior.
//////////////////////////////////////////////////////////

switch ($_REQUEST['action']) {
	// create a new call me request, returns new task id
	case "new":
		$phone = $_REQUEST['phone'];
		echo json_encode(taskNew($phone));
		break;

	// return task status by id
	case "status":
		$id = $_REQUEST['id']+0;
		echo json_encode(taskStatus($id));
		break;
}

exit();
?>
