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
require_once("obj/ValMultiplePhones.val.php");

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

if (isset($_REQUEST["info"])) {
	$limit = 15;//tip has limited size
	
	$result = Query(
		"select SQL_CALC_FOUND_ROWS u.login, u.firstname, u.lastname
		from user u
		left join usersetting us on (us.userid = u.id)
		where us.name='callerid' and us.value=? and u.deleted=0 limit $limit",false,array($_REQUEST["info"]));
	$total = QuickQuery("select FOUND_ROWS()");
	
	if ($total > 0) {
		echo "<b>CallerID set for Users:</b><br /><div style='white-space: nowrap;'>";
		while ($row = DBGetRow($result)) {
			echo "&nbsp;&nbsp;<b>". $row[0] . " - </b> " . $row[1] . " " . $row[2] . "<br />";
		}
		echo "</div>";
		if ($total > $limit) {
			echo _L("* And in %s additional user accounts.<br />", $total-$limit);
		}
		echo "<br />";
	}
	
	$result = Query(
				"select SQL_CALC_FOUND_ROWS u.login, j.name, j.description
				from job j
				left join user u on j.userid = u.id
				left join jobsetting js on (js.jobid = j.id)
				where js.name='callerid' and js.value=? and j.deleted=0 and u.deleted=0 limit $limit",false,array($_REQUEST["info"]));
	$total = QuickQuery("select FOUND_ROWS()");
	if ($total > 0) {
		echo "<b>CallerID set for Jobs:</b><br /><div style='white-space: nowrap;'>";
		while ($row = DBGetRow($result)) {
			echo "&nbsp;&nbsp;<b>" . $row[1] . " - </b> Sent by: " . $row[0] . "<br />";
		}
		echo "</div>";
	}
	if ($total > $limit) {
		echo _L("* And in %s additional jobs.<br />", $total-$limit);
	}
	
	exit();
}

////////////////////////////////////////////////////////////////////////////////
// Form Items And Validators
////////////////////////////////////////////////////////////////////////////////

class MultiImportBox extends FormItem {
	var $clearonsubmit = true;
	var $clearvalue = array();

	function render ($value) {
		$n = $this->form->name."_".$this->name;
		$style = isset($this->args['height']) ? ('style="height: ' . $this->args['height'] . '; overflow: auto;"') : '';

		$str = '<div id='.$n.' class="radiobox" '.$style.'>';
		$hoverdata = array();
		$counter = 1;
		foreach ($this->args['values'] as $checkvalue => $checkname) {
			$id = $n.'-'.$counter;
			$checked = $value == $checkvalue || (is_array($value) && in_array($checkvalue, $value));
			$str .= '<input id="'.$id.'" name="'.$n.'[]" type="checkbox" value="'.escapehtml($checkvalue).'" '.($checked ? 'checked' : '').' /><label id="'.$id.'-label" for="'.$id.'">'.escapehtml($checkname).'</label><br />';
			$hoverdata[$id.'-label'] = $checkvalue;
			$counter++;
		}
		$str .= '</div>';
		$str .= '<script type="text/javascript">
		document.observe("dom:loaded", function() {
				var hover = $H(' . json_encode($hoverdata). ');
				hover.each(function(val) {
					new Tip(val.key, {
						ajax: {
							url: "callerid.php?info=" + val.value
						},
						style: \'protogrey\',
						radius: 4,
						border: 4,
						hideOn: false,
						hideAfter: 0.5,
						stem: \'leftMiddle\',
						hook: { tip: \'leftMiddle\', mouse: true },
						width: \'auto\'
						});
				
					//alert(val.key);
				});
		});
		</script>';

		return $str;
	}
}
////////////////////////////////////////////////////////////////////////////////
// Form Data
////////////////////////////////////////////////////////////////////////////////
$helpstepnum = 1;
$helpsteps = array(_L('Enter one or more Caller ID numbers. Put each number on a new line. Click the "Add" button to add them to the approved Caller ID list.'));

$numbers = QuickQueryList("select callerid,callerid from authorizedcallerid",true);

$formdata["addnumbers"] = array(
	"label" => _L('Manual Add'),
	"value" => '',
	"fieldhelp" => _L('Enter one or more Caller ID numbers. Put each number on a new line. Click the "Add" button to add them to the approved Caller ID list.'),
	"validators" => array(
		array("ValMultiplePhones")
	),
	"control" => array("TextArea", "rows"=> 4),
	"helpstep" => $helpstepnum
);


if (count($numbers)) {
	$usedjobnumbers = QuickQueryList("select value from jobsetting where name='callerid' and value not in (" . repeatWithSeparator("?",",",count($numbers)) .") and value != '' group by value ",false,false,array_keys($numbers));
	$usedusernumbers = QuickQueryList("select value from usersetting where name='callerid' and value not in (" . repeatWithSeparator("?",",",count($numbers)) .") and value != '' group by value ",false,false,array_keys($numbers));
} else {
	$usedjobnumbers = QuickQueryList("select value from jobsetting where name='callerid' and value != ''");
	$usedusernumbers = QuickQueryList("select value from usersetting where name='callerid' and value != ''");
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

$helpsteps[] = array(_L('These Caller ID numbers are currently associated with users or jobs. To add them as approved Caller IDs, check the box next to the numbers you wish to add. Click the \"Add\" button after making your selections.'));
$helpstepnum++;
if (count($importnumberdata) > 0) {
	$formdata["importnumbers"] = array(
		"label" => _L('Import'),
		"fieldhelp" => _L('Caller ID numbers listed here are currently associated with users or jobs. Check the box next to the number to add it to the Caller ID list.'),
		"value" => '',
		"validators" => array(
			array("ValInArray", 'values' => array_keys($importnumberdata))
		),
		"control" => array("MultiImportBox", "height" => "125px", "values" => $importnumberdata),
		"helpstep" => $helpstepnum
	);
} else {
	$formdata["importinfo"] = array(
			"label" => _L('Import'),
			"fieldhelp" => _L('Caller ID numbers listed here are currently associated with users or jobs.'),
			"value" => '',
			"control" => array("FormHtml", "html" => '<div style="border:1px dotted gray;height:125px;"><img src="img/icons/information.png" alt="Information" style="vertical-align:middle"/><span style="line-height:30px;"> ' . _L("No Caller IDs to import") . "</span></div>"),
			"helpstep" => $helpstepnum
	);
}

$buttons = array(submit_button(_L('Add'),"submit","add"));
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
				if ($importnumber != "" && !in_array($importnumber, $numbers))
					$parsednumbers[$importnumber] = true;
			}
		}
		
		$parsednumbers = array_keys($parsednumbers);
		
		if (count($parsednumbers)) {
			$query = "insert into authorizedcallerid (callerid) values " . repeatWithSeparator("(?)",",",count($parsednumbers));
			QuickUpdate($query,false,$parsednumbers);
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
buttons(icon_button("Done", "tick",false,"jobsettings.php","style='margin-bottom:6px'"));
echo '<div style="width:65%;float:left;">';
startWindow(_L('Caller ID Management'));
echo $form->render();
endWindow();

echo '</div><div style="width:300px;float:left;">';
$numbervalues = array();
foreach ($numbers as $number => $value) {
	$numbervalues[] = array($number);
}

startWindow(_L('Approved Caller IDs'));
?>
<div class="scrollTableContainer"><table class="list sortable" id="callerids" style="width:100%">
<?
	if (count($numbervalues))
		showTable($numbervalues, array(0=>"Phone Numbers","actions" => "Actions"),array(0=>"fmt_phone","actions"=>"fmt_actions"));
	else
		echo "<div class='destlabel'><img src='img/largeicons/information.jpg' align='middle'> " . _L("No authorized caller ids.") . "<div>";
?>
</table></div>
<?
endWindow();
echo '</div>';

buttons();
include_once("navbottom.inc.php");