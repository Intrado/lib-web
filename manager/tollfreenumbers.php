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
require_once("../inc/formatters.inc.php");

////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////

if (!$MANAGERUSER->authorized("tollfreenumbers"))
	exit("Not Authorized");

////////////////////////////////////////////////////////////////////////////////
// Action/Request Processing
////////////////////////////////////////////////////////////////////////////////


////////////////////////////////////////////////////////////////////////////////
// Form Items And Validators
////////////////////////////////////////////////////////////////////////////////
class ValMultiplePhones extends Validator {
	var $onlyserverside = true;
	function validate ($value, $args) {
		$numbers = explode(",",$value);
		if (!is_array($numbers)) {
			return "invalid format. Please insert a comma seperated list of phone numbers";
		} 
		
		$parsednumbers = array();
		foreach ($numbers as $number) {
			if ($err = Phone::validate($number)) {
				$errmsg = "$this->label contains the invalid phone number: $number. ";
				foreach ($err as $e) {
					$errmsg .= $e . " ";
				}
				return $errmsg;
			} else {
				$parsednumbers[] = Phone::parse($number);
			}
		}
		$existingnumbers = QuickQueryList("select phone from tollfreenumbers where phone in (" . DBParamListString(count($parsednumbers)) . ")",false,false,$parsednumbers);
		if (count($existingnumbers)) {
			return "The number(s): " . implode(",",$existingnumbers) . " already exist";
		}
		return true;
	}
}

////////////////////////////////////////////////////////////////////////////////
// Form Data
////////////////////////////////////////////////////////////////////////////////
$helpstepnum = 1;
$helpsteps = array(_L('Multiple numbers can be added when seperated by comma'));

$formdata["numbers"] = array(
	"label" => _L('Phone Numbers'),
	"value" => '',
	"fieldhelp" => _L('Multiple numbers can be added when seperated by comma'),
	"validators" => array(
		array("ValRequired"),
		array("ValMultiplePhones")
	),
	"control" => array("TextArea", "rows" => 4, "cols" => 100),
	"helpstep" => $helpstepnum
);

$buttons = array(submit_button(_L('Add'),"submit","add"));
$form = new Form("tollfreenumbers",$formdata,$helpsteps,$buttons);

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
 		$numbers = explode(",",$postdata["numbers"]);
 		
 		$parsednumbers = array();
 		foreach ($numbers as $number) {
 			$parsednumbers[] = Phone::parse($number);
 		}
 		
		QuickUpdate("insert into tollfreenumbers (phone) values " . repeatWithSeparator("(?)",",",count($parsednumbers)),false,$parsednumbers);	

		Query("COMMIT");
		if ($ajax)
			$form->sendTo("tollfreenumbers.php");
		else
			redirect("tollfreenumbers.php");
	}
}

////////////////////////////////////////////////////////////////////////////////
// Display Functions
////////////////////////////////////////////////////////////////////////////////


////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$TITLE = _L('Toll Free Numbers');

include_once("nav.inc.php");
?>
<script type="text/javascript">
<? Validator::load_validators(array("ValMultiplePhones")); ?>
</script>
<?
buttons(icon_button("Done", "tick",false,"advancedactions.php","style='margin-bottom:6px'"));
startWindow(_L('Add Toll Free Numbers'));
echo $form->render();
endWindow();


$numbers = QuickQueryMultiRow("select phone from tollfreenumbers");
startWindow(_L('Unassigned Toll Free Numbers'));
?>
<table class="list sortable" id="customer_dm_table">
<?
	showTable($numbers, array("Phone Numbers"),array("fmt_phone"));
?>
</table>
<?
endWindow();
buttons();
include_once("navbottom.inc.php");
?>