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
require_once("../obj/Import.obj.php");


////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////

if (!$MANAGERUSER->authorized("imports"))
	exit("Not Authorized");

////////////////////////////////////////////////////////////////////////////////
// Action/Request Processing
////////////////////////////////////////////////////////////////////////////////'

if (isset($_GET['cid']) && isset($_GET['importid']) ) {
	$_SESSION['editimport'] = json_encode(
	array("cid" => $_GET['cid']+0,
				"importid" => $_GET['importid']+0));

	redirect();
}

if (!isset($_SESSION['editimport'])) {
	notice("Unable to find import");
	redirect("customerimports.php");
}

$importinfo = json_decode($_SESSION['editimport'],true);
$customerid = $importinfo['cid'];
$importid = $importinfo['importid'];


////////////////////////////////////////////////////////////////////////////////
// Optional Form Items And Validators
////////////////////////////////////////////////////////////////////////////////


////////////////////////////////////////////////////////////////////////////////
// Form Data
////////////////////////////////////////////////////////////////////////////////

$query = "select s.dbhost, c.dbusername, c.dbpassword from customer c inner join shard s on (c.shardid = s.id) where c.id=?";
$custinfo = QuickQueryRow($query,true,false,array($customerid));
$custdb = DBConnect($custinfo["dbhost"], $custinfo["dbusername"], $custinfo["dbpassword"], "c_$customerid");
if (!$custdb) {
	exit("Connection failed for customer: {$custinfo["dbhost"]}, db: c_$customerid");
}

$import = DBFind("Import", "from import where id=?",false,array($importid),$custdb);
if (!$import) {
	notice("Unable to find import");
	redirect("customerimports.php");
}

$helpstepnum = 1;
$helpsteps = array("TODO");

$formdata["nsticketid"] = array(
	"label" => _L('NS Ticket ID'),
	"value" => $import->nsticketid,
	"validators" => array(),
	"control" => array("TextField","size" => 30, "maxlength" => 51),
	"helpstep" => $helpstepnum
);

$formdata["managernotes"] = array(
	"label" => _L('Manager Notes'),
	"value" => $import->managernotes,
	"validators" => array(),
	"control" => array("TextArea","rows" => 3, "cols" => 50),
	"helpstep" => $helpstepnum
);

$buttons = array(submit_button(_L('Save'),"submit","tick"),
				icon_button(_L('Cancel'),"cross",null,"customerimports.php"));
$form = new Form("editimport",$formdata,false,$buttons);

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
		
		global $_dbcon;
		$savedbcon = $_dbcon;
		$_dbcon = $custdb;
		
		Query("BEGIN");
		
		//save data here	
		$import->nsticketid = $postdata["nsticketid"];
		$import->managernotes = $postdata["managernotes"];
		$import->update();

		Query("COMMIT");
		
		$_dbcon = $savedbcon;
		
		if ($ajax)
			$form->sendTo("customerimports.php");
		else
			redirect("customerimports.php");
	}
}

////////////////////////////////////////////////////////////////////////////////
// Display Functions
////////////////////////////////////////////////////////////////////////////////

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$TITLE = _L('Edit User');

include_once("nav.inc.php");

startWindow(_L('Edit Import'));
echo $form->render();
endWindow();
include_once("navbottom.inc.php");
?>