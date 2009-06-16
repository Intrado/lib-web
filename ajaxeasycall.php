<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("inc/common.inc.php");
require_once("obj/Message.obj.php");
require_once("obj/MessagePart.obj.php");
require_once("obj/AudioFile.obj.php");
require_once("obj/FieldMap.obj.php");
require_once("obj/SpecialTask.obj.php");
require_once("obj/Phone.obj.php");

global $USER;

// Check user authorization and bail out if not auth
if (!$USER->authorize("starteasy"))
	exit();

function taskNew($phone,$language) {
	if (!$phone)
		return array("error"=>"badphone");
	if (!$language)
		return array("error"=>"badlanguage");
	global $USER;
	$task = new SpecialTask("new");
	$task->status = "new";
	$task->type = 'EasyCall';
	$task->setData('phonenumber', dbsafe($phone));
	$task->lastcheckin = date("Y-m-d H:i:s");
	$task->setData('progress', _L("Creating Call"));
	$task->setData('callerid', getSystemSetting('callerid'));
	$task->setData('name', "JobWizard-" . date("M j, Y g:i a"));
	$task->setData('origin', "start");
	$task->setData('userid', $USER->id);
	$task->setData('listid', 0);
	$task->setData('jobtypeid', 0);
	$task->setData('count', 0);
	if (is_array($language)) {
		$task->setData('totalamount', count($language));
		$task->setData('currlang', $language[0]);
		$count = 0;
		foreach ($language as $lang)
			$task->setData("language" . $count++, $lang);
	} else {
		$task->setData('totalamount', 1);
		$task->setData('currlang', $language);
		$task->setData("language0", $language);
	}
	$task->setData('progress', _L("Creating Call"));
	$task->status = "queued";
	$task->create();
	QuickUpdate("call start_specialtask(" . $task->id . ")");
	return array("id"=>$task->id);
}

function taskStatus($id) {
	if (!$id)
		return false;
	$task = new SpecialTask($id);
	if (!$task->status)
		return array("error"=>"notask");
	// Parse the task data
	$langdata = array();
	for($x=0; $x<$task->getData('totalamount'); $x++)
		if ($task->getData("message$x"))
			$langdata[$task->getData("language$x")] = $task->getData("message$x");	
	return array(
		"id"=>$task->id,
		"status"=>$task->status,
		"language"=>$langdata?$langdata:false,
		"progress"=>$task->getData('progress'),
		"currlang"=>$task->getData('currlang'),
		"error"=>$task->getData('error'),
		"count"=>$task->getData('count'),
		"totalamount"=>$task->getData('totalamount')
		);
}

//////////////////////////////////////////////////////////
// POST data is a request to start a new special task
// GET data is request for task status
//////////////////////////////////////////////////////////

$id = false;

if (isset($_POST['phone']) && isset($_POST['language'])) {
	$id = "new";
	$language = $_POST['language']; 
	$phone = Phone::parse($_POST['phone']);
}

if (isset($_GET['id'])) {
	$id = $_GET['id']+0;
}

/////////////////////////////////////////////////////////
// If it is a "new" task ID then create a new one
// Otherwise, return the status of the task id
/////////////////////////////////////////////////////////

header("Content-Type: application/json");
if ($id === "new")
	echo json_encode(taskNew($phone,$language));
else
	echo json_encode(taskStatus($id));
exit();
?>
