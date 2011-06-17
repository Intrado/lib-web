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
require_once("obj/Job.obj.php");
require_once("inc/date.inc.php");
require_once("inc/formatters.inc.php");
require_once("inc/facebook.php");
require_once("inc/facebook.inc.php");

////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////

if(!(getSystemSetting('_hasfacebook', false) || getSystemSetting('_hastwitter', false) || !($USER->authorize('viewsystemreports') || $USER->authorize("facebookpost") || $USER->authorize("twitterpost"))))  {
	redirect('unauthorized.php');
}

////////////////////////////////////////////////////////////////////////////////
// Form Data
////////////////////////////////////////////////////////////////////////////////

$jobs = DBFindMany("Job", "from jobpost jp inner join job j on (jp.jobid = j.id) where deleted = 0 and status in ('active','complete','cancelled','cancelling') and j.questionnaireid is null order by id desc limit 500","j");

$joblist = array();
foreach ($jobs as $job) {
	$joblist[$job->id] = $job->name;
}

$archivedjoblist = array();
$archivedjobs = DBFindMany("Job","from jobpost jp inner join job j on (jp.jobid = j.id)  where deleted = 2 and status!='repeating' and j.questionnaireid is null order by id desc limit 500","j");
foreach ($archivedjobs as $job) {
	$archivedjoblist[$job->id] = $job->name;
}

$formdata = array();
$formdata["searchby"] = array(
	"label" => _L("Search By"),
	"fieldhelp" => _L("Search by job name or the date it was sent."),
	"value" => "date",
	"control" => array("RadioButton", "values" => array("date" => _L("Date"),"job" => _L("Job"),"archivedjob" => _L("Archived Job"))),
	"validators" => array(array("ValRequired"), array("ValInArray", "values" => array("date", "job", "archivedjob"))),
	"helpstep" => 1
);
$formdata["dateoptions"] = array(
	"label" => _L("Date Options"),
	"value" => "today",
	"control" => array("ReldateOptions"),
	"validators" => array(array("ValReldate")),
	"helpstep" => 1
);
$formdata["jobid"] = array(
	"label" => _L("Jobs"),
	"value" => '',
	"control" => array("SelectMenu", "values" => $joblist),
	"validators" => array(array("ValInArray", "values" => array_keys($joblist))),
	"helpstep" => 1
);

$formdata["archivedjobid"] = array(
	"label" => _L("Archived Jobs"),
	"value" => '',
	"control" => array("SelectMenu", "values" => $archivedjoblist),
	"validators" => array(array("ValInArray", "values" => array_keys($archivedjoblist))),
	"helpstep" => 1
);


$helpsteps = array (
	_L('TODO')
);

$buttons = array(submit_button(_L('View Report'),"submit","tick"));
$form = new Form("socialmediareport",$formdata,$helpsteps,$buttons);
$form->ajaxsubmit = false;
////////////////////////////////////////////////////////////////////////////////
// Form Data Handling
////////////////////////////////////////////////////////////////////////////////

//check and handle an ajax request (will exit early)
//or merge in related post data
$form->handleRequest();

$datachange = false;
$errors = false;
$showreport = false;
$desc = "";
//check for form submission
if ($button = $form->getSubmit()) { //checks for submit and merges in post data	
	if ($form->checkForDataChange()) {
		$datachange = true;
	} else if (($errors = $form->validate()) === false) { //checks all of the items in this form
		$postdata = $form->getData(); //gets assoc array of all values {name:value,...}
		$reportjobs = array();
		$reportmessages = array();
		$showreport = true;
		$readonlyDB = readonlyDBConnect();
		
		$jobquery = "select distinct j.name,jt.name, u.login,
					j.startdate,j.enddate,j.starttime,j.endtime
					from
					job j inner join jobpost jp on (jp.jobid = j.id) 
					inner join jobtype jt on (jt.id = j.jobtypeid)
					inner join user u on (j.userid = u.id) where jp.posted=1 ";
		
		$messagequery = "select j.name,jp.type,mp.txt,ADDTIME(j.startdate, j.starttime),jp.destination,u.login
										from
							job j inner join jobpost jp on (jp.jobid = j.id)
							inner join message m on (j.messagegroupid = m.messagegroupid)
							inner join messagepart mp on (m.id = mp.messageid)
							inner join jobtype jt on (jt.id = j.jobtypeid)
							inner join user u on (j.userid = u.id) 
							where m.type = 'post' and jp.posted=1 ";
		
		switch($postdata["searchby"]) {
			case "date":
				$dateOptions = json_decode($postdata['dateoptions'], true);
				if (!empty($dateOptions['reldate'])) {
					if ($dateOptions['reldate'] == 'xdays' && isset($dateOptions['xdays'])) {
						$dateOptions['lastxdays'] = $dateOptions['xdays'];
					}
					list($startdate, $enddate) = getStartEndDate($dateOptions['reldate'], $dateOptions);
					$desc = " From: " . date("m/d/Y", $startdate) . " To: " . date("m/d/Y", $enddate);
					$extrasql = "and j.finishdate between ? and ? + interval 1 day";
					$jobquery .= $extrasql;
					$messagequery .= $extrasql;
					$reportjobs = Query($jobquery,$readonlyDB, array(date("Y-m-d", $startdate),date("Y-m-d", $enddate)));
					$reportmessages = Query($messagequery,$readonlyDB, array(date("Y-m-d", $startdate),date("Y-m-d", $enddate)));
				}
				break;
			case "job":
				$extrasql = "and j.id = ?";
				$jobquery .= $extrasql;
				$messagequery .= $extrasql;
				$reportjobs = Query($jobquery,$readonlyDB,array($postdata["jobid"]));
				$reportmessages = Query($messagequery,$readonlyDB,array($postdata["jobid"]));
				break;
			case "archivedjob":
				$extrasql = "and j.id = ?";
				$jobquery .= $extrasql;
				$messagequery .= $extrasql;
				$reportjobs = Query($jobquery,$readonlyDB,array($postdata["archivedjobid"]));
				$reportmessages = Query($messagequery,$readonlyDB,array($postdata["archivedjobid"]));
				break;
		}
	}
}

