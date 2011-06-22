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


$helpsteps = array ();

$buttons = array(submit_button(_L('View Report'),"view","fugue/magnifier"),submit_button(_L('Download CSV Report'),"download","fugue/arrow_270"));
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
$downloadreport = false;
$desc = "";
//check for form submission
if ($button = $form->getSubmit()) { //checks for submit and merges in post data	
	if ($form->checkForDataChange()) {
		$datachange = true;
	} else if (($errors = $form->validate()) === false) { //checks all of the items in this form
		$postdata = $form->getData(); //gets assoc array of all values {name:value,...}
		$reportjobs = array();
		$reportmessages = array();
		if ($button == "download") {
			$downloadreport = true;
		} else {
			$showreport = true;
		}
		$readonlyDB = readonlyDBConnect();
		
		$messagequery = "select j.id, j.name,jp.type,mp.txt,ADDTIME(j.startdate, j.starttime),jp.destination,u.login
										from
							job j inner join jobpost jp on (jp.jobid = j.id)
							inner join message m on (j.messagegroupid = m.messagegroupid and m.subtype = jp.type)
							inner join messagepart mp on (m.id = mp.messageid)
							inner join jobtype jt on (jt.id = j.jobtypeid)
							inner join user u on (j.userid = u.id) 
							where m.type = 'post' and jp.posted=1 and jp.type in ";
		$types = array();
		if (getSystemSetting('_hasfacebook', false)) {
			$types[] = "facebook";
		}
		if (getSystemSetting('_hastwitter', false)) {
			$types[] = "twitter";
		}

		$messagequery .= "('" . implode("','",$types) . "') ";
		
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
					$messagequery .= $extrasql;
					$reportmessages = Query($messagequery,$readonlyDB, array(date("Y-m-d", $startdate),date("Y-m-d", $enddate)));
				}
				break;
			case "job":
				$extrasql = "and j.id = ?";
				$messagequery .= $extrasql;
				$reportmessages = Query($messagequery,$readonlyDB,array($postdata["jobid"]));
				break;
			case "archivedjob":
				$extrasql = "and j.id = ?";
				$messagequery .= $extrasql;
				$reportmessages = Query($messagequery,$readonlyDB,array($postdata["archivedjobid"]));
				break;
		}
	}
}

////////////////////////////////////////////////////////////////////////////////
// Prepare Report Data
////////////////////////////////////////////////////////////////////////////////
$data = array();
if ($showreport || $downloadreport) {
	
	if (getSystemSetting('_hasfacebook', false) && $USER->authorize("facebookpost")  && fb_hasValidAccessToken()) {
		$authpages = getFbAuthorizedPages();
		$authwall = getSystemSetting("fbauthorizewall");
		$accesstoken = $USER->getSetting("fb_access_token", false);
	}

	if ($reportmessages) {
		
		// store facebook account names to avoid contacting facebook for each individual post if the pageid is the same
		$fbaccountnames = array();
		
		// Prepare and merge the post items
		while ($row = DBGetRow($reportmessages)) {
			
			// Merge Facebook and Twitter posts into one report post item 
			if (isset($data[$row[0]])) {
				$post = $data[$row[0]];
			}else {
				$post["jobname"] = $row[1];
				$post["user"] = $row[6];
				$post["date"] = $row[4];
				$post["fbdest"] = "";
				$post["fbcontent"] = "";
				$post["twhandle"] = "";
				$post["twcontent"] = "";
			}
			
			switch($row[2]) {
				case "facebook":
					$post["fbdest"] .= $post["fbdest"] != ""?", ":"";
					
					$post["fbcontent"] = $row[3];
					if ($row[5] == "wall") {
						$post["fbdest"] .= _L('Wall');
					} else if (isset($fbaccountnames[$row[5]])) {
						// Look up account page id in cache or if not there fetch from fb 
						$post["fbdest"] .= $fbaccountnames[$row[5]];
					} else {
						try {
							$accountinfo = $facebookapi->api("/$row[5]", 'GET', array());
							if ($accountinfo) {
								$fbaccountnames[$row[5]] = $accountinfo["name"];
								$post["fbdest"] .= $accountinfo["name"];
							} else {
								$fbaccountnames[$row[5]] = $row[5];//_L("Not Available");
								$post["fbdest"] .= $row[5]; //_L("Not Available");
							}
						} catch (FacebookApiException $e) {
							$fbaccountnames[$row[5]] = $row[5];// _L("Not Available");
							$post["fbdest"] = $row[5];// _L("Not Available");
							error_log($e);
						}
					}
					break;
				case "twitter":
					$post["twhandle"] = $row[5];
					$post["twcontent"] = $row[3];
					// Do not modify, Just print the handle 
					break;
			} 
			
			$data[$row[0]] = $post;
		}
	}
}

