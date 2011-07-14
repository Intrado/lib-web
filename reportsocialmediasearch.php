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

$jobs = DBFindMany("Job", "from jobpost jp inner join job j on (jp.jobid = j.id) where deleted = 0 and status in ('complete','cancelled') group by j.id order by id desc limit 500","j");

$joblist = array();
foreach ($jobs as $job) {
	$joblist[$job->id] = $job->name;
}

$archivedjoblist = array();
$archivedjobs = DBFindMany("Job","from jobpost jp inner join job j on (jp.jobid = j.id)  where deleted = 2 and status!='repeating' group by j.id order by id desc limit 500","j");
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
	"value" => '{"reldate":"today"}',
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
$queryresult = false;

$desc = "";
//check for form submission
if ($button = $form->getSubmit()) { //checks for submit and merges in post data	
	if ($form->checkForDataChange()) {
		$datachange = true;
	} else if (($errors = $form->validate()) === false) { //checks all of the items in this form
		$postdata = $form->getData(); //gets assoc array of all values {name:value,...}		
		if ($button == "download") {
			$downloadreport = true;
		} else {
			$showreport = true;
		}
		$readonlyDB = readonlyDBConnect();
		$query = "select j.id, j.name,jp.type,mp.txt,ADDTIME(j.startdate, j.starttime),jp.destination,u.login, jp.posted
										from
							job j inner join jobpost jp on (jp.jobid = j.id)
							inner join message m on (j.messagegroupid = m.messagegroupid and m.subtype = jp.type)
							inner join messagepart mp on (m.id = mp.messageid)
							inner join jobtype jt on (jt.id = j.jobtypeid)
							inner join user u on (j.userid = u.id) 
							where m.type = 'post' and j.status in ('complete','cancelled') and jp.type in ";
		$types = array();
		if (getSystemSetting('_hasfacebook', false)) {
			$types[] = "facebook";
		}
		if (getSystemSetting('_hastwitter', false)) {
			$types[] = "twitter";
		}

		$query .= "('" . implode("','",$types) . "') ";
		
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
					$query .= $extrasql;
					$queryresult = Query($query,$readonlyDB, array(date("Y-m-d", $startdate),date("Y-m-d", $enddate)));
				}
				break;
			case "job":
				$extrasql = "and j.id = ?";
				$query .= $extrasql;
				$queryresult = Query($query,$readonlyDB,array($postdata["jobid"]));
				break;
			case "archivedjob":
				$extrasql = "and j.id = ?";
				$query .= $extrasql;
				$queryresult = Query($query,$readonlyDB,array($postdata["archivedjobid"]));
				break;
		}
	}
}

////////////////////////////////////////////////////////////////////////////////
// Prepare Report Data
////////////////////////////////////////////////////////////////////////////////
$titles = array(
	"jobname" => _L("Job Name"),
	"user" =>  _L("Submitted by"),
	"date" => _L("Post Date")
);

if (getSystemSetting('_hasfacebook', false)) {
	$titles["fbdest"] = _L("Facebook Destination");
	$titles["fbstatus"] = _L("Facebook Status");
	$titles["fbcontent"] = _L("Facebook Content");
}

if (getSystemSetting('_hastwitter', false)) {
	$titles["twhandle"] =  _L("Twitter Handle");
	$titles["twstatus"] = _L("Twitter Status");
	$titles["twcontent"] = _L("Twitter Content");
}

$data = array();

