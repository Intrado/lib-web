<?
////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$MANAGERUSER->authorized("manageserver"))
	exit("Not Authorized");

////////////////////////////////////////////////////////////////////////////////
// Functions, Form Items And Validators
////////////////////////////////////////////////////////////////////////////////

////////////////////////////////////////////////////////////////////////////////
// Form Data
////////////////////////////////////////////////////////////////////////////////
// load default props file.
// TODO: check this out from somewhere
$defaultprops = parse_ini_file("../inc/settings.ini.php", true);

// TODO: check out this server's existing props file
$currentprops = array();

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