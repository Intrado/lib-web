<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("inc/common.inc.php");
require_once("inc/date.inc.php");
require_once("inc/securityhelper.inc.php");
require_once("obj/Job.obj.php");
require_once("obj/JobType.obj.php");
require_once("inc/table.inc.php");
require_once("inc/html.inc.php");
require_once("inc/form.inc.php");
require_once("obj/PeopleList.obj.php");
require_once("obj/Message.obj.php");
require_once("obj/AudioFile.obj.php");
require_once("obj/FieldMap.obj.php");
require_once("obj/SurveyQuestionnaire.obj.php");
require_once("inc/formatters.inc.php");
require_once("obj/PeopleList.obj.php");
require_once("obj/RenderedList.obj.php");



////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////
function gettingStarted() {
	return array("type" => "systemmessage","id" => '',
								"icon" => "",
								"date" => date("Y-m-d H:i:s", time()),
								"status" => "Getting Started",
								"message" => '
	<table border="0" cellpadding="3" cellspacing="0" width=100%>
		<tr>
			<td NOWRAP align="right" valign="top" class="bottomBorder"><b>Help:&nbsp;&nbsp; </b></td>
			<td class="bottomBorder">Need Assistance? View the quick video guides.
				<ul style="list-style-image: url(img/icons/control_play_blue.png)">
				<li><a href="">Creating a new notification</a>
				<li><a href="">Making a clear message</a>
				</ul>
			</td>
		</tr>
		<tr>
			<td NOWRAP align="right" valign="top"><b>New User:&nbsp;&nbsp; </b></td>
			<td >This printable PDF training guide teaches product basics in a simple step-by-step format.
			<ul style="list-style-image: url(img/icons/page_white_acrobat.png)">
				<li><a href="help/getting_started_online.pdf"> Training Guide</a>
			</ul>
			</td>
		</tr>
	</table>
								'
			);
}

$CURRENTVERSION = "6.2";

if ($USER->authorize("loginweb") === false) {
	redirect('unauthorized');
}

if($USER->authorize("leavemessage")){
	$count = QuickQuery("select count(*) from voicereply where userid = '$USER->id' and listened = '0'");
}

if (isset($_GET['closewhatsnew'])) {
	QuickUpdate("delete from usersetting where userid=$USER->id and name='whatsnewversion'");
	QuickUpdate("insert into usersetting (userid, name, value) values ($USER->id, 'whatsnewversion', $CURRENTVERSION)");
}


function itemcmp($a, $b) {
	if ($a["date"] == $b["date"]) {
        return 0;
    }
    return ($a["date"] > $b["date"]) ? -1 : 1;
}
$filter = "";
if (isset($_GET['filter'])) {
	$filter = $_GET['filter'];
}

$mergeditems = array();


