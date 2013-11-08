<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("inc/common.inc.php");
require_once("inc/securityhelper.inc.php");
require_once("inc/table.inc.php");
require_once("inc/html.inc.php");
require_once("inc/utils.inc.php");
require_once("obj/Validator.obj.php");
require_once("obj/Form.obj.php");
require_once("obj/FormItem.obj.php");

require_once("obj/TopicDataManager.obj.php");
require_once("obj/Topic.obj.php");


////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!(getSystemSetting('_hasquicktip') && $USER->authorize('tai_canmanagetopics'))) {
	redirect('unauthorized.php');
}

///////////////////////////////////////////////////////////////////////////////
// Request processing:
///////////////////////////////////////////////////////////////////////////////

if(isset($_GET['topicid'])) {
	if ($_GET['topicid'] == 'new') {
		$topicname = "";
	} else {
		$topicid = $_GET['topicid'];
		$topicname = QuickQuery("select name from tai_topic where id = ?", false, array($topicid));
		if (!$topicname) {
			redirect('unauthorized.php');
		}
	}

} else {
	redirect("topicdatamanager.php");
}

////////////////////////////////////////////////////////////////////////////////
// Optional Form Items And Validators
////////////////////////////////////////////////////////////////////////////////

////////////////////////////////////////////////////////////////////////////////
// Form Data
////////////////////////////////////////////////////////////////////////////////

$namevalidators = array(
	array("ValRequired"),
	array("ValLength","min" => 1,"max" => 255)
);

$formdata = array();
$helpsteps = array();

$formdata["topicname"] = array(
	"label" => _L('Topic Name'),
	"value" => $topicname,
	"validators" => $namevalidators,
	"control" => array("TextField", "size" => 30, "maxlength" => 255)
);

$buttons = array(submit_button(_L('Save'), "submit", "tick"),
								 icon_button(_L('Cancel'),"cross",null,"topicdatamanager.php"));
$form = new Form("editorgform",$formdata,$helpsteps,$buttons);

////////////////////////////////////////////////////////////////////////////////
// Form Data Handling
////////////////////////////////////////////////////////////////////////////////

$topicDataManager = new TopicDataManager();

//check and handle an ajax request (will exit early)
//or merge in related post data
$form->handleRequest();


$datachange = false;
$errors = false;

//check for form subission
if ($button = $form->getSubmit()) { //checks for submit and merges in post data
	$ajax = $form->isAjaxSubmit(); //whether or not this requires an ajax response

	if ($form->checkForDataChange()) {

	} else if (($errors = $form->validate()) === false) { //checks all of the items in this form
		$postdata = $form->getData(); //gets assoc array of all values {name:value,...}

		$topicname = trim($postdata['topicname']);

		Query("BEGIN");

		$topic = DBFind("Topic", "from tai_topic where id = ?", false, array($topicid));

		if ($topic) {
			$topic->name = $topicname;
			$topic->update();
		} else {
			$topic = new Topic();
			$topic->name = $topicname;
			$topic->create();
			QuickUpdate("insert into tai_organizationtopic (organizationid, topicid) values (?, ?)",
									false,
									array($topicDataManager->rootOrganizationId(), $topic->id));
		}

		Query("COMMIT");
		if ($ajax) {
			$form->sendTo("topicdatamanager.php");
		} else {
			redirect("topicdatamanager.php");
		}
	}
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$PAGE = "admin:settings";
$TITLE = "";

include_once("nav.inc.php");

startWindow(isset($topicid)?_L('Edit Topic'):_L('Create Topic'));

echo $form->render();

endWindow();
include_once("navbottom.inc.php");
?>