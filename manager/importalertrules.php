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
require_once("../obj/WeekRepeat.fi.php");
require_once("../obj/WeekRepeat.val.php");
require_once("../obj/ImportAlertRule.obj.php");
////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////

if (!$MANAGERUSER->authorized("imports"))
	exit("Not Authorized");


if (isset($_GET['cid']) && isset($_GET['importid']) && isset($_GET['categoryid'])) {
	$_SESSION['importalertrules'] = json_encode(
		array("cid" => $_GET['cid']+0,
			"importid" => $_GET['importid']+0,
			"categoryid" => $_GET['categoryid']+0));
	redirect();	
}

if (!$_SESSION['importalertrules'])
	exit("Not Authorized");


$ruleinfo = json_decode($_SESSION['importalertrules'],true);
if (!isset($ruleinfo["cid"]) || !isset($ruleinfo["categoryid"]) || !isset($ruleinfo["importid"])) 
	exit("Not Authorized");

$customerid = $ruleinfo["cid"];
$categoryid = $ruleinfo["categoryid"];
$importid =  $ruleinfo["importid"];


////////////////////////////////////////////////////////////////////////////////
// Action/Request Processing
////////////////////////////////////////////////////////////////////////////////


////////////////////////////////////////////////////////////////////////////////
// Optional Form Items And Validators
////////////////////////////////////////////////////////////////////////////////

class TimeWindow extends FormItem {
	function render ($value) {
		
		$n = $this->form->name."_".$this->name;
		$str = "<input id='$n' name='$n' type='hidden' value='' />
				<select id='importtime' onchange='updatetimewindow();'>
				<option value=0> -- Disabled -- </option>";
		
		$timevalues = newform_time_select(NULL,NULL,NULL);
		foreach ($timevalues as $key => $time) {
			$str .= "<option value='$key' " . (isset($value["importtime"]) && $value["importtime"] == $time?"selected":"") . ">$time</option>";
		}
		$str .= "</select>";
		$str .= "&nbsp;&nbsp;&nbsp;
				<select id='timewindow' onchange='updatetimewindow()'>
						<option value=0> -- Select Time Window -- </option>";
		
		$timevalues = newform_time_select(NULL,NULL,NULL);
		for($i=10;$i<=130;$i+=15) {
			$str .= "<option value='$i' " . (isset($value["timewindow"]) && $value["timewindow"] == $i?"selected":"") . ">$i</option>";
		}
		$str .= '</select>';
		
		return $str;
	}
	function renderJavascript() {
		$n = $this->form->name."_".$this->name;
		$str = "
			function updatetimewindow() {
				if ($('importtime').value != 0) {
					$('$n').value = '{\"importtime\":\"' + $('importtime').value + '\",\"timewindow\":\"' + $('timewindow').value + '\"}';
					$('timewindow').disabled=false;
					
				} else {
					$('$n').value = '';
					$('timewindow').disabled=true;
					$('timewindow').value = 0;
				}
			}
			document.observe('dom:loaded', function() {
				updatetimewindow();
			});
		";
		return $str;
	}
}

class ValWeekDays extends Validator {
	var $onlyserverside = true;
	function validate ($value, $args) {
		if(!is_array($value)) {
			$value = json_decode($value);
		}
		for($i = 0;$i < 7;$i++){
			if(!is_bool($value[$i]))
			return _L('Invalid Input');
		}
		return true;
	}
}

////////////////////////////////////////////////////////////////////////////////
// Form Data
////////////////////////////////////////////////////////////////////////////////



//$importrules = DBFindMany("ImportAlertRule", "from importalertrule");
$query = "select s.dbhost, c.dbusername, c.dbpassword from customer c inner join shard s on (c.shardid = s.id) where c.id=?";
$custinfo = QuickQueryRow($query,true,false,array($customerid));
$custdb = DBConnect($custinfo["dbhost"], $custinfo["dbusername"], $custinfo["dbpassword"], "c_$customerid");
if (!$custdb) {
	exit("Connection failed for customer: {$custinfo["dbhost"]}, db: c_$customerid");
}


// Get Alert Rules, use only category 1;
$dows = array();
$daysold =  DBFind("ImportAlertRule", "from importalertrule where importid=? and categoryid=? and name='daysold' and operation='gt'",false,array($importid,$categoryid),$custdb);
if (!$daysold)
	$daysold = new ImportAlertRule();

$minsize =  DBFind("ImportAlertRule", "from importalertrule where importid=? and categoryid=? and name = 'size' and operation='lt'",false,array($importid,$categoryid),$custdb);
if (!$minsize)
	$minsize = new ImportAlertRule();