switch ($filter) {
	case "list":
		$mergeditems = array_merge($mergeditems,QuickQueryMultiRow("select 'list' as type,'Saved' as status, id, name, modifydate as date, lastused from list where userid=? and deleted != 1 and modifydate is not null order by modifydate desc limit 10",true,false,array($USER->id)));
		break;
	case "messages":
		$mergeditems = array_merge($mergeditems,QuickQueryMultiRow("select 'message' as type,'Saved' as status,id, name, modifydate as date, type as messagetype, deleted from message where  userid=? and deleted != 1 and modifydate is not null order by modifydate desc limit 10",true,false,array($USER->id)));
		break;
	case "savedjob":
		$mergeditems = array_merge($mergeditems,QuickQueryMultiRow("select 'job' as type,status,id, name, modifydate as date, type as jobtype, deleted from job where userid=? and deleted != 1 and finishdate is null and modifydate is not null and status = 'new' order by modifydate desc limit 10",true,false,array($USER->id)));
		break;
	case "scheduledjob":
		$mergeditems = array_merge($mergeditems,QuickQueryMultiRow("select 'job' as type,status,id, name, modifydate as date, type as jobtype, deleted from job where userid=? and deleted != 1 and finishdate is null and modifydate is not null and status = 'scheduled' order by modifydate desc limit 10",true,false,array($USER->id)));
		break;
	case "activejob":
		$mergeditems = array_merge($mergeditems,QuickQueryMultiRow("select 'job' as type,status,id, name, modifydate as date, type as jobtype, deleted from job where userid=? and deleted != 1 and finishdate is null and modifydate is not null and status in ('processing','procactive','active') order by modifydate desc limit 10",true,false,array($USER->id)));
		break;
	case "cancelledjob":
		$mergeditems = array_merge($mergeditems,QuickQueryMultiRow("select 'job' as type,status,id, name, modifydate as date, type as jobtype, deleted from job where userid=? and deleted != 1 and finishdate is null and modifydate is not null and status in ('cancelled','cancelling') order by modifydate desc limit 10",true,false,array($USER->id)));
		$mergeditems = array_merge($mergeditems,QuickQueryMultiRow("select 'job' as type,status,id, name, finishdate as date,type as jobtype, deleted from job where userid=? and deleted != 1 and finishdate is not null and status in ('cancelled','cancelling') order by finishdate desc limit 10",true,false,array($USER->id)));
		break;
	case "completedjob":
		$mergeditems = array_merge($mergeditems,QuickQueryMultiRow("select 'job' as type,status,id, name, finishdate as date,type as jobtype, deleted from job where userid=? and deleted != 1 and finishdate is not null and status = 'complete' order by finishdate desc limit 10",true,false,array($USER->id)));
		$mergeditems = array_merge($mergeditems,QuickQueryMultiRow("select 'job' as type,status,id, name, modifydate as date, type as jobtype, deleted from job where userid=? and deleted != 1 and finishdate is null and modifydate is not null and status in ('cancelled','cancelling') order by modifydate desc limit 10",true,false,array($USER->id)));
		$mergeditems = array_merge($mergeditems,QuickQueryMultiRow("select 'job' as type,status,id, name, finishdate as date,type as jobtype, deleted from job where userid=? and deleted != 1 and finishdate is not null and status in ('cancelled','cancelling') order by finishdate desc limit 10",true,false,array($USER->id)));
		break;	
	case "savedreports":
		$mergeditems = array_merge($mergeditems, QuickQueryMultiRow("select 'report' as type,'Saved' as status,id, name, modifydate as date from reportsubscription where userid=? and modifydate is not null order by modifydate desc limit 10",true,false,array($USER->id)));
		break;
	case "emailedreports":
		$mergeditems = array_merge($mergeditems, QuickQueryMultiRow("select 'report' as type,'Emailed' as status,id, name, lastrun as date from reportsubscription where userid=? and lastrun is not null order by lastrun desc limit 10",true,false,array($USER->id)));
		break;
	case "systemmessages":
		$mergeditems[] = gettingStarted();	
		$mergeditems = array_merge($mergeditems, QuickQueryMultiRow("select 'systemmessage' as type,'' as status,icon, message, modifydate as date from systemmessages where modifydate is not null order by modifydate desc limit 9",true));
		break;	
	default:
		$mergeditems = array_merge($mergeditems,QuickQueryMultiRow("select 'list' as type,'Saved' as status, id, name, modifydate as date, lastused from list where userid=? and deleted != 1 and modifydate is not null order by modifydate desc limit 10",true,false,array($USER->id)));
		$mergeditems = array_merge($mergeditems,QuickQueryMultiRow("select 'message' as type,'Saved' as status,id, name, modifydate as date, type as messagetype, deleted from message where userid=? and deleted != 1 and modifydate is not null order by modifydate desc limit 10",true,false,array($USER->id)));
		$mergeditems = array_merge($mergeditems,QuickQueryMultiRow("select 'job' as type,status,id, name, modifydate as date, type as jobtype, deleted from job where userid=? and deleted != 1 and finishdate is null and modifydate is not null order by modifydate desc limit 10",true,false,array($USER->id)));
		$mergeditems = array_merge($mergeditems,QuickQueryMultiRow("select 'job' as type,status,id, name, finishdate as date,type as jobtype, deleted from job where userid=? and deleted != 1 and finishdate is not null order by finishdate desc limit 10",true,false,array($USER->id)));
		$mergeditems = array_merge($mergeditems,QuickQueryMultiRow("select 'report' as type,'Saved' as status,id, name, modifydate as date from reportsubscription where userid=? and modifydate is not null order by modifydate desc limit 10",true,false,array($USER->id)));
		$mergeditems = array_merge($mergeditems,QuickQueryMultiRow("select 'report' as type,'Emailed' as status,id, name, lastrun as date from reportsubscription where userid=? and lastrun is not null order by lastrun desc limit 10",true,false,array($USER->id)));
		$mergeditems = array_merge($mergeditems, QuickQueryMultiRow("select 'systemmessage' as type,'' as status,icon, message, modifydate as date from systemmessages where modifydate is not null order by modifydate desc limit 9",true));
		if(QuickQuery("select count(*) from job where status='complete' and userid=?",false,array($USER->id)) == 0) {
			$mergeditems[] = gettingStarted();	
		}
		break;	
} 

