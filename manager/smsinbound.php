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
require_once("../obj/Phone.obj.php");
require_once("XML/RPC.php");
require_once("authclient.inc.php");

////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$MANAGERUSER->authorized("smsblock"))
	exit("Not Authorized");


////////////////////////////////////////////////////////////////////////////////
// Action/Request Processing
////////////////////////////////////////////////////////////////////////////////


////////////////////////////////////////////////////////////////////////////////
// Optional Form Items And Validators
////////////////////////////////////////////////////////////////////////////////

// Example of a custom form FormItem
class TemplateItem extends FormItem {
	function render ($value) {
		$n = $this->form->name."_".$this->name;
		if($value == null || $value == "") // Handle empty value to combind this validator with ValRequired
			$value = array("left" => "false","right" => "false");
		// edit input type from "hidden" to "text" to debug the form value
		$str = '<input id="'.$n.'" name="'.$n.'" type="hidden" value="'.escapehtml(json_encode($value)).'"/>';
		$str .= '<input id="'.$n.'left" name="'.$n.'left" type="checkbox" onchange="setValue_'.$n.'()" value="" '. ($value["left"] == "true" ? 'checked' : '').' />';
		$str .= '<input id="'.$n.'right" name="'.$n.'right" type="checkbox" onchange="setValue_'.$n.'()" value="" '. ($value["right"] == "true" ? 'checked' : '').' />';
		$str .= '<script>function setValue_'.$n.'(){
								$("'.$n.'").value = Object.toJSON({
									"left": $("'.$n.'left").checked.toString(),
									"right": $("'.$n.'right").checked.toString()
							});
							form_do_validation($("' . $this->form->name . '"), $("' . $n . '"));
						 }
				</script>';		
		return $str;
	}
}

// Example of a custom form Validator
class ValTemplateItem extends Validator {
	function validate ($value, $args) {
		if(!is_array($value)) {
			$value = json_decode($value,true);
		}
		if (!($value["left"] == "true" || $value["right"] == "true"))	
			return "One item is required for " . $this->label;
		else
			return true;

	}
	function getJSValidator () {
		return 
			'function (name, label, value, args) {			
				checkval = value.evalJSON();
				if (!(checkval.left == "true" || checkval.right == "true"))
					return "One item is required for " + label;
				return true;
			}';
	}
}

////////////////////////////////////////////////////////////////////////////////
// Form Data
////////////////////////////////////////////////////////////////////////////////

$data = array(
	"datereceived" => date("Y-m-d")
);
if (isset($_SESSION["smsinbounddata"])) {
	$data = array_merge($data, json_decode($_SESSION["smsinbounddata"], true));
}

$formdata = array(
	"datereceived" => array(
		"label" => _L('Date YYYY-MM-DD'),
		"value" => $data["datereceived"],
		"validators" => array(
			array("ValLength","min" => 10,"max" => 10)
		),
		"control" => array("TextField","size" => 10, "maxlength" => 10),
		"helpstep" => 1
	)
);

$helpsteps = array (
	_L('Enter the exact date, in the format YYYY-MM-DD. The results window displays all inbound SMS messages received on that date.<br><br>
		In the results table, click on the SMS Number to be redirected to the opt-in and block page.')
);

$buttons = array(submit_button(_L('Refresh'), "submit", "arrow_refresh"));
$form = new Form("smsinboundform", $formdata, $helpsteps, $buttons);

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
		$_SESSION['smsinbounddata'] = json_encode($postdata);
		
		// no data to save, just populate results table
		//Query("BEGIN");
		//save data here	
		//Query("COMMIT");
		if ($ajax)
			$form->sendTo("smsinbound.php");
		else
			redirect("smsinbound.php");
	}
}


$titles = array(
	"0" => _L('Date Received'),
	"1" => _L('Shortcode'),
	"2" => _L('SMS Number'),
	"3" => _L('Message ID'),
	"4" => _L('Message Orig'),
	"5" => _L('Message'),
	"6" => _L('Carrier'),
	"7" => _L('Action Taken'),
	"8" => _L('Current Status')
);

$formatters = array (
	"2" => "fmt_smsnumber",
	"8" => "fmt_status"
);

$results = QuickQueryMultiRow("select datereceived, shortcode, smsnumber, message_id, message_orig, message, carrier, action from smsinbound where datereceived like ?", false, null, array($data["datereceived"]."%"));

if (count($results) > 0) {
	$numbers = "";
	foreach ($results as $row) {
		$numbers .= "'" . $row[2] . "',";
	}
	$numbers = substr($numbers, 0, strlen($numbers)-1); // chop trailing comma
	$query = "select sms, status from smsblock where sms in (".$numbers.")";
	
	//connect to aspshard to query smsblock list for current status
	$shard = QuickQueryRow("select id, dbhost, dbusername, dbpassword from shard limit 1");

	$sharddb = DBConnect($shard[1], $shard[2], $shard[3], "aspshard")
		or die("Could not connect to shard database");

	$smsstatus = QuickQueryList($query, true, $sharddb);
} else {
	$smsstatus = array();
}

////////////////////////////////////////////////////////////////////////////////
// Display Functions
////////////////////////////////////////////////////////////////////////////////

function fmt_smsnumber($row, $index) {
	return "<a href=\"smsblock.php?sms=" . $row[2] ."\">" . $row[2] . "</a>";
}

function fmt_status($row, $index) {
	global $smsstatus;
	
	if (isset($smsstatus[$row[2]]))
		$status = $smsstatus[$row[2]];
	else
		$status = "No Record";
	
	if ($status == 'pendingoptin') {
		return "Pending Opt-In";
	} else {
		return ucfirst($status);
	}
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$PAGE = "manager:smsinbound";
$TITLE = _L('Inbound SMS');

include_once("nav.inc.php");

startWindow(_L('Inbound SMS'));
echo $form->render();
endWindow();

startWindow(_L('Results'));
if (count($results) > 0) {
	echo "<table class=list>";
	showTable($results, $titles, $formatters);
	echo "</table>";
} else {
	echo _L('No inbound messages for this date.');
}
endWindow();
include_once("navbottom.inc.php");
?>
