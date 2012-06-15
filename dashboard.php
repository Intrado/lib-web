<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("inc/common.inc.php");

////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////

// everyone can see dashboard page

////////////////////////////////////////////////////////////////////////////////
// Action/Request Processing
////////////////////////////////////////////////////////////////////////////////

// List all possible request values and set their defaults
$ranges = array("7days" => "7 Days","month" => "Month","year" => "Year");
$requestValues = array(
			"showactivity" => 'me',
			"daterange" => '7days',
);


// Update request values from what is passed in
foreach($requestValues as $key => $values) {
	if (isset($_REQUEST[$key])) {
		$requestValues[$key] = $_REQUEST[$key];
	}
}

$useridList = array();
$useridList = QuickQueryList("select u.id, concat_ws(' ', u.firstname, u.lastname) from userlink ul inner join user u on (ul.subordinateuserid = u.id) where ul.userid=?",true,false,array($USER->id));
$useridList[$USER->id] = _L("Me");
$start_datetime = time();
switch($requestValues["daterange"]) {
	case "7days":
		$start_datetime -= 604800;
		break;
	case "month":
		$start_datetime -= 2592000;
		break;
	case "year":
		$start_datetime -= 31536000;
		break;
}

$start_datetime = date("Y-m-d h:m:s",$start_datetime);
$end_datetime = date("Y-m-d h:m:s",time());

////////////////////////////////////////////////////////////////////////////////
// Stats functions
////////////////////////////////////////////////////////////////////////////////

function generateStats($useridList, $start_datetime, $end_datetime) {
	error_log(json_encode($useridList));
	
	// sql query parameters, always in same order for all stats
	$params = array();
	$params[] = $start_datetime;
	$params[] = $end_datetime;
	$params = array_merge($params, $useridList);
	
	$stats = array();
	
	// broadcasts
	$query = "select count(*) from job j " .
		"where j.status in ('procactive','active','complete','cancelled','cancelling') and " .
		"j.activedate >= ? and j.activedate <= ? and " .
		"j.userid in (" . repeatWithSeparator("?", ",", count($useridList)) . ")";
		
	$stats["total_jobs"] = QuickQuery($query, null, $params);

	// languages
	$query = "select count(distinct(m.languagecode)) from message m " .
		"join messagegroup mg on (mg.id = m.messagegroupid) " .
		"join job j on (j.messagegroupid = mg.id) " .
		"where j.status in ('procactive','active','complete','cancelled','cancelling') and " .
		"j.activedate >= ? and j.activedate <= ? and " .
		"j.userid in (" . repeatWithSeparator("?", ",", count($useridList)) . ")";
		
	$stats["total_languages"] = QuickQuery($query, null, $params);
	
	// total of each message type
	$query = "select m.type as type, count(*) as total from message m " .
		"join messagegroup mg on (mg.id = m.messagegroupid) " .
		"join job j on (j.messagegroupid = mg.id) " .
		"where  " .
		"j.status in ('procactive','active','complete','cancelled','cancelling') and " .
		"j.activedate >= ? and j.activedate <= ? and " .
		"j.userid in (" . repeatWithSeparator("?", ",", count($useridList)) . ") " .
		"group by m.type";
	
	// init to zero, in case none found in query
	$stats["total_phones"] = 0;
	$stats["total_emails"] = 0;
	$stats["total_sms"] = 0;
	$stats["total_posts"] = 0;
	
	$rows = QuickQueryMultiRow($query, true, null, $params);
	foreach ($rows as $row) {
		if (!strcmp("phone", $row['type']))
			$stats['total_phones'] = $row['total'];
		if (!strcmp("email", $row['type']))
			$stats['total_emails'] = $row['total'];
		if (!strcmp("sms", $row['type']))
			$stats['total_sms'] = $row['total'];
		if (!strcmp("post", $row['type']))
			$stats['total_posts'] = $row['total'];
	}
	
	$total_types = $stats['total_phones'] + $stats["total_emails"] + $stats["total_sms"] + $stats["total_posts"];
	$stats["percentage_slice"] = $total_types != 0 ? 100/$total_types : 0;
	
	// top jobtypes
	$query = "select jt.name as name, count(*) as total from job j " .
		"left join jobtype jt on (jt.id = j.jobtypeid) " .
		"where j.status in ('procactive','active','complete','cancelled','cancelling') and " .
		"j.activedate >= ? and j.activedate <= ? and " .
		"j.userid in (" . repeatWithSeparator("?", ",", count($useridList)) . ") " .
		"group by j.jobtypeid " .
		"order by total desc";
		
	$stats["top_jobtypes"] = QuickQueryMultiRow($query, true, null, $params);

	// top senders
	$query = "select concat_ws(' ', u.firstname, u.lastname) as name, count(*) as total from job j " .
		"left join user u on (u.id = j.userid) " .
		"where j.status in ('procactive','active','complete','cancelled','cancelling') and " .
		"j.activedate >= ? and j.activedate <= ? and " .
		"j.userid in (" . repeatWithSeparator("?", ",", count($useridList)) . ") " .
		"group by j.userid " .
		"order by total desc";
		
	$stats["top_users"] = QuickQueryMultiRow($query, true, null, $params);

	$stats["total_users"] = count($stats["top_users"]);

	return $stats;
}