uasort($mergeditems, 'itemcmp');






////////////////////////////////////////////////////////////////////////////////
// Display Functions
////////////////////////////////////////////////////////////////////////////////

function fmt_surveyactions ($obj,$name) {

	return '<a href="surveytemplate.php?id=' . $obj->id . '">Edit</a>&nbsp;|&nbsp;'
			. '<a href="survey.php?scheduletemplate=' . $obj->id . '">Schedule</a>&nbsp;|&nbsp;'
			. '<a href="surveys.php?deletetemplate=' . $obj->id . '">Delete</a>';
}

function job_responses ($obj,$name) {
		$played = QuickQuery("Select count(*) from voicereply where jobid = '$obj->id' and listened = '0'");
		$total = QuickQuery("Select count(*) from voicereply where jobid = '$obj->id'");
		if($played > 0)
			return '&nbsp;-<a style="display:inline;font-weight:bold; color: #000;" href="replies.php?jobid=' . $obj->id . '">&nbsp;'. $played . ' Unplayed Response(s)</a>';
		else if($total != 0) {
			return '&nbps;-<a style="display:inline;color: #000;" href="replies.php?jobid=' . $obj->id . '">&nbps' . $total . ' Response(s)</a>';
		}
		
		
		
}
function job_lists ($obj,$name) {
		$lists = array();
		$lists[] = QuickQuery("select listid from job where id=?",false, array($obj->id));
		$lists = array_merge($lists, QuickQueryList("select listid from joblist where jobid = ?",false,false,array($obj->id)));
		$calctotal = 0;
		foreach ($lists as $id) {
			$list = new PeopleList($id);
			$renderedlist = new RenderedList($list);
			$renderedlist->calcStats();
			$calctotal = $calctotal + $renderedlist->total;
		}
		return $calctotal;
}



////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$PAGE = 'start:start';
$TITLE = _L('Welcome %1$s %2$s',
	escapehtml($USER->firstname),
	escapehtml($USER->lastname));

if($USER->authorize("leavemessage")){
	if($count > 0){
		$DESCRIPTION = "<img src=\"img/bug_important.gif\"> You have unplayed responses to your notifications..." .
				"<a href=\"replies.php?jobid=all&showonlyunheard=true\">click to view</a>";
	}
}

include_once("nav.inc.php");

?>
	<link href='css/timeline.css' type='text/css' rel='stylesheet' media='screen'>
	<table border=0 cellpadding=0 cellspacing=5>
	<tr>
		<td>
			<div style="width:250px;top:0px;text-align:center;">
					<img style="cursor:pointer;" src="img/largeicons/newjob.jpg" align="middle" alt="Start a new job" title="Start a new job" 
							onclick="window.location = 'jobwizard.php?new'"
							onmouseover="this.src='img/largeicons/newjob_over.jpg'"
							onmouseout="this.src='img/largeicons/newjob.jpg'" />
			<br />		
					<img style="cursor:pointer;" src="img/largeicons/newemergency.jpg" align="middle" alt="Start a new job" title="Start a new job" 
							onclick="window.location = 'jobwizard.php?new'"
							onmouseover="this.src='img/largeicons/newemergency_over.jpg'"
							onmouseout="this.src='img/largeicons/newemergency.jpg'" />			
			</div>
