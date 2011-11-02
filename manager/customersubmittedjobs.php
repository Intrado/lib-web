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
////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////

if (!$MANAGERUSER->authorized("activejobs"))
	exit("Not Authorized");


////////////////////////////////////////////////////////////////////////////////
// Action/Request Processing
////////////////////////////////////////////////////////////////////////////////

////////////////////////////////////////////////////////////////////////////////
// Form Data
////////////////////////////////////////////////////////////////////////////////
// default settings
$settings = array(
	"jobstatus" => "scheduled"
);
if (isset($_GET["jobstatus"])) {
	$settings["jobstatus"] = $_GET["jobstatus"];
}

$jobstatus = array("scheduled" => "Scheduled","processing" => "Processing");
$formdata["jobstatus"] = array(
	"label" => _L('Job Status'),
	"value" => $settings['jobstatus'],
	"validators" => array(
		array("ValRequired"),
		array("ValInArray", "values" => array_keys($jobstatus))
	),
	"control" => array("SelectMenu", "values" => $jobstatus),
	"helpstep" => 1
);

$buttons = array(submit_button(_L('Refresh'),"submit","arrow_refresh"));
$form = new Form("submittedjobs",$formdata,false,$buttons);

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
		if ($ajax)
			$form->sendTo("customersubmittedjobs.php?" . http_build_query($postdata));
		else
			redirect("customersubmittedjobs.php?" . http_build_query($postdata));
	}
}

$customers = QuickQueryList("select id, urlcomponent from customer",true);

$res = Query("select id, dbhost, dbusername, dbpassword from shard order by id");
$shards = array();
while($row = DBGetRow($res)){
	$dsn = 'mysql:dbname=aspshard;host='.$row[1];
	$db = new PDO($dsn, $row[2], $row[3]);
	$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
	$shards[$row[0]] = $db;
}

$schedjobs = array();
$extrasql = "";

if(isset($_GET['cid'])){
	$customerid = $_GET['cid'] + 0;
	$extrasql .= " and j.customerid = $customerid ";
	if(isset($_GET['user'])){
		$userid = $_GET['user'] + 0;
		$extrasql .= " and j.userid = $userid ";
	}
}

foreach ($shards as $shardid => $sharddb) {
	Query("use aspshard", $sharddb);
	QuickUpdate("set time_zone='GMT'",$sharddb);
	$args=array($settings['jobstatus']);
	if ($settings['jobstatus'] == "processing") {
		$extrasql .= " and status='procactive' or";
	} else {
		$extrasql .= " and";
	}
	$query = "select systempriority, customerid, id, startdate, starttime, timezone,
			convert_tz(addtime(startdate,starttime),timezone,'SYSTEM') systemstarttime
			from qjob j where 1 $extrasql status=? 
			order by systemstarttime , systempriority, customerid, id";				
	$res = Query($query,$sharddb, array($settings['jobstatus']));
	while ($row = DBGetRow($res)) {
		$secondstostart = strtotime($row[6]) - time();
		$seconds = abs($secondstostart);
		$days = floor($seconds/86400);
		$seconds -= $days*86400;
		$hours = floor($seconds/3600);
		$seconds -= $hours*3600;
		$minutes = floor($seconds/60);
		$seconds -= $minutes*60;

		$timetorun = str_pad($hours,2, "0",STR_PAD_LEFT) . ":" . str_pad($minutes,2, "0",STR_PAD_LEFT) . ":" . str_pad($minutes,2, "0",STR_PAD_LEFT) . ($days ? " + $days Days" : "");
		if($secondstostart < 0)
		$timetorun = " - " . $timetorun;

		$schedjobs[$secondstostart][] = array ($row[1], $customers[$row[1]], $row[2], $row[3], $row[4], $row[5], $timetorun);
	}
}




////////////////////////////////////////////////////////////////////////////////
// Display Functions
////////////////////////////////////////////////////////////////////////////////




function fmt_custurl($row, $index){
	$url = "<a href=\"customerlink.php?id=" . $row[0] ."\" target=\"_blank\">" . $row[1] . "</a>";
	return $url;
}


//index 0 is jobid
function fmt_play_jobs($row, $index){
	$url = "";
	if($row[0])
	$url = "<a onclick='popup(\"customerplaymessage.php?customerid=" . $row[0] . "&jobid=" . $row[2] . "\", 400, 500); return false;' href=\"#\" alt='' title='Play Message'><img src='mimg/s-play.png' border=0></a>";
	return $url;
}


////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$TITLE = _L('Customer Jobs');

include_once("nav.inc.php");

// Optional Load Custom Form Validators
?>
<script type="text/javascript">
<? Validator::load_validators(array()); ?>
</script>
<div style='padding:5px;'>
<a href='customeractivejobs.php' >Phone</a>&nbsp;|&nbsp;<a href='customeractivesmsjobs.php' >SMS</a>&nbsp;|&nbsp;<a href='customeractiveemailjobs.php' >Email</a>&nbsp;|&nbsp;
<a href="customersubmittedjobs.php?clear" style="color:black">Scheduled/Processing</a>
</div>
<?
startWindow(_L('Scheduled/Processing Jobs Filter'));
echo $form->render();
endWindow();

startWindow(_L('Jobs'));
$titles = array ("Customer id",
				"Customer url",
				"Job id",
				"Start Date",
				"Start Time",
				"Timezone",
				"Time until run",
				"Play Message"
			);
ksort($schedjobs);
$scheddata = array();
foreach ($schedjobs as $schedstart => $schedjob) 
	foreach ($schedjob as $job)
		$scheddata[] = $job;

echo "<hr>{$jobstatus[$settings['jobstatus']]} jobs: <table border=\"1\">";
showTable($scheddata, $titles, array(1 => "fmt_custurl",7 => "fmt_play_jobs"));
echo "</table>";
endWindow();

include_once("navbottom.inc.php");
?>