if ($showreport || $downloadreport) {
	if ($queryresult) {
		$twitterids = array();
		$facebookids = array();
		
		// store facebook account names to avoid contacting facebook for each individual post if the pageid is the same
		$fbaccountnames = array();
		
		// Prepare and merge the post items
		while ($row = DBGetRow($queryresult)) {
			
			// Merge Facebook and Twitter posts into one report post item 
			if (isset($data[$row[0]])) {
				$post = $data[$row[0]];
			}else {
				$post["jobname"] = $row[1];
				$post["user"] = $row[6];
				$post["date"] = $row[4];
				$post["fbdest"] = "";
				$post["fbstatus"] = "";
				$post["fbcontent"] = "";
				$post["twhandle"] = "";
				$post["twstatus"] = "";
				$post["twcontent"] = "";
			}
			
			switch($row[2]) {
				case "facebook":
					$post["fbdest"] .= $post["fbdest"] != ""?", ":"";
					$status = $row[7] == "1"?"Posted":"Not Posted";
					if ($status != $post["fbstatus"]) {
						$post["fbstatus"] .= $post["fbstatus"] != ""?", $status":$status;
					}
					
					$post["fbcontent"] = $row[3];
					if (isset($fbaccountnames[$row[5]])) {
						// Look up account page id in cache or if not there fetch from fb 
						$post["fbdest"] .= $fbaccountnames[$row[5]];
					} else {
						$fbaccountnames[$row[5]] = $row[5];
						$attempts = 3;
						while($attempts) {
							try {
								$accountinfo = $facebookapi->api("/$row[5]", 'GET', array());
								if ($accountinfo) {
									// using first name to check if it is a person or a standalone page.
									$name = isset($accountinfo["first_name"]) ? $accountinfo["name"] . "'s wall":$accountinfo["name"];
									$fbaccountnames[$row[5]] = $name;
									$post["fbdest"] .= $name;
									break;
								} else {
									$attempts--;
								}
							} catch (FacebookApiException $e) {
								error_log($e);
								$attempts--;
							}
						} 
						// Fill in pageid if failed to connect to fb
						if ($attempts == 0)
							$post["fbdest"] .= $row[5];
						
					}
					break;
				case "twitter":
					$post["twhandle"] = $row[5]; // Set id here to be able to map to twitter response
					if (!isset($twitterids[$row[5]])) 
						$twitterids[$row[5]] = $row[5]; // Set id to be able to identify id if twitter can not get the screen_name
					$post["twstatus"] = $row[7] == "1"?"Posted":"Not Posted";
					$post["twcontent"] = $row[3];
					// Do not modify, Just print the handle 
					break;
			} 
			$data[$row[0]] = $post;
		}	
		
		// Get twitter screennames in batch since twitter has a request limit of 150 request per hour for each ip for unathenticated requests
		// Since twitter supports batch lookup of users the report will only have to do one request to twitter.
		// https://dev.twitter.com/docs/rate-limiting
		if(count($twitterids) > 0) {
			if (count($twitterids) > 100) {
				$twitterids = array_slice($twitterids,100);
				error_log("Limiting request for twitter to 100 userids. Report with " . count($twitterids) . "twitter ids?");
			}
			
			$referer = $_SERVER["HTTP_REFERER"];
			if (!$referer) {
				$referer = (isset($SETTINGS['translation']['referer']) && $SETTINGS['translation']['referer'])?$SETTINGS['translation']['referer']:"http://asp.schoolmessenger.com";
			}
			$url = "http://api.twitter.com/1/users/lookup.json?user_id=" . implode(",",array_keys($twitterids));
			$context_options = array ('http' => array ('method' => 'GET','header'=> "Referer: $referer"));
			$context = stream_context_create($context_options);
			$attempts = 3;
			while($attempts) {
				$fp = @fopen($url, 'rb', false, $context);
				if ($fp) {
					$response = @stream_get_contents($fp);
					if ($response) {
						$result = json_decode($response);
						foreach($result as $user) {
							$twitterids[$user->id] = $user->screen_name;
						}
						foreach($data as $jobid => $post) {
							if ($post["twhandle"] != "") {							
								$data[$jobid]["twhandle"] = $twitterids[$post["twhandle"]];	
							}					
						}
						break;
					} else {
						$attempts--;
						error_log("Unable to read from $url");
					}
				} else {
					$attempts--;
					error_log("Unable to send user lookup request to $url");
				}
			}
		} 
	}
}
////////////////////////////////////////////////////////////////////////////////
// Display Functions
////////////////////////////////////////////////////////////////////////////////

// Save ids in $tipids for posts that are too long to fit In the table to be able to create a tooltip
$tipsids = array();
function fmt_socialcontent($row,$index) {
	global $tipsids;
	$id = "hvc_" . count($tipsids);
	$contentlength = 25;
	$content = $row[$index];
	if(strlen($content) > $contentlength) {
		$tipsids[] = $id;
		// For posts that are too long a hidden div is created with the full text to show in a tooltip
		return "<div id=\"{$id}\">" . escapehtml(substr($content,0,$contentlength-3) . "...") . "</div><div id='{$id}_long' style='display:none'>" . escapehtml($content) . "</div>";
	}
	return escapehtml($content);
}

////////////////////////////////////////////////////////////////////////////////
// Generate CSV Report
////////////////////////////////////////////////////////////////////////////////

if ($downloadreport) {
	header("Pragma: private");
	header("Cache-Control: private");
	header("Content-disposition: attachment; filename=report.csv");
	header("Content-type: application/vnd.ms-excel");
	// Note: Post date time could be inaccurate since it is job starttime. Time could be before the accurate post time so until this is fixed show only the date
	// TODO find a way to add a accurate timestamp for postdate
	$csvformatters = array ("date" => "fmt_txt_date");
	showCsvData($data, $titles,$csvformatters);
	exit();
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
	echo '<table width="100%" cellpadding="3" cellspacing="1" class="list">';
	// Note: Post date time could be inaccurate since it is job starttime. Time could be before the accurate post time so until this is fixed show only the date
	// TODO find a way to add a accurate timestamp for postdate
	$htmlformatters = array (
		"date" => "fmt_txt_date",
		"fbcontent" => "fmt_socialcontent",
		"twcontent" => "fmt_socialcontent"
	);
	
	showTable($data, $titles,$htmlformatters);
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