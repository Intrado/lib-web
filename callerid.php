<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("inc/common.inc.php");
require_once("inc/securityhelper.inc.php");
require_once("inc/table.inc.php");
require_once("inc/html.inc.php");
require_once("inc/utils.inc.php");
require_once("obj/Validator.obj.php");
require_once("obj/Form.obj.php");
require_once("obj/FormItem.obj.php");
require_once("obj/Phone.obj.php");
require_once("inc/formatters.inc.php");
////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('managesystem')) {
	redirect('unauthorized.php');
}

//requireapprovedcallerid

////////////////////////////////////////////////////////////////////////////////
// Action/Request Processing
////////////////////////////////////////////////////////////////////////////////

if (isset($_REQUEST["delete"])) {
	$query = "delete from authorizedcallerid where callerid=?";
	QuickUpdate($query,false,array($_REQUEST["delete"]));
	redirect();
}

////////////////////////////////////////////////////////////////////////////////
// Form Items And Validators
////////////////////////////////////////////////////////////////////////////////

class ValMultiplePhones extends Validator {
	var $onlyserverside = true;
	function validate ($value, $args) {
		$numbers = explode("\n",$value);
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
		return true;
	}
}

////////////////////////////////////////////////////////////////////////////////
// Form Data
////////////////////////////////////////////////////////////////////////////////
$helpstepnum = 1;
$helpsteps = array(_L('Multiple numbers can be added when seperated by comma'));

$numbers = QuickQueryList("select callerid,callerid from authorizedcallerid",true);

$formdata[] = "Caller ID Settings";
$helpsteps[$helpstepnum++] = _L("This specifies the default Caller ID to use for new Jobs. If a user has access rights, they may override this with a new setting.");

$formdata["defaultcallerid"] = array(
	"label" => _L("Default Caller ID Number"),
	"fieldhelp" => _L("This is the default Caller ID for all jobs."),
	"value" => Phone::format(getSystemSetting('callerid')),
	"validators" => array(
		array("ValRequired"),
		array("ValLength","min" => 2,"max" => 20),
		array("ValPhone")),
	"control" => array("TextField","maxlength" => 20),
	"helpstep" => $helpstepnum
);

$formdata["requireapprovedcallerid"] = array(
	"label" => _L('Only Allow Approved Caller ID'),
	"fieldhelp" => _L('Allows users to select Caller ID from a list of approved numbers'),
	"value" => getSystemSetting("requireapprovedcallerid",false),
	"validators" => array(),
	"control" => array("CheckBox"),
	"helpstep" => $helpstepnum
);

$formdata[] = "Caller ID Tools";
$formdata["addnumbers"] = array(
	"label" => _L('Manual Add'),
	"value" => '',
	"fieldhelp" => _L('Multiple numbers can be added when seperated by comma'),
	"validators" => array(
		array("ValMultiplePhones")
	),
	"control" => array("TextArea", "rows"=> 4),
	"helpstep" => $helpstepnum
);


if (count($numbers)) {
	$usedjobnumbers = QuickQueryList("select value from jobsetting where name='callerid' and value not in (" . repeatWithSeparator("?",",",count($numbers)) .") group by value ",false,false,array_keys($numbers));
	$usedusernumbers = QuickQueryList("select value from usersetting where name='callerid' and value not in (" . repeatWithSeparator("?",",",count($numbers)) .") group by value ",false,false,array_keys($numbers));
} else {
	$usedjobnumbers = QuickQueryList("select value from jobsetting where name='callerid'");
	$usedusernumbers = QuickQueryList("select value from usersetting where name='callerid'");
}
$usednumbers = array_unique(array_merge($usedjobnumbers,$usedusernumbers));

$importnumberdata = array();
foreach ($usednumbers as $usednumber) {
	$desc = array();
	if (in_array($usednumber, $usedusernumbers))
		$desc[] = _L("user(s)");
	if (in_array($usednumber, $usedjobnumbers))
		$desc[] = _L("job(s)");
	$importnumberdata[$usednumber] = Phone::format($usednumber) . " - " . _L("Used by ") . implode(_L(" and "),$desc);
}


