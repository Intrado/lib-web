<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("common.inc.php");
require_once("../inc/table.inc.php");
require_once("../inc/html.inc.php");
require_once("../inc/utils.inc.php");
require_once("../obj/Validator.obj.php");
require_once("../obj/Form.obj.php");
require_once("../obj/FormItem.obj.php");
require_once("Server.obj.php");
require_once("Service.obj.php");
////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$MANAGERUSER->authorized("manageserver"))
	exit("Not Authorized");

////////////////////////////////////////////////////////////////////////////////
// Action/Request Processing
////////////////////////////////////////////////////////////////////////////////
if (isset($_GET['serverid'])) {
	$_SESSION['servicenew'] = $_GET['serverid'] + 0;
	redirect();
}

////////////////////////////////////////////////////////////////////////////////
// Optional Form Items And Validators
////////////////////////////////////////////////////////////////////////////////

////////////////////////////////////////////////////////////////////////////////
// Form Data
////////////////////////////////////////////////////////////////////////////////
$serverid = false;
if (isset($_SESSION['servicenew']))
	$serverid = $_SESSION['servicenew'];

$server = new Server($serverid);

if (!$server->hostname)
	exit("Missing/Invalid server id!");

// Form Items
$formdata = array(_L("%s New Service Profile", $server->hostname));
$formdata["type"] = array( 
		"label" => _L('Service Type'),
		"value" => "",
		"validators" => array(
			array("ValInArray", 'values' => array_keys(Service::getTypes()))),
		"control" => array("SelectMenu", 'values' => Service::getTypes()),
		"helpstep" => 1
	);
$formdata["notes"] = array( 
		"label" => _L('Notes'),
		"value" => "",
		"validators" => array(array("ValRequired")),
		"control" => array("TextArea", "cols"=>55),
		"helpstep" => 1
	);
$formdata["runmode"] = array( 
		"label" => _L('Server run mode'),
		"value" => "active",
		"validators" => array(
			array("ValInArray", 'values' => array_keys(Service::getRunModes()))),
		"control" => array("SelectMenu", 'values' => Service::getRunModes()),
		"helpstep" => 1
	);

$helpsteps = array ();

$buttons = array(submit_button(_L('Save'),"submit","tick"),
				icon_button(_L('Cancel'),"cross",null,"servicelist.php?serverid=". $server->id));
$form = new Form("servicenewform",$formdata,$helpsteps,$buttons);

////////////////////////////////////////////////////////////////////////////////
// Form Data Handling
////////////////////////////////////////////////////////////////////////////////

//check and handle an ajax request (will exit early)
//or merge in related post data
$form->handleRequest();

$datachange = false;
$errors = false;
//check for form submission
if ($button = $form->getSubmit()) { //checks for submit and merges in post data
	$ajax = $form->isAjaxSubmit(); //whether or not this requires an ajax response	
	
	if ($form->checkForDataChange()) {
		$datachange = true;
	} else if (($errors = $form->validate()) === false) { //checks all of the items in this form
		$postdata = $form->getData(); //gets assoc array of all values {name:value,...}
		Query("BEGIN");
		
		// create a service entry
		$service = new Service();
		$service->serverid = $server->id;
		$service->type = $postdata['type'];
		$service->notes = $postdata['notes'];
		$service->runmode = $postdata['runmode'];
		$service->create();
		
		Query("COMMIT");
		
		unset($_SESSION['servicenew']);
		
		if ($ajax)
			$form->sendTo("serviceedit.php?id=". $service->id);
		else
			redirect("serviceedit.php?id=". $service->id);
	}
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$PAGE = "service:new";
$TITLE = _L('Create Service Profile');

include_once("nav.inc.php");

startWindow(_L('New Service Profile'));
echo $form->render();
endWindow();
include_once("navbottom.inc.php");
?>