//used in listcontacts as a callback for gen2cache
function query_jobstats ($jobid) {
	//FIXME this should use the slave
	return QuickQueryRow("select
		sum(rc.type='phone') as total_phone,
		sum(rc.type='email') as total_email,
		sum(rc.type='sms') as total_sms,
		from reportperson rp
		left join reportcontact rc on (rp.jobid = rc.jobid and rp.type = rc.type and rp.personid = rc.personid)
		where rp.jobid = ?", true, false, array($jobid));
}


function fmt_job_content($obj, $name) {
	return "<img src=\"themes/newui/phone-grey.png\"/> <img src=\"themes/newui/email-grey.png\"/> <img src=\"themes/newui/sms-grey.png\"/> <img src=\"themes/newui/social-grey.png\"/>";
}

function fmt_job_recipients($obj, $name) {
	$lists = QuickQueryList("select listid from joblist where jobid = ?", false, false, array($obj->id));
	$total = 0;
	foreach ($lists as $id) {
		//expect the list mod date hasnt changed when using cache
		$list = new PeopleList($id);
		$expect = array("modifydate" => $list->modifydate);
		$total += gen2cache(300, $expect, null, "calc_startpage_list_info", $id);
	}
	return $total;
}


////////////////////////////////////////////////////////////////////////////////
// Data
////////////////////////////////////////////////////////////////////////////////

if ($requestValues["showactivity"] == "me") {
	$queryUsers = array($USER->id);
} else if ($requestValues["showactivity"] == "everyone") {
	$queryUsers = array_keys($useridList);
} else {
	$queryUsers = array($requestValues["showactivity"] + 0);
}

// sql explained key useraccess
$query = "select max(j.activedate) from job j " .
	"where j.userid in (" . repeatWithSeparator("?", ",", count($queryUsers)) . ")";
	
$expect = QuickQuery($query, null, $queryUsers);
// keep one day, key generated
$stats = gen2cache(60*60*24, $expect, null, "generateStats", $queryUsers, $start_datetime, $end_datetime);


$jobtemplates = DBFindMany("Job", "from job where userid=? and status='template' and not deleted and type = 'notification' order by modifydate desc",false,array($USER->id));

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE = "start:start";
$TITLE = _L("Welcome");

include("nav.inc.php");
?>

	<div class="wrapper">
	
	<div class="main_activity">
<?if (count($useridList) > 0) {?>
		<div class="users cf">
			<p>Show activity for
			<select onchange="window.location='start.php?showactivity=' + this.options[selectedIndex].value + '&<?= http_build_query(array_diff_key($requestValues,array("showactivity" => ''))) ?>'">
				<option value="me" <?= ($requestValues["showactivity"]=="me")?"selected":"";?>><?= _L("Me")?></option>
				<option value="everyone" <?= ($requestValues["showactivity"]=="everyone")?"selected":"";?>><?= _L("Everyone")?></option>
				<optgroup label="<?= _L("Individual Users")?>">
				<?
				foreach ($useridList as $userid => $displayname) {
					if ($userid != $USER->id)
						echo "<option value=\"$userid\"" . ($requestValues["showactivity"]==$userid?"selected":"") . ">$displayname</option>";
				}
				?>
				</optgroup>
			</select></p>
		</div>
<?}?>
		
		<div class="window summary">
			<div class="window_title_wrap">
			<h2><?= _L("Activity Summary")?></h2>
			<div class="btngroup">
				<?
				$urlQueryState = http_build_query(array_diff_key($requestValues,array("daterange" => '')));
				
				foreach ($ranges as $range => $display) {
					echo "<button " . ($requestValues["daterange"] == $range?"class=\"active\"":"") . " onclick=\"window.location='start.php?daterange=$range&$urlQueryState'\">$display</button>";
				}
				?>
			</div>
			</div>
			
			<div class="window_body_wrap cf">
			<div class="col">
				<h4><?= getJobsTitle()?></h4>
				<p><strong><?=$stats["total_jobs"]?></strong></p>
				<p><?= _L("%s Languages", $stats["total_languages"])?></p>
				<p><?=_L("%s Senders", $stats["total_users"])?></p>
			</div>
			
			<div class="col bloc">
				<h4><?= _L("Content Mix")?></h4>
				<img class="dashboard_graph" src="graph_dashboard.png.php?blue=<?=$stats["total_phones"]?>&red=<?=$stats["total_emails"]?>&organge=<?=$stats["total_sms"]?>&green=<?=$stats["total_posts"]?>" />
				<ul>
				<li><img src="themes/newui/images/phone-blue.png"/>&nbsp;<?= round($stats["percentage_slice"] * $stats["total_phones"]) ?>%</li>
				<li><img src="themes/newui/images/email-red.png"/>&nbsp;<?= round($stats["percentage_slice"] * $stats["total_emails"]) ?>%</li>
				<li><img src="themes/newui/images/sms-orange.png"/>&nbsp;<?= round($stats["percentage_slice"] * $stats["total_sms"]) ?>%</li>
				<li><img src="themes/newui/images/social-green.png"/>&nbsp;<?= round($stats["percentage_slice"] * $stats["total_posts"]) ?>%</li>
				</ul>
				
			</div>
			
			<div class="col bloc">
				<h4><?= _L("Top Types")?></h4>
				<ul>
<?
				for ($i = 0; $i < 4; $i++) {
					if (!isset($stats['top_jobtypes'][$i]))
						break;
?>
					<li><span><?=$stats["top_jobtypes"][$i]['total']?></span><?=$stats["top_jobtypes"][$i]['name']?></li>
<?
				}
?>
				</ul>
			</div>
			
			<div class="col bloc">
				<h4><?= _L("Top Senders")?></h4>
				<ul>
<?
				for ($i = 0; $i < 4; $i++) {
					if (!isset($stats['top_users'][$i]))
						break;
?>
					<li><span><?=$stats["top_users"][$i]['total']?></span><?=$stats["top_users"][$i]['name']?></li>
<?
				}
?>
				</ul>
			</div>
			</div><!-- /window_body_wrap -->
		</div>
		
		<div class="window broadcasts">
			<div class="window_title_wrap"><h2><?= getJobsTitle()?></h2></div>
			
			<div class="window_body_wrap">
			<h3><?= _L("In Progress")?><span><?= _L("(Sending Now)")?></span></h3>
			<table class="jobprogress info" id="activejobs">
				<thead>
				</thead>
				<tbody>
				</tbody>
			</table>
			<div id="moreactivejobs"></div>
			
			<h3><?= _L("On Deck")?> <span><?= _L("(Sending Soon)")?></span></h3>
			<table class="info" id="scheduledjobs">
				<thead>
				</thead>
				<tbody>
				</tbody>
			</table>
			<div id="morescheduledjobs"></div>
			
			<h3><?= _L("Completed")?> <span><?= _L("(Already Sent)")?></span></h3>
			<table class="info" id="completedjobs">
				<thead>
				</thead>
				<tbody>
				</tbody>
			</table>
			<div id="morecompletedjobs"></div>
			
			</div><!-- /window_body_wrap -->
		</div>
	</div><!-- end main_activity -->
	
	
	<div class="main_aside">
		<a class="bigbtn" href="message_sender.php"><span><?= _L("New %s",getJobTitle())?></span></a>
	
		<div class="templates">
			<h3><?= _L("%s Templates",getJobTitle())?></h3>
			<ul>
			<?
			if (count($jobtemplates)) {
				foreach($jobtemplates as $jobtemplate) {
					echo "<li><a href=\"message_sender.php?templateid={$jobtemplate->id}\">{$jobtemplate->name}</a></li>";
				}
			} else {
				echo "<li>" . _L("No Templates") . "</li>";
			}
			?>
			</ul>
			<?= icon_button(_L("Add Template"), "add",false,"jobtemplate.php?id=new") ?>
			
		</div>
	
		<div class="help">
			<h3>Need Help?</h3>
			<p>Visit the <a href="#" onclick="popup('help/index.php',750,500);">help section</a> or call (800) 920-3897. Also be sure to <a href="mailto:<?=$_SESSION['_supportemail']?>">give us feedback</a> about the new version.</p>
		</div>
	</div><!-- end main_aside -->
	
	</div><!-- end wrapper -->


<script type="text/javascript">
<?
$who = isset($_GET["showactivity"])?$_GET["showactivity"]:"me";
?>


function updateTableTools(section, action, override, start, limit, count){
	// Remove previous tooltips if they exist add add new ones
	$$('.jobtools').each(function(element) {
		Tips.remove(element.id);
		element.tip = new Tip(element.id, element.next().innerHTML, {
			style: 'protogrey',
			radius: 4,
			border: 4,
			hideOn: false,
			hideAfter: 0.5,
			stem: 'rightTop',
			hook: {  target: 'leftMiddle', tip: 'topRight'  },
			width: 'auto',
			offset: { x: 0, y: 0 }
		});
	});
	
	//Update More link to with the correct show status and url
	if (count >= limit) {
		if (override) {
			start = 0;
			limit += limit;
		} else {
			start += limit;
		}
		
		$("more" + section).update(new Element("a",{href: "#", 
			onclick: "ajax_obj_table_update('" + section + "','ajaxjob.php?action=" + action + "&who=<?=$who?>&start=" + start + "&limit=" + limit + "'," + override + ",updateTableTools.curry('" + section + "','" + action + "'," + override + "," + start + "," + limit + ")); return false;"
			}).insert("<?= _L("Show More")?>"));
	} else {
		$("more" + section).update("");
	}
}

document.observe('dom:loaded', function() {
	ajax_obj_table_update('activejobs','ajaxjob.php?action=activejobs&who=<?=$who?>&start=0&limit=10',true,updateTableTools.curry("activejobs","activejobs",true,0,10));
	ajax_obj_table_update('scheduledjobs','ajaxjob.php?action=scheduledjobs&who=<?=$who?>&start=0&limit=10',false,updateTableTools.curry("scheduledjobs","scheduledjobs",false,0,10));
	ajax_obj_table_update('completedjobs','ajaxjob.php?action=completedjobs&who=<?=$who?>&start=0&limit=5',false,updateTableTools.curry("completedjobs","completedjobs",false,0,5));
});

</script>


<?
include("navbottom.inc.php");
?>