$maxsize =  DBFind("ImportAlertRule", "from importalertrule where importid=? and categoryid=? and name = 'size' and operation='gt'",false,array($importid,$categoryid),$custdb);
if (!$maxsize)
	$maxsize = new ImportAlertRule();

$mintimewindow =  DBFind("ImportAlertRule", "from importalertrule where importid=? and categoryid=? and name = 'importtime' and operation='lt'",false,array($importid,$categoryid),$custdb);
if (!$mintimewindow)
	$mintimewindow = new ImportAlertRule();

$maxtimewindow =  DBFind("ImportAlertRule", "from importalertrule where importid=? and categoryid=? and name = 'importtime' and operation='gt'",false,array($importid,$categoryid),$custdb);
if (!$maxtimewindow)
	$maxtimewindow = new ImportAlertRule();



$helpstepnum = 1;
$helpsteps = array("TODO");
$formdata = array();


$sizeoptions = "";
for($i=10;$i<=100;$i+=10) {
	$sizeoptions .= "<option value='$i'>$i%</option>";
}
$filesize = QuickQuery("select datalength from import where id=?",$custdb,array($importid)); 
$formdata["calcsize"] = array(
	"label" => "",
	"control" => array("FormHtml","html"=> "<div style='margin-top:20px'>Current File size: " . escapehtml($filesize) .
		'(bytes) &plusmn;
		<select name="sizerange" id="sizerange">' . $sizeoptions . '</select>
		<a href="#" onclick="var filesize=' . $filesize . ';
		 $(\'alertrule_minsize\').value=Math.floor(filesize*(1 - $(\'sizerange\').value/100));
		 form_do_validation($(\'alertrule\'), $(\'alertrule_minsize\'));
		 $(\'alertrule_maxsize\').value=Math.floor(filesize*(1 + $(\'sizerange\').value/100));
		 form_do_validation($(\'alertrule\'), $(\'alertrule_maxsize\'));
		  
		  return false;">Calculate Size</a></div>'),	
	"helpstep" => $helpstepnum
);

$formdata["minsize"] = array(
	"label" => _L('Minimum File Size'),
	"value" => $minsize->testvalue,
	"validators" => array(
		array("ValNumber")
	),
	"control" => array("TextField","size" => 30, "maxlength" => 51),
	"helpstep" => $helpstepnum
);
$formdata["maxsize"] = array(
	"label" => _L('Maximum File Size'),
	"value" => $maxsize->testvalue,
	"validators" => array(
		array("ValNumber")
	),
	"control" => array("TextField","size" => 30, "maxlength" => 51),
	"helpstep" => $helpstepnum
);
$formdata["daysold"] = array(
	"label" => _L('Number of Days Old'),
	"value" => $daysold->testvalue,
	"validators" => array(
		array("ValNumber")
	),
	"control" => array("TextField","size" => 3, "maxlength" => 3),
	"helpstep" => $helpstepnum
);

$formdata[] = "Scheduled Time Window";
// New backend implementation store the days of week to run in the rule
// the frontend still has one field for all rules
$dows = array_merge(
	explode(',',$mintimewindow->daysofweek),
	explode(',',$maxtimewindow->daysofweek)
);

$days = array();
for ($x = 0; $x < 7; $x++) {

	$days[] = in_array($x+1,$dows);
}
$timevalues = newform_time_select(NULL,NULL,NULL);
$formdata["daysofweek"] = array(
	"label" => _L("Days to Run"),
	"value" => $days,
	"validators" => array(
array("ValRequired"),
array("ValWeekDays")
),
	"control" => array(
		"WeekRepeatItem",
		"timevalues" => $timevalues
),
	"helpstep" => ++$helpstepnum
);

$midnight_today = mktime(0,0,0);
$timewindowvalue = array();
if ($mintimewindow->id && $maxtimewindow->id) {
	$timewindow = ($maxtimewindow->testvalue - $mintimewindow->testvalue) / 2;
	$importtime = $midnight_today + $mintimewindow->testvalue + $timewindow;
	$timewindowvalue = array("importtime" => date("g:i a",$importtime), "timewindow" => $timewindow/60);
}


//$mintimewindow
//$maxtimewindow
$formdata["timewindow"] = array(
	"label" => _L('Time Window'),
	"value" => $timewindowvalue,
	"validators" => array(),
	"control" => array("TimeWindow"),
	"helpstep" => $helpstepnum
);

$formdata[] = "Alert Notification";
$formdata["emailinfo"] = array(
		"label" => " ",
		"control" => array("FormHtml","html" => '<img src="img/icons/information.png" alt="" />Emails are shared between all alerts in this category'),
		"helpstep" => $helpstepnum
);
$emails = QuickQuery("select emails from importalertcategory where id=?",$custdb,array($categoryid));
$formdata["emails"] = array(
		"label" => _L("Emails"),
		"value" => $emails,
		"validators" => array(
			array("ValEmailList")
		),
		"control" => array("TextArea","cols" => 30, "rows" => 3),
		"helpstep" => $helpstepnum
);

$buttons = array(submit_button(_L('Save'),"submit","tick"),
				icon_button(_L('Cancel'),"cross",null,"customerimports.php"));
$form = new Form("alertrule",$formdata,false,$buttons);

////////////////////////////////////////////////////////////////////////////////
// Form Data Handling
////////////////////////////////////////////////////////////////////////////////

function setRule($rule,$name,$operation,$value,$category,$daysofweek) {
	global $importid;
	$rule->importid = $importid;
	$rule->categoryid = $category;
	$rule->name = $name;
	$rule->operation = $operation;
	$rule->testvalue = $value;
	$rule->daysofweek = $daysofweek;
	
	if ($rule->id)
		$rule->update();
	else
		$rule->create();
}


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
		
		$repeatdata = json_decode($postdata["daysofweek"],true);
		$dow = array();
		for ($x = 0; $x < 7; $x++) {
			if ($repeatdata[$x] === true) {
				$dow[$x] = $x+1;
			}
		}
		$daysofweek = implode(",",$dow);
		
		$shardinfo = QuickQueryRow("select s.dbhost, s.dbusername, s.dbpassword from shard s inner join customer c on (c.shardid = s.id) where c.id = ?", true,false,array($customerid));
		$sharddb = DBConnect($shardinfo["dbhost"], $shardinfo["dbusername"], $shardinfo["dbpassword"], "aspshard");
		if(!$sharddb) {
			exit("Connection failed for customer: $customerid, shardhost: {$shardinfo["dbhost"]}");
		}
		
		global $_dbcon;
		$savedbcon = $_dbcon;
		$_dbcon = $custdb;
		
		
		
		Query("BEGIN");
		if ($postdata["minsize"]) {
			setRule($minsize,"size","lt",$postdata["minsize"],$categoryid,"1,2,3,4,5,6,7");
		} else {
			QuickUpdate("delete from importalert where customerid=? and importalertruleid=?", $sharddb, array($customerid,$minsize->id));
			$minsize->destroy();
		}
		
		if ($postdata["maxsize"]) {
			setRule($maxsize,"size","gt",$postdata["maxsize"],$categoryid,"1,2,3,4,5,6,7");
		} else {
			QuickUpdate("delete from importalert where customerid=? and importalertruleid=?", $sharddb, array($customerid,$maxsize->id));
			$maxsize->destroy();
		}
		
		if ($postdata["daysold"]) {
			setRule($daysold,"daysold","gt",$postdata["daysold"],$categoryid,"1,2,3,4,5,6,7");
		} else {
			QuickUpdate("delete from importalert where customerid=? and importalertruleid=?", $sharddb, array($customerid,$daysold->id));
			$daysold->destroy();
		}
		
		if ($postdata["timewindow"]) {
			$timewindow = json_decode($postdata["timewindow"],true);
			if (isset($timewindow["importtime"]) && isset($timewindow["timewindow"])) {
				$importtime = strtotime($timewindow["importtime"]);
				$mintime = $importtime - $midnight_today - $timewindow["timewindow"] * 60;
				$maxtime = $importtime - $midnight_today + $timewindow["timewindow"] * 60;
				setRule($mintimewindow,"importtime","lt",$mintime,$categoryid,$daysofweek);
				setRule($maxtimewindow,"importtime","gt",$maxtime,$categoryid,$daysofweek);
			}
		} else {
			QuickUpdate("delete from importalert where customerid=? and importalertruleid=? or importalertruleid=?", $sharddb, array($customerid,$mintimewindow->id,$maxtimewindow->id));
			$mintimewindow->destroy();
			$maxtimewindow->destroy();
		}
		
		QuickUpdate("update importalertcategory set emails=? where id=?",false,array($postdata["emails"],$categoryid));
		
		Query("COMMIT",$custdb);
		// restore global db connection
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

function fmt_template ($obj, $field) {
	return $obj->$field;
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$TITLE = _L('Import Alert Rule');

include_once("nav.inc.php");

?>
<script type="text/javascript">
<? Validator::load_validators(array("ValWeekDays")); ?>
</script>
<?

$categorytype = QuickQuery("select type from importalertcategory where id=?",$custdb,array($categoryid));

startWindow(_L('%s Import Alert Rules', ucfirst($categorytype)));
echo $form->render();
endWindow();
include_once("navbottom.inc.php");
?>