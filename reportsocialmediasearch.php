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
	$titles["twhandle"] =  _L("Twitter User");
	$titles["twstatus"] = _L("Twitter Status");
	$titles["twcontent"] = _L("Twitter Content");
}

$data = array();
$twitterids = array();
$facebookids = array();


if ($showreport || $downloadreport) {
	if ($queryresult) {

		
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
				$post["fbdest"] = array();
				$post["fbstatus"] = "";
				$post["fbcontent"] = "";
				$post["twhandle"] = "";
				$post["twstatus"] = "";
				$post["twcontent"] = "";
			}
			
			switch($row[2]) {
				case "facebook":
					$facebookids[$row[5]] = $row[5];
					$post["fbdest"][] = $row[5];
					//$post["fbdest"] .= $post["fbdest"] != ""?", ":"";
					$status = $row[7] == "1"?"Posted":"Not Posted";
					if ($status != $post["fbstatus"]) {
						$post["fbstatus"] .= $post["fbstatus"] != ""?", $status":$status;
					}
					
					$post["fbcontent"] = $row[3];
					break;
				case "twitter":
 					$post["twhandle"] = $row[5]; // Set id here to be able to map to twitter response
 					if (!isset($twitterids[$row[5]])) 
 						$twitterids[$row[5]] = $row[5]; // Set id to be able to identify id if twitter cannot get the screen_name
					$post["twstatus"] = $row[7] == "1"?"Posted":"Not Posted";
					$post["twcontent"] = $row[3];
					// Do not modify, Just print the handle 
					break;
			} 
			$data[$row[0]] = $post;
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

$fbdestinations = array();
function fmt_fbdestination($row,$index) {
	global $fbdestinations;
	$destinations = $row[$index];
	if ($destinations) {
		// Store facebook destination ids for javascript handling 
		$id = "fbd_" . count($fbdestinations);
		$fbdestinations[$id] = $destinations;
		return "<div id=\"{$id}\"><img src=\"img/ajax-loader.gif\" alt=\"\"> Loading Facebook Destinations</div>";
	} else {
		return "";
	}
}

$twdestinations = array();
function fmt_twdestination($row,$index) {
	global $twdestinations;
	$destination = $row[$index];
	if ($destination) {
		// Store twitter destination ids for javascript handling
		$id = "twd_" . count($twdestinations);
		$twdestinations[$id] = $destination;
		return "<div id=\"{$id}\"><img src=\"img/ajax-loader.gif\" alt=\"\"> Loading Twitter User</div>";
	} else {
		return "";
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
		"fbdest" => "fmt_fbdestination",
		"fbcontent" => "fmt_socialcontent",
		"twhandle" => "fmt_twdestination",
		"twcontent" => "fmt_socialcontent"
	);
	
	showTable($data, $titles,$htmlformatters);
	echo '</table>';
	
	endWindow();
}
buttons();


// Include Twitter user name lookup i report contians twitter
if (count($twitterids)) {
?>
	<script type="text/javascript">
	var twcache = $H();
	function showTwitter(response) {
	    var names = '';
	    response.each(function(itm) {
	    	twcache.set(itm.id,itm.screen_name);
	    });
	    var twdestinations = $H(<?= json_encode($twdestinations); ?>);
		if (twdestinations.size() > 0) {
			twdestinations.each(function(itm) {
				var name = twcache.get(itm.value);
				$(itm.key).update(name?name:itm.value);
			});
		}
	}
	</script>
	<? 
	$twitteridchunks = array_chunk(array_keys($twitterids), 100);
	foreach($twitteridchunks as $twidchunk) {
		echo '<script type="text/javascript" src="https://api.twitter.com/1/users/lookup.json?user_id=' . implode(",",$twidchunk) . '&callback=showTwitter"></script>';
	}
}

// Include Facebook user name lookup i report contians facebook
if (count($facebookids)) {
?>
<div id="fb-root"></div>
<script type="text/javascript">
document.observe('dom:loaded', function() {
	window.fbAsyncInit = function() {
		FB.init({appId: "<?= $SETTINGS['facebook']['appid'] ?>", status: true, cookie: false, xfbml: true});

		fill_fb_cache(uniquefacebookids);		
	};
	(function() {
		var e = document.createElement("script");
		e.type = "text/javascript";
		e.async = true;
		e.src = document.location.protocol + "//connect.facebook.net/en_US/all.js";
		document.getElementById("fb-root").appendChild(e);
	}());
});
var fbcache = $H();
var fbattemps = 3;
var uniquefacebookids = $A(<?= json_encode(array_keys($facebookids)); ?>);

function fill_fb_cache() {	
	// Cache should be full if uniquefacebookids is empty. Then display names 
	if (uniquefacebookids.size() <= 0) {
		diplayfacebookinfo();
		return;
	}
	var id = uniquefacebookids.last();
	FB.api('/' + id, function(response) {
		if (response && response.id && response.name) {
		    fbcache.set(response.id,response.name);
	    	uniquefacebookids = uniquefacebookids.without(response.id);
	    	fbattemps = 3;
		} else {
			fbattemps--;
			if (!fbattemps)
	    		uniquefacebookids = uniquefacebookids.without(uniquefacebookids.last());	
		}
	    fill_fb_cache();
	});
}

function diplayfacebookinfo(){
	var fbdestinations = $H(<?= json_encode($fbdestinations); ?>);
	if (fbdestinations.size() > 0) {
		fbdestinations.each(function(itm) {
			var destinations = '';
			itm.value.each(function(id) {
				var name = fbcache.get(id);
				destinations += ", " + (name?name:id);
			});
			$(itm.key).update(destinations.sub(", ",""));
		});
	}
}
</script>
<?
}
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
	});
});
</script>
	
<?
include_once("navbottom.inc.php");
?>