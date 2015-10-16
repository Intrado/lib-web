<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
include_once("inc/common.inc.php");
include_once("inc/securityhelper.inc.php");
include_once("inc/table.inc.php");
include_once("inc/html.inc.php");
include_once("inc/utils.inc.php");
include_once("inc/form.inc.php");
include_once("obj/NotificationType.obj.php");
include_once("obj/Setting.obj.php");
include_once("obj/Phone.obj.php");

if (!isset($_REQUEST['api'])) {
	header("HTTP/1.1 404 NotFound");
	exit();
}

if (!$USER->authorize('managesystem')) {
	header("HTTP/1.1 403 Forbidden");
	exit();
}

$ntid = $_REQUEST['ntid'];

if (!strlen($ntid)) {
	header("HTTP/1.1 404 NotFound");
	exit();
}

$form = "setting";
$section = "main";

if (!CheckFormSubmit($form, $section)) {
	header("HTTP/1.1 404 NotFound");
	exit();
}

// API-MODE requests are state-less -- clear any left-over formdata from session
//
ClearFormData($form);
MergeSectionFormData($form, $section);

$systemprioritynames = array(
	"1" => "Emergency",
	"2" => "High Priority",
	"3" => "General");

foreach($systemprioritynames as $index => $name){
	$types[$index] = DBFindMany('NotificationType', "from notificationtype where deleted=0 and systempriority = '" . $index . "' and type = 'job' order by name");
}

$maxphones = getSystemSetting("maxphones", 3);
$maxemails = getSystemSetting("maxemails", 2);
$maxsms = getSystemSetting("maxsms", 2);
$maxcolumns = max($maxphones, $maxemails, $maxsms);

$dtype = null;

foreach($systemprioritynames as $index => $name) {
	foreach($types[$index] as $type) {
		if ($ntid == (int)$type->id) {
			$dtype = $type;
			break;
		}
	}
}

if ($dtype) {
	// Name can be modified for General priority notification types only!
	//
	if($dtype->systempriority == 3) {
		if(QuickQuery("select count(*) from notificationtype where not deleted and name = '" . DBSafe(strtolower(GetFormData($form, $section, "jobtypename" . $dtype->id))) . "'")) {
			header("HTTP/1.1 409 Conflict");
			header("Content-Type: application/json");
			exit(json_encode(Array("code" => "nameNotAvailable")));
		}

		$dtype->name = GetFormData($form, $section, "jobtypename" . $dtype->id);
	}

	$dtype->info = GetFormData($form, $section, "jobtypedesc" . $dtype->id);
	$dtype->update();

	QuickUpdate("Begin");
	QuickUpdate("delete from jobtypepref where jobtypeid = '" . $dtype->id . "'");
	$values = array();

	for($i=0; $i<$maxphones; $i++){
		$values[] = "('" . $dtype->id . "','phone','" . $i . "','"
			. DBSafe(GetFormData($form, $section, "jobtype" . $dtype->id . "phone" . $i)) . "')";
	}
	for($i=0; $i<$maxemails; $i++){
		$values[] = "('" . $dtype->id . "','email','" . $i . "','"
			. DBSafe(GetFormData($form, $section, "jobtype" . $dtype->id . "email" . $i)) . "')";
	}
	if(getSystemSetting("_hassms")){
		if($dtype->type == 'job'){
			for($i=0; $i<$maxsms; $i++){
				$values[] = "('" . $dtype->id . "','sms','" . $i . "','"
					. DBSafe(GetFormData($form, $section, "jobtype" . $type->id . "sms" . $i)) . "')";
			}
		}
	}
	QuickUpdate("insert into jobtypepref (jobtypeid, type, sequence, enabled)
					values " . implode(",", $values));
	QuickUpdate("Commit");
} else {
	header("HTTP/1.1 404 NotFound");
	exit();
}