<?		
			if ($USER->authorize("startstats")) {
?>
			</td>
			<td width="100%" valign="middle">
				<div>
<?
				include_once("inc/timeline.inc.php");
?>
			</td>
			</tr>
			<tr>
			<td colspan="2">
<? 		
			startWindow(_L('Recent Activity'));
			
				$limit = 10;
				$duplicatejob = array(); 
				//style="border: none;border-collapse: collapse;">
				$activityfeed = '<table width="100%">
				<tr>
					<td class="feed" style="width: 180px;vertical-align: top;font-size: 12px;font-weight: bold;" >
					<div class="feedfilter">					
						<a href="start.php?filter=none"><img src="img/largeicons/tiny20x20/globe.jpg">Show&nbsp;All</a><br />
					</div>
					<br />		
					<h1>Filter By:</h1>
					<div class="feedfilter">	
						<a href="start.php?filter=job"><img src="img/largeicons/tiny20x20/ping.jpg">Jobs</a><br />
					</div>
					<div id="jobfilters" class="feedsubfilter">	
							<a href="start.php?filter=scheduledjob"><img src="img/largeicons/tiny20x20/clock.jpg">Scheduled</a><br />	
							<a href="start.php?filter=activejob"><img src="img/largeicons/tiny20x20/ping.jpg">Active</a><br />
							<a href="start.php?filter=completedjob"><img src="img/largeicons/tiny20x20/checkedgreen.jpg">Completed</a><br />
					</div>
					<div class="feedfilter">	
						<a href="start.php?filter=messages"><img src="img/largeicons/tiny20x20/letter.jpg">Messages</a><br />
					</div>
					<div class="feedsubfilter">	
							<a href="start.php?filter=phonemessage"><img src="img/largeicons/tiny20x20/phonehandset.jpg">Phone</a><br />	
							<a href="start.php?filter=emailmessage"><img src="img/largeicons/tiny20x20/email.jpg">Email</a><br />
							<a href="start.php?filter=smsmessage"><img src="img/largeicons/tiny20x20/smschat.jpg">SMS</a><br />
					</div>
					<div class="feedfilter">	
						<a href="start.php?filter=list"><img src="img/largeicons/tiny20x20/addrbook.jpg">Contacts</a><br />
						<a href="start.php?filter=savedreports"><img src="img/largeicons/tiny20x20/savedreport.jpg">Reports</a><br />
						<a href="start.php?filter=systemmessages"><img src="img/largeicons/tiny20x20/news.jpg">System&nbsp;Messages</a><br />
						
					</div>
					
					</td>
					<td width="30px">&nbsp;</td>
					<td class="feed" valign="top" >
						<table>
				
				';	
				
				/*
										
						<a href="start.php?filter=activejob"><img src="img/largeicons/tiny20x20/ping.jpg">Active&nbsp;Jobs</a><br />
						<a href="start.php?filter=completedjob"><img src="img/largeicons/tiny20x20/checkedgreen.jpg">Completed&nbsp;Jobs</a><br />
						<a href="start.php?filter=scheduledjob"><img src="img/largeicons/tiny20x20/clock.jpg">Scheduled&nbsp;Jobs</a><br />
						<a href="start.php?filter=savedreports"><img src="img/largeicons/tiny20x20/savedreport.jpg">Saved&nbsp;Reports</a><br />
						<a href="start.php?filter=systemmessages"><img src="img/largeicons/tiny20x20/news.jpg">System&nbsp;Messages</a><br />
					*/
				//"border="1"
				//style="border: none;border-collapse: collapse;">
				$actionids = array();
				
				if(empty($mergeditems)) {
					$activityfeed .= '<tr><td><h3><img src="img/icons/information.gif" />&nbsp;' . _L("No Recent Items.") . '</h3></td></tr>';
				} else {
					while(!empty($mergeditems) && $limit > 0) {
						$item = array_shift($mergeditems);
						$time = date("M j, g:i a",strtotime($item["date"]));	
						$title = $item["status"];
						$content = "";
						$tools = "";
						$itemid = $item["id"];
						$icon = "";
						$defaultlink = "";
						$defaultonclick = null;
						if($item["type"] == "job" ) {
							if(array_search($itemid,$duplicatejob) !== false) {
								continue;
							} 
							$status = $item["status"];
							if($status == "completed" || $status == "cancelled") {
								//$title = _L("Completed Job");
								$duplicatejob[] = $itemid;
							}
	
							//	$title = _L("Edited Job");
							//else
							//	$title = _L("Submitted Job");
							$content = $time .  '&nbsp;-&nbsp;<b>' .  $item["name"] . '</b>&nbsp;';
							
							
							$job = new Job();
							$job->id = $itemid;
							$job->status = $status;
							$job->deleted = $item["deleted"];
							$job->type = $item["jobtype"];
							
							
							$tools = fmt_jobs_actions ($job,$item["name"],true);
							
							$jobtype = $item["jobtype"] == "survey" ? _L("Survey") : _L("Job");
							switch($status) {
								case "new":
									$title = _L('%1$s Saved',$jobtype);
									$defaultlink = "job.php?id=$itemid";
									$icon = '<img src="img/largeicons/floppy.jpg" />';
									break;
								case "repeating":
									$title = _L('Repeating Job Saved');
									//$tools = action_link(_L("Run Now"),"page_go","jobs.php?runrepeating=$itemid", "return confirm('Are you sure you want to run this job now?');");						
									$icon = '<img src="img/largeicons/calendar.jpg" />';
									$defaultlink = "jobrepeating.php?id=$itemid";					
									break;
								case "complete":
									$title = _L('%1$s Completed Successfully',$jobtype);
									$icon = '<img src="img/largeicons/' . ($item["jobtype"]=="survey"?"checklist.jpg":"checkedgreen.jpg") .  '">';
									$defaultlink = $item["jobtype"] == "survey" ? "reportsurveysummary.php?jobid=$itemid" : "reportjobsummary.php?jobid=$itemid";									

									break;
								case "cancelled":
									$title = _L('%1$s Cancelled',$jobtype);
									$icon = '<img src="img/largeicons/checkedgreen.jpg" />';
									$defaultlink = $item["jobtype"] == "survey" ? "reportsurveysummary.php?jobid=$itemid" : "reportjobsummary.php?jobid=$itemid";
									break;
								case "active":
									$title = _L('%1$s Submitted, Status: Active',$jobtype);
									$icon = '<img src="img/largeicons/ping.jpg" />';
									$defaultlink = "#";
									$defaultonclick = "popup('jobmonitor.php?jobid=$id', 500, 450);";
									break;
								case "scheduled":
									$title = _L('%1$s Submitted, Status: Scheduled',$jobtype);
									$icon = '<img src="img/largeicons/clock.jpg" />';
									$defaultlink = "job.php?id=$itemid";
									break;
								case "procactive":
									$title = _L('%1$s Submitted, Status: %2$s',$jobtype,escapehtml(fmt_status($job,$item["name"])));
							//		$title = _L('%1$s Submitted, Status: %1$s',$jobtype,fmt_status($job,$item["name"]));
									$icon = '<img src="img/largeicons/gear.jpg" />';
									$defaultlink = "job.php?id=$itemid";
									break;								
								default:
									$title = _L('Job %1$s',escapehtml(fmt_status($job,$item["name"])));
									break;
							}
							//$title .= job_responses($job,Null);
							
							$jobtypes = explode(",",$item["jobtype"]);
							$content .= '<div style="margin-right:10px;margin-top:10px;">';
							$typelength = count($jobtypes) - 1;
							$typecount = 1;
							foreach($jobtypes as $jobtype) {
								if($jobtype == "sms")
									$alt = strtoupper($jobtype);
								else
									$alt = escapehtml(ucfirst($jobtype));
									
								if($typecount == $typelength)
									$content .= $alt . "&nbsp;and&nbsp;";
								else if($typecount > $typelength)
									$content .= $alt . "&nbsp;";
								else
									$content .= $alt . ",&nbsp;";
								$typecount++;
								//$content .= '<img height="20px" src="img/themes/' . getBrandTheme() . '/icon_' . $jobtype . '.gif".gif" alt="'. $alt .' " title="'. $alt .'" />';
							}
							$contacts = job_lists($job,Null);
							
							$content .= "message&nbsp;with&nbsp;" . ($contacts!=1?$contacts . "&nbsp;contacts":"one contact");
							$content .= job_responses($job,Null);
							$content .= '</div>';
							
							
						} else if($item["type"] == "list" ) {
							$title = "List " . $title;
							$content = $time .  ' - <b>' .  $item["name"];
							if(isset($item["lastused"]))
								$content .= ' (Last used: ' . date("M j, g:i a",strtotime($item["lastused"]));
							else
								$content .= ' (Never used';
							$content .= ')</b>';
							$defaultlink = "list.php?id=$itemid";
							$tools = action_links (action_link("Edit", "pencil", "list.php?id=$itemid"),action_link("Preview", "application_view_list", "showlist.php?id=$itemid"));
							$icon = '<img src="img/largeicons/addrbook.jpg">';			
						} else if($item["type"] == "message" ) {
							$messagetype = $item["messagetype"];
							$title = _L('%1$s message %2$s',escapehtml(ucfirst($messagetype)),escapehtml($title));
							$content = $time .  ' - <b>' .  $item["name"] . '</b>';
							$tools = action_links (
								action_link("Edit", "pencil", 'message' . $item["messagetype"] . '.php?id=' . $itemid),
								action_link("Play","diagona/16/131",null,"popup('previewmessage.php?close=1&id=$itemid', 400, 500); return false;")
								);	
							$defaultlink = "message$messagetype.php?id=$itemid";
							switch($messagetype) {
								case "phone":
									$icon = '<img src="img/largeicons/phonehandset.jpg">';
									break;
								case "email":
									$icon = '<img src="img/largeicons/email.jpg">';
									break;
								case "sms":
									$icon = '<img src="img/largeicons/smschat.jpg">';
									break;
							}
						} else if($item["type"] == "report" ) {
							$title = "Report " . $title;				
							$content = $time .  ' - ' .  $item["name"];
							$icon = '<img src="img/largeicons/savedreport.jpg">';
							$defaultlink = "reportjobsummary.php?id=$itemid";
						} else if($item["type"] == "systemmessage" ) {
							$content = $item["message"];
							$icon = '<img src="img/largeicons/notepad.jpg">';
						}
						
						
						$defaultonclick = !isset($defaultonclick) ? "" : 'onclick="'. $defaultonclick. '"';
						
						$tdstyle = $limit>1?'class="bottomBorder"':"";
						$activityfeed .= '<tr>	
												<td ' . $tdstyle. ' valign="top" width="60px"><a href="' . $defaultlink . '" ' . $defaultonclick . '>' . $icon . '</a></td>
												<td  ' . $tdstyle. '>
													<div class="feedtitle">
														<a href="' . $defaultlink . '" ' . $defaultonclick . '>	
														' . $title . '</a>
													</div>
													<a href="' . $defaultlink . '" ' . $defaultonclick . '>
														<span>' . $content . '</span>
													</a>
												</td>';
						if($tools) {
							$activityfeed .= '	<td ' . $tdstyle. ' valign="middle">
													<div id="actionlink_'. $itemid .'" style="cursor:pointer" ><img src="img/largeicons/tiny20x20/tools.jpg".gif"/>&nbsp;Tools</div>
													<div id="actions_'. $itemid .'" style="display:none;">' . $tools  . '</div>
												</td>';
							$actionids[] = "'$itemid'";
						
						}
						$activityfeed .= 	'	</tr>';
						$limit--;
					}
				} 
				
				$activityfeed .= '</table></td></tr>	</table>';
				echo $activityfeed;
			endWindow();
			
			?> <script>
				var actionids = Array(<?= implode(",",$actionids)?>);
				for(i=0;i<<?= count($actionids) ?>;i++){
					var id = actionids[i];
					$('actionlink_' + id).tip = new Tip('actionlink_' + id, $('actions_' + id).innerHTML, {
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
				}
			</script><? 
			}

	?></td></tr></table><?

include_once("navbottom.inc.php");
?>
