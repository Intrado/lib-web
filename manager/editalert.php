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
require_once("inc/importalert.inc.php");


////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////

if (!$MANAGERUSER->authorized("imports"))
	exit("Not Authorized");

////////////////////////////////////////////////////////////////////////////////
// Action/Request Processing
////////////////////////////////////////////////////////////////////////////////'

if (isset($_GET['customerid']) &&  isset($_GET['importalertruleid']) ) {
	$_SESSION['alertinfo'] = json_encode(array(
		"customerid" => $_GET['customerid']+0,
		"importalertruleid" => $_GET['importalertruleid']+0)
	);
	redirect();
}

if (!isset($_SESSION['alertinfo'])) {
	notice("Unable to find alert");
	redirect("importalerts.php");
}

$alertinfo = json_decode($_SESSION['alertinfo'],true);
$customerid = $alertinfo['customerid'];
$importalertruleid = $alertinfo['importalertruleid'];

////////////////////////////////////////////////////////////////////////////////
// Optional Form Items And Validators
////////////////////////////////////////////////////////////////////////////////


////////////////////////////////////////////////////////////////////////////////
// Form Data
////////////////////////////////////////////////////////////////////////////////
list($shardid,$dbhost,$dbusername,$dbpassword) = QuickQueryRow("select s.id, s.dbhost, s.dbusername, s.dbpassword from customer c inner join shard s on (c.shardid = s.id) where c.id=?",false,false,array($customerid));
$dsn = 'mysql:dbname=aspshard;host='.$dbhost;
$sharddb = new PDO($dsn, $dbusername, $dbpassword);
$sharddb->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);

Query("use aspshard", $sharddb);

$query = "select importname,name,operation,testvalue,actualvalue,notes from importalert where customerid=? and importalertruleid=?";

$alert = QuickQueryRow($query,true,$sharddb, array($customerid,$importalertruleid));

if ($alert === false) {
	notice("Unable to find alert");
	redirect("importalerts.php");
}

$helpstepnum = 1;
$formdata["alert"] = array(
	"label" => _L('Alert'),
	"control" => array("FormHtml","html" => formatAlert($alert["name"], $alert["operation"], $alert["testvalue"], $alert["actualvalue"])),
	"helpstep" => $helpstepnum
);

$formdata["notes"] = array(
	"label" => _L('Notes'),
	"value" => $alert["notes"],
	"validators" => array(),
	"control" => array("TextArea","rows" => 3, "cols" => 50),
	"helpstep" => $helpstepnum
);

$buttons = array(submit_button(_L('Save'),"submit","tick"),
				icon_button(_L('Cancel'),"cross",null,"importalerts.php"));
$form = new Form("editalert",$formdata,false,$buttons);

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
		
		QuickUpdate("update importalert set notes=? where customerid=? and importalertruleid=?",$sharddb, array($postdata["notes"],$customerid,$importalertruleid));
		
		if ($ajax)
			$form->sendTo("importalerts.php");
		else
			redirect("importalerts.php");
	}
}

////////////////////////////////////////////////////////////////////////////////
// Display Functions
////////////////////////////////////////////////////////////////////////////////

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$TITLE = _L('Edit Alert Notes for Import: %s', $alert["importname"]);

include_once("nav.inc.php");

startWindow(_L('Edit Alert Notes for Import: %s', $alert["importname"]));
echo $form->render();
endWindow();
include_once("navbottom.inc.php");
?>