////////////////////////////////////////////////////////////////////////////////
// Generate CSV Report
////////////////////////////////////////////////////////////////////////////////

if ($downloadreport) {
	header("Pragma: private");
	header("Cache-Control: private");
	header("Content-disposition: attachment; filename=report.csv");
	header("Content-type: application/vnd.ms-excel");

	//generate the CSV header
	echo _L("Job Name") . ',' . _L("Submitted by") . ',' . _L("Post Date");
	if (getSystemSetting('_hasfacebook', false)) {
		echo ',' . _L("Facebook Destination") . ',' . _L("Facebook Content");
	}
	if (getSystemSetting('_hastwitter', false)) {
		echo ',' . _L("Twitter Handle") . ',' . _L("Twitter Content");
	}
	
	echo "\r\n";
	
	foreach ($data as $post) {
		
		$count = 0;
		foreach ($post as $key => $item) {
			echo ($count > 0 ?',':'') . '"' . addslashes($item) . '"';
			$count++;
		}
		echo "\r\n";
	}
	exit();
}


////////////////////////////////////////////////////////////////////////////////
// Display Functions
////////////////////////////////////////////////////////////////////////////////

// Save ids in $tipids for posts that are too long to fit In the table to be able to create a tooltip
$tipsids = array();
function ConditionalContentLink($id,$content) {
	global $tipsids;
	$contentlength = 25;
	if(strlen($content) > $contentlength) {
		$tipsids[] = $id;
		// For posts that are too long a hidden div is created with the full text to show in a tooltip
		return "<div id=\"{$id}\">" . escapehtml(substr($content,0,$contentlength-3) . "...") . "</div><div id='{$id}_long' style='display:none'>" . escapehtml($content) . "</div>";
	} else {
		return escapehtml($content);
	}
}

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
	startWindow(_L('Report Details:'));
	echo '<table class="list" style="width:100%;text-align:left;" cellpadding="3" cellspacing="1">';
	echo '<tr class="listHeader"><th>Job Name</th><th>Submitted by</th><th>Post Date</th>';
	if (getSystemSetting('_hasfacebook', false)) {
		echo '<th>' . _L("Facebook Destination") . '</th><th>' . _L("Facebook Content") . '</th>';
	}
	if (getSystemSetting('_hastwitter', false)) {
		echo '<th>' . _L("Twitter Handle") . '</th><th>' . _L("Twitter Content") . '</th>';
	}
	echo '</tr>';
	$alt = 0;
	foreach ($data as $post) {
		$alt++;
		echo $alt % 2 ? '<tr>' : '<tr class="listAlt">';
		echo "<td>" . escapehtml($post["jobname"]) . "</td><td>" . escapehtml($post["user"]) . "</td><td>" . escapehtml($post["date"]) . "</td>";
		
		if (getSystemSetting('_hasfacebook', false)) {
			echo "<td>" . escapehtml($post["fbdest"]) . "</td><td>" . ConditionalContentLink("fb_$alt", $post["fbcontent"]) . "</td>";
		}
		if (getSystemSetting('_hastwitter', false)) {
			echo "<td>" . escapehtml($post["twhandle"]) . "</td><td>" . ConditionalContentLink("tw_$alt", $post["twcontent"]) . "</td>";
		}
		echo '</tr>';
	}
	echo '</table>';
	
	endWindow();
}
buttons();
?>
<script type="text/javascript">
document.observe('dom:loaded', function() {
	var tipids = [<?= (count($tipsids) > 0 ? "'" . implode("','",$tipsids) . "'" : "") ?>];
	tipids.each(function(id) {
		new Tip($(id), $(id + '_long').innerHTML, {
			style: 'protogrey',
			radius: 4,
			border: 4,
			hideAfter: 0.5,
			stem: 'rightMiddle',
			hook: {  target: 'leftMiddle', tip: 'rightMiddle' }
		});
	})
});
</script>
<?
include_once("navbottom.inc.php");
?>