if (count($importnumberdata) > 0) {
	$formdata["importnumbers"] = array(
		"label" => _L('Import'),
		"value" => '',
		"validators" => array(
			array("ValInArray", 'values' => array_keys($importnumberdata))
		),
		"control" => array("MultiCheckBox", "height" => "50px", "values" => $importnumberdata),
		"helpstep" => $helpstepnum
	);
} else {
	$formdata["importinfo"] = array(
			"label" => _L('Import'),
			"value" => '',
			"control" => array("FormHtml", "html" => '<img src="img/icons/information.png" alt="Information" style="vertical-align:middle"/><span style="line-height:30px;"> ' . _L("No Caller IDs to import") . "</span>"),
			"helpstep" => $helpstepnum
	);
	
}

$buttons = array(submit_button(_L('Save'),"submit","tick"));
$form = new Form("calleridmanagement",$formdata,$helpsteps,$buttons);

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
		
		
		// Maintain a set of numbers to avoid error on duplicate caller ids from manual add and imported callerids
		$newnumbers = explode("\n",$postdata["addnumbers"]);
		$parsednumbers = array();
		foreach ($newnumbers as $number) {
			$parsednumber = Phone::parse($number);
			if ($parsednumber != "" && !in_array($parsednumber, $numbers))
				$parsednumbers[$parsednumber] = true;
		}
		
		if (isset($postdata["importnumbers"])) {
			foreach ($postdata["importnumbers"] as $importnumber) {
				$parsednumbers[$importnumber] = true;
			}
		}
		
		$parsednumbers = array_keys($parsednumbers);
		
		if (count($parsednumbers)) {
			$query = "insert into authorizedcallerid (callerid) values " . repeatWithSeparator("(?)",",",count($parsednumbers));
			QuickUpdate($query,false,$parsednumbers);
		}
		
		setSystemSetting('callerid', Phone::parse($postdata['defaultcallerid']));
		
		setSystemSetting('requireapprovedcallerid', $postdata['requireapprovedcallerid']==="true"?1:0);
		
		
		if ($postdata['requireapprovedcallerid']==="true") {
			QuickUpdate("insert ignore into authorizedusercallerid (userid,callerid) (select userid,value from usersetting where name='callerid')");
		}
		
		Query("COMMIT");
		if ($ajax)
			$form->sendTo("callerid.php");
		else
			redirect("callerid.php");
	}
}

////////////////////////////////////////////////////////////////////////////////
// Display Functions
////////////////////////////////////////////////////////////////////////////////

function fmt_addcheck($row, $index){
	return '<input id="' . $row[0] . '" class="addcheckbox" type="checkbox" value="' . $row[0] . '"/>';
}

function fmt_actions($row, $index) {
	$actionlinks = array();
	$actionlinks[] = action_link("Delete", "delete","{$_SERVER["SCRIPT_NAME"]}?delete=$row[0]","return confirmDelete();");
	return action_links($actionlinks);
}
////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$PAGE = _L("admin").":"._L("settings");
$TITLE = _L('Caller ID Management');

include_once("nav.inc.php");
?>
<script type="text/javascript">
<? Validator::load_validators(array("ValMultiplePhones")); ?>
</script>
<?
//buttons(icon_button("Done", "tick",false,"settings.php","style='margin-bottom:6px'"));
startWindow(_L('Caller ID Management'));
echo $form->render();
endWindow();


$numbervalues = array();
foreach ($numbers as $number => $value) {
	$numbervalues[] = array($number);
}

startWindow(_L('Approved Caller IDs'));
?>
<table class="list sortable" id="callerids">
<?
	if (count($numbervalues))
		showTable($numbervalues, array(0=>"Phone Numbers","actions" => "Actions"),array(0=>"fmt_phone","actions"=>"fmt_actions"));
	else
		echo "<div class='destlabel'><img src='img/largeicons/information.jpg' align='middle'> " . _L("No authorized caller ids.") . "<div>";
?>
</table>
<?
endWindow();
//buttons();
include_once("navbottom.inc.php");