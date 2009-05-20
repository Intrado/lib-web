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
		$data = sane_parsestr($task->data);
		$langmap = json_decode($data['langmap'], true);
		$langdata = array();
		foreach($data as $key => $value) {
			if (substr($key,0,7) == "message") {
				$msgindex = substr($key,7);
				if (isset($reqlangs[$langmap["language$msgindex"]])) {
					if (isset($langdata[$langmap["language$msgindex"]]))
						$langdata[$langmap["language$msgindex"]]["message"] = $value;
					else
						$langdata[$langmap["language$msgindex"]] = array("message" => $value, "language"=>$reqlangs[$langmap["language$msgindex"]]);
				}
				$task->delData($key);
			}
			
			if (substr($key,0,8) == "language") 
				$task->delData($key);
		}
	}
	
	foreach ($reqlangs as $langid => $language) {
		if (!isset($langdata[$langid]))
			$langdata[$langid] = array("language"=>$language, "message"=>"");
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
		$currlang = "Default";
	}
	if (isset($_GET['language'])) {
		$reqlangs = json_decode($_GET['language'],true);
		$tasklangs = getTaskLangs($reqlangs);
		$langs = array();
		$totalamount = count($tasklangs);
		$count = 0;
		$langindex = 0;
		if (!isset($currlang))
			$currlang = "";
		
		foreach ($tasklangs as $langid => $values) {
			$langs["language$langindex"] = $langid;
			$task->delData("language$langindex");
			$task->delData("message$langindex");
			$task->setData("language$langindex", $values['language']);
			if ($values['message']) {
				$task->setData("message$langindex", $values['message']);
				$count = $langindex + 1;
				$currlang = $values['language'];
			}
			$langindex++;
		}
		$task->setData('count', $count);
		$task->setData('totalamount', $totalamount);
		$task->setData('langmap',json_encode($langs));
		$task->setData('currlang', $currlang);
		if ($task->id !== "new")
			$task->update();
	}
	
	if ($task->id == "new") {
		$task->create();
	}
		
	if (isset($_GET['phonenumber'])) {
		$task->setData('phonenumber', dbsafe($_GET['phonenumber']));
	}
	
	if (($task->status == "done" || $task->status == "new") && isset($_GET['start'])) {
		$task->delData('error');
		$task->setData('progress', _L("Creating Call"));
		$task->status = "queued";
		$task->update();
		QuickUpdate("call start_specialtask(" . $task->id . ")");
	}

	// Parse the task data
	$langdata = array();
	$langnametoid = array();
	$data = sane_parsestr($task->data);
	$langmap = json_decode($data['langmap'], true);
	foreach($data as $key => $value) {
		if (substr($key,0,7) == "message") {
			$msgindex = substr($key,7);
			$langdata[$langmap["language$msgindex"]] = $value;
		}
		if (substr($key,0,8) == "language") {
			$msgindex = substr($key,8);
			$langnametoid[$value] = $langmap["language$msgindex"];
		}
	}
	
	// Return the task info
	$return = array(
		"id"=>$task->id,
		"language"=>$langdata,
		"progress"=>$data['progress'],
		"currlang"=>isset($langnametoid[$data['currlang']])?$langnametoid[$data['currlang']]:"",
		"error"=>isset($data['error'])?$data['error']:"",
		"count"=>$data['count'],
		"totalamount"=>$data['totalamount']);
}

header("Content-Type: application/json");
echo json_encode($return);
exit();	
?>
