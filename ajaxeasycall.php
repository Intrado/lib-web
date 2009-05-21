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

global $USER;
$return = false;

function getTaskLangs($reqlangs) {
	global $task;
	if ($task->id == "new") {
		$langdata = array();
	} else {
		$langdata = array();
		for($x=0; $x<$task->getData('totalamount'); $x++) {
			if ($task->getData("message$x") && in_array($task->getData("language$x"), $reqlangs))
				$langdata[$task->getData("language$x")] = $task->getData("message$x");
			
			$task->delData("language$x");
			$task->delData("message$x");
		}
	}
	
	foreach ($reqlangs as $language) {
		if (!isset($langdata[$language]))
			$langdata[$language] = "";
	}
	return $langdata;
}

if ($USER->authorize("starteasy") && isset($_GET['id'])) {
	// Create a task object based on the requested ID
	$task = new SpecialTask(dbsafe($_GET['id']));
	error_log("Request ID: " . $_GET['id']);
	//new tasks have to have all their defaults set up
	if($task->id == "new") {
		$task->status = "new";
		$task->type = 'EasyCall';
		$task->setData('phonenumber', dbsafe($_GET['phonenumber']));
		$task->lastcheckin = date("Y-m-d H:i:s");
		$task->setData('progress', _L("Creating Call"));
		$task->setData('callerid', getSystemSetting('callerid'));
		$task->setData('name', "JobWizard-" . date("M j, Y g:i a"));
		$task->setData('origin', "start");
		$task->setData('userid', $USER->id);
		$task->setData('listid', 0);
		$task->setData('jobtypeid', 0);
	}
	if (isset($_GET['language'])) {
		$reqlangs = json_decode($_GET['language'],true);
		$tasklangs = getTaskLangs($reqlangs);
		$langs = array();
		$totalamount = count($tasklangs);
		$count = 0;
		$langindex = 0;
		
		foreach ($tasklangs as $language => $message) {
			$task->setData("language$langindex", $language);
			if ($message) {
				$task->setData("message$langindex", $message);
				$count = $langindex + 1;
			}
			$langindex++;
		}
		
		if ($task->getData("language$count")) {
			$currlang = $task->getData("language$count");
		} else {
			$currlang = "";
		}
		$task->setData('count', $count);
		$task->setData('totalamount', $totalamount);
		$task->setData('currlang', $currlang);
		if ($task->id !== "new")
			$task->update();
	}
	
	if ($task->id == "new") {
		$task->create();
	}
		
	if (isset($_GET['phonenumber'])) {
		$task->delData('error');		
		$task->setData('phonenumber', dbsafe($_GET['phonenumber']));
		$task->update();
	}
	
	if (($task->status == "done" || $task->status == "new") 
		&& isset($_GET['start']) 
		&& (($task->getData('count') + 0) < ($task->getData('totalamount') + 0))) {
		$task->delData('error');
		$task->setData('progress', _L("Creating Call"));
		$task->status = "queued";
		$task->update();
		QuickUpdate("call start_specialtask(" . $task->id . ")");
	}

	// Parse the task data
	$langdata = array();
	for($x=0; $x<$task->getData('totalamount'); $x++)
		if ($task->getData("message$x"))
			$langdata[$task->getData("language$x")] = $task->getData("message$x");
	
	// Return the task info
	$return = array(
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

header("Content-Type: application/json");
echo json_encode($return);
exit();	
?>
