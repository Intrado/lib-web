<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("inc/common.inc.php");
require_once("obj/SpecialTask.obj.php");
require_once("obj/Phone.obj.php");
require_once("obj/Content.obj.php");
require_once("obj/AudioFile.obj.php");

global $USER;

// Check user authorization and bail out if not auth
if (!$USER->authorize("starteasy"))
	exit();

// set the header for the return data
header("Content-Type: application/json");


function taskNew($phone) {
	global $USER;

	// Parse the phone to remove invalid junk and validate that it's a phone number
	$phone = Phone::parse($phone);
	if (!$phone)
		return array("error"=>"badphone");

	// create a new special task
	$task = new SpecialTask();
	$task->userid = $USER->id;
	$task->status = 'new';
	$task->type = 'EasyCall';
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
	global $USER;

	if (!$id)
		return false;

	// get the task data
	// if its an empty task (no status) or doesn't belong to this user return an error
	$task = new SpecialTask($id);
	if (!$task->status || $task->userid !== $USER->id)
		return array("error"=>"notask");

	// Parse and return the task progress data
	return array(
		"status"=>$task->status,
		"progress"=>$task->getData('progress'),
		"error"=>$task->getData('error')
		);
}

// creates an audiofile from the specialtask id requested
function createaudiofile($id) {
	global $USER;

	// get the task data
	$task = new SpecialTask($id);

	// check that the user owns the specialtask and it's done
	if ($task->status !== 'done' || $task->userid !== $USER->id)
		return array("error"=>"notask");

	// check that there is content in task data and it exists
	$contentid = $task->getData('contentid');
	$content = DBFind("Content", "from content where id = ?", false, array($contentid));
	if (!$content)
		return array("error"=>"saveerror");

	// create an audio file belonging to this user and return it's id
	query("BEGIN");
	$audiofile = new AudioFile();
	$audiofile->userid = $USER->id;
	$audiofile->name = "EasyCall - " . date('Y-m-d H:i:s');
	$audiofile->description = "EasyCall - " . date('Y-m-d H:i:s');
	$audiofile->contentid = $content->id;
	$audiofile->recorddate = date('Y-m-d H:i:s');
	$audiofile->deleted = 1;
	$audiofile->permanent = 0;
	$audiofile->messagegroupid = 0;
	$audiofile->create();
	query("COMMIT");

	// return success or failure based on audiofile id (should have one if it was created)
	if ($audiofile->id)
		return array("audiofileid"=>$audiofile->id);
	else
		return array("error"=>"saveerror");

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

	// create and return audiofile id
	case "getaudiofile":
		$id = $_REQUEST['id']+0;
		echo json_encode(createaudiofile($id));
		break;

	// unknown request
	default:
		error_log("Unknown action request:" . $_REQUEST['action']);
		echo json_encode(array("error"=>"badaction"));
}

exit();
?>
