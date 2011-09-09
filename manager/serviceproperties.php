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
// Functions, Form Items And Validators
////////////////////////////////////////////////////////////////////////////////
function parseCommSuiteProperties($filepath) {
	$props = array();
	$f = fopen($filepath, "r");
	while ($line = fgets($f)) {
		// TODO: parse out special comments that provide validators or a list of values
		if (trim($line) && substr($line, 0, 1) != "#") {
			$equalspos = strpos($line,"=");
			$name = substr($line, 0, $equalspos);
			$value = substr($line, $equalspos + 1);
			
			$dotpos = strpos($line,".");
			$section = substr($line, 0, $dotpos);
			
			if (!isset($props[$section]))
				$props[$section] = array();
			
			$props[$section][$name] = $value;
		}
	}
	return $props;
}

////////////////////////////////////////////////////////////////////////////////
// Form Data
////////////////////////////////////////////////////////////////////////////////
$name = $notes = "";
$production = false;
$commsuitejmxport = 3100;
$server = new Server($serverid);
if ($server->id) {
	$name = $server->hostname;
	$notes = $server->notes;
	$production = $server->production;
	$commsuitejmxport = $server->getSetting("commsuitejmxport");
} else {
	exit("Missing/Invalid Server ID");
}

// load default props file.
// TODO: check this out from somewhere
$defaultprops = parseCommSuiteProperties("/tmp/commsuite.properties");

// TODO: check out this server's existing props file
$currentprops = array();

// merge the two, so new props show up.
$props = array_merge($defaultprops, $currentprops);

// Form Items
$formdata = array();
foreach ($props as $section => $sectionprops) {
	$formdata[] = $section;
	foreach ($sectionprops as $name => $value) {
		$formdata[$name] = array(
				"label" => $name,
				"value" => $value,
				"validators" => array(),
				"control" => array("TextField"),
				"helpstep" => 1
			);
	}
}

$helpsteps = array ();

$buttons = array(submit_button(_L('Save'),"submit","tick"),
				icon_button(_L('Cancel'),"cross",null,"serverlist.php"));
$form = new Form("serverpropertiesform",$formdata,$helpsteps,$buttons,"vertical");

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
		
		// TODO: commit the new props file
		
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
$TITLE = _L('Create/Edit Properties');

include_once("nav.inc.php");

// Optional Load Custom Form Validators
?>
<script type="text/javascript">
<? Validator::load_validators(); ?>
</script>
<?

startWindow(_L('CommSuite Properties'));
echo $form->render();
endWindow();
include_once("navbottom.inc.php");

?>