////////////////////////////////////////////////////////////////////////////////
// Display Functions
////////////////////////////////////////////////////////////////////////////////


////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$PAGE = "reports:reports";
$TITLE = _L('Social Media Log');

include_once("nav.inc.php");

?>
<script type="text/javascript">
	<? Validator::load_validators(array("ValReldate")); ?>
</script>

<script type="text/javascript">
var formname = '<?=$form->name?>';
var displaySearchItem = function() {
	$(formname + '_dateoptions').up('tr').hide();
	$(formname + '_jobid').up('tr').hide();
	$(formname + '_archivedjobid').up('tr').hide();
	switch(form_get_value($(formname),formname + '_searchby')) {
		case 'date':
			$(formname + '_dateoptions').up('tr').show();
			break;
		case 'job':
			$(formname + '_jobid').up('tr').show();
			break;
		case 'archivedjob':
			$(formname + '_archivedjobid').up('tr').show();
			break;
	}
}


document.observe('dom:loaded', function() {
	var radio = $(formname + '_searchby').select('input');
	displaySearchItem();
	radio.invoke('observe', 'click', function(event) {
		displaySearchItem();
	});
});
</script>

<?

buttons(icon_button("Done", "tick",false,"reports.php"));
startWindow(_L('Options'));
echo $form->render();
endWindow();

if ($showreport) {
	
	if (getSystemSetting('_hasfacebook', false) && $USER->authorize("facebookpost")  && fb_hasValidAccessToken()) {
		$authpages = getFbAuthorizedPages();
		$authwall = getSystemSetting("fbauthorizewall");
		$accesstoken = $USER->getSetting("fb_access_token", false);
	}
	$titles = array(0 => "Job Name",
					1 => "Job Type",
					2 => "Submitted by",
					3 => "Scheduled Date",
					5 => "Scheduled Time");

	$formatters = array(3 => "fmt_scheduled_date",5 => "fmt_scheduled_time");
	startWindow(_L('Summary: ' . $desc));
	
	echo '<table class="list" cellpadding="3" cellspacing="1" width="100%">';
		
	$data = array();
	if ($reportjobs) {
		while ($row = DBGetRow($reportjobs)) {
			$data[] = $row;
		}
	}
	showTable($data, $titles, $formatters);
	echo '</table>';
	
	endWindow();
	startWindow(_L('Report Details:'));
	$titles = array(0 => "Job Name",
					5 => "Submitted by",
					3 => "Post Date",
					1 => "Type",
					4 => "Destination",
					2 => "Content"
					);

	$formatters = array(
						2 => "fmt_limit_25",
						3 => "fmt_date");

	echo '<table class="list" cellpadding="3" cellspacing="1" width="100%">';
	$data = array();
	
	if ($reportmessages) {
		
		// store facebook account names to avoid contacting facebook for each individual post if the pageid is the same
		$fbaccountnames = array();
		while ($row = DBGetRow($reportmessages)) {
			switch($row[1]) {
				case "facebook":
					if ($row[4] == "wall")
						break;
					// Look up account page id in cache or if not there fetch from fb 
					if (isset($fbaccountnames[$row[4]])) {
						$row[4] = $fbaccountnames[$row[4]];
					} else {
						try {
							$accountinfo = $facebookapi->api("/$row[4]", 'GET', array());
							if ($accountinfo) {
								$fbaccountnames[$row[4]] = $accountinfo["name"];
								$row[4] = $accountinfo["name"];
							} else {
								$fbaccountnames[$row[4]] = _L("Not Available");
								$row[4] =  _L("Not Available");
							}
						} catch (FacebookApiException $e) {
							$fbaccountnames[$row[4]] =  _L("Not Available");
							$row[4] =  _L("Not Available");
							error_log($e);
							return false;
						}
					}
					break;
				case "twitter":
					// Do not modify, Just print the handle 
					break;
				default:
					// output blank rather than output the destination field directly if unknown type
					$row[4] = "";
			} 
			
			$data[] = $row;
		}
	}
	showTable($data, $titles, $formatters);
	echo '</table>';
	
	endWindow();
}
buttons();
include_once("navbottom.inc.php");
?>