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
////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$MANAGERUSER->authorized("manageserver"))
	exit("Not Authorized");

////////////////////////////////////////////////////////////////////////////////
// Action/Request Processing
////////////////////////////////////////////////////////////////////////////////
$serverid = false;
if (isset($_GET['id'])) 
	$serverid = $_GET['id'];

////////////////////////////////////////////////////////////////////////////////
// Optional Form Items And Validators
////////////////////////////////////////////////////////////////////////////////
class ValServerExists extends Validator {
	var $onlyserverside = true;
	function validate ($value, $args) {
		// check that dns can resolve this hostname
		if (gethostbyname($value) == $value)
			return "Unknown host, is it resolvable?";
		// check that it isn't already in the database
		$querylimit="";
		$queryargs = array($value);
		if ($args['thisid']) {
			$querylimit = " and id != ? ";
			$queryargs[] = $args['thisid'];
		}
		if (QuickQuery("select 1 from server where name = ? ". $querylimit, false, $queryargs))
			return "Server already exists!";
		return true;
	}
}

////////////////////////////////////////////////////////////////////////////////
// Form Data
////////////////////////////////////////////////////////////////////////////////
$name = $notes = "";
$production = false;
$commsuitejmxport = 3100;
$server = new Server($serverid);
if ($server->id) {
	$name = $server->name;
	$notes = $server->notes;
	$production = $server->production;
	$commsuitejmxport = $server->getSetting("commsuitejmxport");
}

// Form Items
$formdata = array();
if ($server->id)
	$pagetitle = "Edit existing server entry";
else
	$pagetitle = "Create new server entry";
$formdata[] = $pagetitle;
$formdata["name"] = array( 
		"label" => _L('Host Name'),
		"value" => $name,
		"validators" => array(
			array("ValRequired"),
			array("ValServerExists", "thisid"=>$serverid)),
		"control" => array("TextField", "maxlength"=>50),
		"helpstep" => 1
	);
$formdata["notes"] = array( 
		"label" => _L('Notes'),
		"value" => $notes,
		"validators" => array(array("ValRequired")),
		"control" => array("TextArea", "cols"=>55),
		"helpstep" => 1
	);
$formdata["production"] = array( 
		"label" => _L('Server is in production'),
		"value" => $production,
		"validators" => array(),
		"control" => array("CheckBox"),
		"helpstep" => 1
	);
$formdata["commsuitejmxport"] = array( 
		"label" => _L('CommSuite Service JMX port'),
		"value" => $commsuitejmxport,
		"validators" => array(
			array("ValNumber", "min"=>1000, "max"=>65000)),
		"control" => array("TextField", "maxlength"=>5),
		"helpstep" => 1
	);

$helpsteps = array ();

$buttons = array(submit_button(_L('Save'),"submit","tick"),
				icon_button(_L('Cancel'),"cross",null,"serverlist.php"));
$form = new Form("servereditform",$formdata,$helpsteps,$buttons);

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
		
		// update/create a server entry
		$server->name = $postdata['name'];
		$server->notes = $postdata['notes'];
		$server->production = ($postdata['production']?1:0);
		if ($server->id)
			$server->update();
		else
			$server->create();
		
		// add/remove settings
		$server->setSetting("commsuitejmxport", $postdata['commsuitejmxport']);
		
		Query("COMMIT");
		if ($ajax)
			$form->sendTo("serverlist.php");
		else
			redirect("serverlist.php");
	}
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$PAGE = "server:edit";
$TITLE = _L('Create/Edit Server');

include_once("nav.inc.php");

// Optional Load Custom Form Validators
?>
<script type="text/javascript">
<? Validator::load_validators(array("ValServerExists")); ?>
</script>
<?

startWindow(_L('Individual Server'));
echo $form->render();
endWindow();
include_once("navbottom.inc.php");
?>