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
require_once("../obj/BurstTemplate.obj.php");

////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////

if (!$MANAGERUSER->authorized("pdfbursttemplates")) exit("Not Authorized");

////////////////////////////////////////////////////////////////////////////////
// Action/Request Processing
////////////////////////////////////////////////////////////////////////////////'
if (isset($_GET['cid'])) {
	$_SESSION['bursttemplatecid'] = intval($_GET['cid']);
}
if (isset($_GET['id'])) {
	if ($_GET['id'] === "new") {
		$_SESSION['bursttemplateid'] = null;
	} else {
		$_SESSION['bursttemplateid']= intval($_GET['id']);
	}
	redirect();
}

$bursttemplateid = isset($_SESSION['bursttemplateid']) ? $_SESSION['bursttemplateid'] : false;

// Make sure cid is set in the session because we need to change to that customer's database...
if (! isset($_SESSION['bursttemplatecid'])) {
	exit(_L('Missing customer id'));
}

$bursttemplatecid = $_SESSION['bursttemplatecid'];
$custinfo = QuickQueryRow("select s.dbhost, c.dbusername, c.dbpassword, c.urlcomponent from customer c inner join shard s on (c.shardid = s.id) where c.id = '{$bursttemplatecid}'");
$custdb = DBConnect($custinfo[0], $custinfo[1], $custinfo[2], "c_{$bursttemplatecid}");
if (! $custdb) {
	exit("Connection failed for customer: $custinfo[0], db: c_{$bursttemplatecid}");
}


////////////////////////////////////////////////////////////////////////////////
// Optional Form Items And Validators
////////////////////////////////////////////////////////////////////////////////


////////////////////////////////////////////////////////////////////////////////
// Form Data
////////////////////////////////////////////////////////////////////////////////

// If a burst template ID was specified
if ($bursttemplateid) {
	// Load up the one we want
	$bursttemplate = DBFind("BurstTemplate", "from bursttemplate where id = ? and not deleted", false, array($bursttemplateid), $custdb);
} else {
	// Otherwise we're going to create a new query, so start a blank one
	$bursttemplate = new BurstTemplate();
}

$helpstepnum = 1;
$helpsteps = array("TODO");

$formdata["name"] = array(
	"label" => _L('Name'),
	"value" => $bursttemplate->name,
	"validators" => array(
		array("ValRequired"),
	),
	"control" => array("TextField","size" => 30, "maxlength" => 51),
	"helpstep" => $helpstepnum
);

$formdata["coordx"] = array(
	"label" => _L('Coordinate X'),
	"value" => $bursttemplate->x,
	"validators" => array(
		array("ValRequired"),
	),
	"control" => array("TextField", "size" => 15, "maxlength" => 15),
	"helpstep" => $helpstepnum
);

$formdata["coordy"] = array(
	"label" => _L('Coordinate Y'),
	"value" => $bursttemplate->y,
	"validators" => array(
		array("ValRequired"),
	),
	"control" => array("TextField", "size" => 15, "maxlength" => 15),
	"helpstep" => $helpstepnum
);


$buttons = array(
	submit_button(_L('Save'),"submit","tick"),
	icon_button(_L('Cancel'),"cross",null,"bursttemplates.php?cid={$bursttemplatecid}")
);
$form = new Form("bursttemplateedit", $formdata, false, $buttons);

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
	}
	else if (($errors = $form->validate()) === false) { //checks all of the items in this form
		$postdata = $form->getData(); //gets assoc array of all values {name:value,...}

		// save the global dbcon and replace it with custdb so that the DBMO can get to the customer data
		global $_dbcon;
		$savedbcon = $_dbcon;
		$_dbcon = $custdb;

		Query("BEGIN");
		
		//save data here	
		$bursttemplate->name = $postdata["name"];
		$bursttemplate->x = $postdata["coordx"];
		$bursttemplate->y = $postdata["coordy"];

		if ($bursttemplateid)
			$bursttemplate->update();
		else
			$bursttemplate->create();

		Query("COMMIT");

		// restore the database connection
		$_dbcon = $savedbcon;

		// Session data cleanup
		unset($_SESSION['bursttemplatecid'], $_SESSION['bursttemplateid']);

		if ($ajax)
			$form->sendTo("bursttemplates.php?cid={$bursttemplatecid}&id={$bursttemplate->id}");
		else
			redirect("bursttemplates.php?cid={$bursttemplatecid}");
	}
}

////////////////////////////////////////////////////////////////////////////////
// Display Functions
////////////////////////////////////////////////////////////////////////////////

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$TITLE = ($bursttemplateid) ? _L('Edit PDF Burst Template') : _L('New PDF Burst Template');
$PAGE = 'commsuite:customers';
include_once("nav.inc.php");
startWindow($TITLE);
echo $form->render();
endWindow();
include_once("navbottom.inc.php");
?>
