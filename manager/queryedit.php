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
require_once("AspAdminQuery.obj.php");

////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////

if (!$MANAGERUSER->authorized("editqueries"))
	exit("Not Authorized");

////////////////////////////////////////////////////////////////////////////////
// Action/Request Processing
////////////////////////////////////////////////////////////////////////////////'

if (isset($_GET['id'])) {
	if ($_GET['id'] === "new") {
		$_SESSION['queryid'] = null;
	} else {
		$_SESSION['queryid']= $_GET['id']+0;
	}
	redirect();
}

$queryid = isset($_SESSION['queryid'])?$_SESSION['queryid']:false;

////////////////////////////////////////////////////////////////////////////////
// Optional Form Items And Validators
////////////////////////////////////////////////////////////////////////////////


////////////////////////////////////////////////////////////////////////////////
// Form Data
////////////////////////////////////////////////////////////////////////////////


if ($queryid) {
	$managerquery = DBFind("AspAdminQuery", "from aspadminquery where id=?",false,array($queryid));
} else {
	$managerquery = new AspAdminQuery();
}

$helpstepnum = 1;
$helpsteps = array("TODO");
if (!$queryid && $MANAGERUSER->queries != "unrestricted") {
	$formdata["notice"] = array(
		"label" => _L("Notice"),
		"control" => array("FormHtml","html"=> "<div style='color:red;'>Your admin acount is restricted. A new query will not show up in the query list when you create it.</div>"),	
		"helpstep" => $helpstepnum
	);
}


$formdata["name"] = array(
	"label" => _L('Name'),
	"value" => $managerquery->name,
	"validators" => array(
		array("ValRequired"),
	),
	"control" => array("TextField","size" => 30, "maxlength" => 51),
	"helpstep" => $helpstepnum
);

$formdata["notes"] = array(
	"label" => _L('Notes'),
	"value" => $managerquery->notes,
	"validators" => array(
		array("ValRequired"),
	),
	"control" => array("TextArea","cols" => 50, "rows" => 5),
	"helpstep" => $helpstepnum
);

$formdata["query"] = array(
	"label" => _L('Query'),
	"value" => $managerquery->query,
	"validators" => array(
		array("ValRequired"),
	),
	"control" => array("TextArea","cols" => 100, "rows" => 5),
	"helpstep" => $helpstepnum
);


$formdata["numargs"] = array(
	"label" => _L('Number of arguments'),
	"value" => $managerquery->numargs,
	"validators" => array(
		array("ValRequired"),
		array("ValNumeric"),
	),
	"control" => array("TextField","size" => 2),
	"helpstep" => $helpstepnum
);

$formdata["singlecustomer"] = array(
	"label" => _L('Single Customer Query'),
	"value" => $managerquery->getOption("singlecustomer"),
	"validators" => array(),
	"control" => array("CheckBox"),
	"helpstep" => $helpstepnum
);

$formdata["usemaster"] = array(
	"label" => _L('Run on Master'),
	"value" => $managerquery->getOption("usemaster"),
	"validators" => array(),
	"control" => array("CheckBox"),
	"helpstep" => $helpstepnum
);


$buttons = array(submit_button(_L('Save'),"submit","tick"),
				icon_button(_L('Cancel'),"cross",null,"querylist.php"));
$form = new Form("queryedit",$formdata,false,$buttons);

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
		
		//save data here	
		$managerquery->name = $postdata["name"];
		$managerquery->notes = $postdata["notes"];
		$managerquery->query = $postdata["query"];
		$managerquery->numargs = $postdata["numargs"];
		
		if ($postdata["singlecustomer"])
			$managerquery->setOption("singlecustomer");
		else
			$managerquery->unsetOption("singlecustomer");
			
		if ($postdata["usemaster"])
			$managerquery->setOption("usemaster");
		else
			$managerquery->unsetOption("usemaster");
		
		if ($queryid)
			$managerquery->update();
		else
			$managerquery->create();
		
		Query("COMMIT");
		if ($ajax)
			$form->sendTo("querylist.php");
		else
			redirect("querylist.php");
	}
}

////////////////////////////////////////////////////////////////////////////////
// Display Functions
////////////////////////////////////////////////////////////////////////////////

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$TITLE = _L('Edit Query');

include_once("nav.inc.php");

startWindow(_L('Edit Query'));
echo $form->render();
endWindow();
include_once("navbottom.inc.php");
?>