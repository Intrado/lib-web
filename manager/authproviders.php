<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("common.inc.php");
require_once("../inc/form.inc.php");
require_once("../inc/html.inc.php");
require_once("../inc/utils.inc.php");
require_once("../inc/table.inc.php");
require_once("../obj/Validator.obj.php");
require_once("../obj/Form.obj.php");
require_once("../obj/FormItem.obj.php");
////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////

if (!$MANAGERUSER->authorized("editcustomer"))
	exit("Not Authorized");


////////////////////////////////////////////////////////////////////////////////
// Action/Request Processing
////////////////////////////////////////////////////////////////////////////////

if (!isset($_GET['cid'])) {
	exit("Missing customer id");
} else {
	$cid = $_GET['cid'] + 0;
	$custurl = QuickQuery("select c.urlcomponent from customer c where c.id = ?", false, array($cid));
}

if (isset($_GET['delete'])) {
	QuickQuery("delete from authenticationprovider where customerid = ? and type = ? and endpoint = ?",
			false, array($cid, $_GET['type'], $_GET['domain']));
	redirect("?cid=$cid");
}

////////////////////////////////////////////////////////////////////////////////
// Formatters
////////////////////////////////////////////////////////////////////////////////

function fmt_actions($row,$index) {
	global $cid;
	return action_links(
		array(action_link("Delete", "application_delete","authproviders.php?cid=$row[0]&type=$row[1]&domain=$row[2]&delete","return confirmDelete();")));
}

////////////////////////////////////////////////////////////////////////////////
// Form Data
////////////////////////////////////////////////////////////////////////////////

$titles = array(
		"1" => "Type",
		"2" => "FQDN",
		"actions" => "Actions");

$formatters = array(
		"actions" => "fmt_actions");

$data = QuickQueryMultiRow("select customerid, type, endpoint
		from authenticationprovider where customerid = ?", false, false, array($cid));

$formdata = array(
	"type" => array(
		"label" => _L('Type'),
		"value" => "powerschool",
		"validators" => array(
			array("ValRequired"),
			array("ValInArray", "values" => array("powerschool"))
		),
		"control" => array("SelectMenu","values" => array("powerschool" => "PowerSchool")),
		"helpstep" => 1
	),
	"domain" => array(
		"label" => _L('FQDN'),
		"value" => "",
		"validators" => array(
			array("ValRequired"),
			array("ValLength", "min" => 5, "max" => 255)
		),
		"control" => array("TextField"),
		"helpstep" => 2
	)
);

$buttons = array(submit_button(_L('Save'),"submit","tick"),
				icon_button(_L('Cancel'),"cross",null,"advancedcustomeractions.php"));
$form = new Form("templateform",$formdata,array(),$buttons);


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

		QuickQuery("insert into authenticationprovider values (?,?,?)", false, array($cid, $postdata["type"], $postdata["domain"]));
		
		Query("COMMIT");
		if ($ajax)
			$form->sendTo("authproviders.php?cid=$cid");
		else
			redirect("authproviders.php?cid=$cid");
	}
}

////////////////////////////////////////////////////////////////////////////////
// Display Functions
////////////////////////////////////////////////////////////////////////////////

function fmt_template ($obj, $field) {
	return $obj->$field;
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$TITLE = _L('Authentication Providers');
$PAGE = "commsuite:customers";

include_once("nav.inc.php");

startWindow(_L('Authentication Providers for Customer: %s', $custurl));
?><table class='list sortable' id='providers_preview'><?
showTable($data, $titles, $formatters);
?></table><?
endWindow();

startWindow(_L('New Authentication Provider'));
echo $form->render();
endWindow();

include_once("navbottom.inc.php");
?>
