<?
////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$MANAGERUSER->authorized("manageserver"))
	exit("Not Authorized");

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
			
			$dotpos = strpos($name,".");
			$section = substr($name, 0, $dotpos);
			$shortname = substr($name, $dotpos + 1);
			
			if (!isset($props[$section]))
				$props[$section] = array();
			
			$props[$section][$shortname] = $value;
		}
	}
	return $props;
}

////////////////////////////////////////////////////////////////////////////////
// Form Data
////////////////////////////////////////////////////////////////////////////////
$server = new Server($service->serverid);

$cvs = new CvsServer($SETTINGS['servermanagement']['cvsurl']);

// check out this server's existing props file
$file = $cvs->co("{$server->hostname}/{$service->runmode}/commsuite/commsuite.properties");
if ($file) {
	$currentprops = parseCommSuiteProperties($file);
} else {
	// server doesn't have a props file, copy the default and import it 
	$cvs->copyDefault($server->hostname);
	$currentprops = array();
}

// load default props file.
$file = $cvs->co("default/{$service->runmode}/commsuite/commsuite.properties");
$defaultprops = parseCommSuiteProperties($file);

// merge the two, so new props show up.
$props = array_merge($defaultprops, $currentprops);

// Form Items
$formdata = array();
foreach ($props as $section => $sectionprops) {
	$formdata[] = $section;
	foreach ($sectionprops as $name => $value) {
		$formdata["$section.$name"] = array(
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
				icon_button(_L('Cancel'),"cross",null,"servicelist.php"));
$form = new Form("serverpropertiesform",$formdata,$helpsteps,$buttons);

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
			$form->sendTo("servicelist.php");
		else
			redirect("servicelist.php");
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
<? //Validator::load_validators(); ?>
</script>
<?

startWindow(_L('CommSuite Properties'));
echo $form->render();
endWindow();
include_once("navbottom.inc.php");
?>