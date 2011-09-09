<?
////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!isset($_SESSION['serviceedit']['serviceid']))
	exit("Not Authorized");

////////////////////////////////////////////////////////////////////////////////
// Optional Form Items And Validators
////////////////////////////////////////////////////////////////////////////////

////////////////////////////////////////////////////////////////////////////////
// Form Data
////////////////////////////////////////////////////////////////////////////////
if (isset($_SESSION['serviceedit']['serviceid']))
	$serviceid = $_SESSION['serviceedit']['serviceid'];
else
	$serviceid = false;
	
$service = new Service($serviceid);

if (!$service->type)
	exit("Not Authorized");

$server = new Server($service->serverid);
if (!$server->hostname)
	exit("Missing/Invalid server id!");

// Form Items
$formdata = array();
$formdata["todo"] = array( 
		"label" => _L('TODO'),
		"value" => "",
		"validators" => array(array("ValRequired")),
		"control" => array("TextField", "maxlength"=>255, "size"=>50),
		"helpstep" => 1
	);

$helpsteps = array ();

$buttons = array(submit_button(_L('Save'),"submit","tick"),
				icon_button(_L('Cancel'),"cross",null,"servicelist.php?serverid=". $server->id));
$form = new Form("serviceeditform",$formdata,$helpsteps,$buttons);

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
		
		Query("COMMIT");
		if ($ajax)
			$form->sendTo("servicelist.php?serverid=". $server->id);
		else
			redirect("servicelist.php?serverid=". $server->id);
	}
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$PAGE = "service:new";
$TITLE = _L('Edit Service');

include_once("nav.inc.php");

startWindow(_L('Host: %1$s Service: %2$s Mode: %3$s', $server->hostname, $service->type, $service->runmode));
echo $form->render();
endWindow();
include_once("navbottom.inc.php");
?>