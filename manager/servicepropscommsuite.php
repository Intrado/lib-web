<?
////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$MANAGERUSER->authorized("manageserver"))
	exit("Not Authorized");

////////////////////////////////////////////////////////////////////////////////
// Form Data
////////////////////////////////////////////////////////////////////////////////
$server = new Server($service->serverid);

$cvs = new CvsServer($SETTINGS['servermanagement']['cvsurl']);

// check out this server's existing props file
$propsfile = $cvs->co("{$server->hostname}/{$service->runmode}/commsuite/service.properties");
if (!$propsfile) {
	// server doesn't have a props file, copy the default and import it 
	$cvs->copyDefault($server->hostname);
	$propsfile = $cvs->co("default/{$service->runmode}/commsuite/service.properties");
}

// Form Items
$formdata = array();
$formdata["propsfile"] = array(
	"label" => "Properties",
	"value" => file_get_contents($propsfile),
	"validators" => array(),
	"control" => array("TextArea", "cols"=> "120", "rows"=>"40"),
	"helpstep" => 1
);

$cvs->cleanupTempFiles();

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
		
		// check out file, overwrite with formdata, commit file
		$propsfile = $cvs->co("{$server->hostname}/{$service->runmode}/commsuite/service.properties");
		$fp = fopen($propsfile, "w");
		fwrite($fp, $postdata['propsfile']);
		fclose($fp);
		
		$cvs->commit("{$server->hostname}/{$service->runmode}/commsuite/service.properties");
		$cvs->cleanupTempFiles();
		
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