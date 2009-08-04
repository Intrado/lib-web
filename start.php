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

if ($USER->authorize("loginweb") === false) {
	redirect('unauthorized');
}

if($USER->authorize("leavemessage")){
	$count = QuickQuery("select count(*) from voicereply where userid = '$USER->id' and listened = '0'");
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
	case "lists":
		$mergeditems = array_merge($mergeditems,QuickQueryMultiRow("select 'list' as type,'Saved' as status, id, name, modifydate as date, lastused from list where userid=? and deleted != 1 and modifydate is not null order by modifydate desc limit 10",true,false,array($USER->id)));
		break;
	case "messages":
		$mergeditems = array_merge($mergeditems,QuickQueryMultiRow("select 'message' as type,'Saved' as status,id, name, modifydate as date, type as messagetype, deleted from message where  userid=? and deleted != 1 and modifydate is not null order by modifydate desc limit 10",true,false,array($USER->id)));
		break;
	case "phonemessages":
		$mergeditems = array_merge($mergeditems,QuickQueryMultiRow("select 'message' as type,'Saved' as status,id, name, modifydate as date, type as messagetype, deleted from message where  userid=? and deleted != 1 and modifydate is not null and type='phone' order by modifydate desc limit 10",true,false,array($USER->id)));
		break;
	case "emailmessages":
		$mergeditems = array_merge($mergeditems,QuickQueryMultiRow("select 'message' as type,'Saved' as status,id, name, modifydate as date, type as messagetype, deleted from message where  userid=? and deleted != 1 and modifydate is not null and type='email' order by modifydate desc limit 10",true,false,array($USER->id)));
		break;
	case "smsmessages":
		$mergeditems = array_merge($mergeditems,QuickQueryMultiRow("select 'message' as type,'Saved' as status,id, name, modifydate as date, type as messagetype, deleted from message where  userid=? and deleted != 1 and modifydate is not null and type='sms' order by modifydate desc limit 10",true,false,array($USER->id)));
		break;
	case "jobs":
		$mergeditems = array_merge($mergeditems,QuickQueryMultiRow("select 'job' as type,status,id, name, modifydate as date,'modifydate' as datetype, type as jobtype, deleted from job where userid=? and deleted != 1 and (finishdate is null || status='repeating') and modifydate is not null order by modifydate desc limit 10",true,false,array($USER->id)));
		$mergeditems = array_merge($mergeditems,QuickQueryMultiRow("select 'job' as type,status,id, name, finishdate as date,'finishdate' as datetype, type as jobtype, deleted from job where userid=? and deleted != 1 and status!='repeating' and finishdate is not null order by finishdate desc limit 10",true,false,array($USER->id)));
		break;		
	case "savedjobs":
		$mergeditems = array_merge($mergeditems,QuickQueryMultiRow("select 'job' as type,status,id, name, modifydate as date,'modifydate' as datetype, type as jobtype, deleted from job where userid=? and deleted != 1 and finishdate is null and modifydate is not null and status = 'new' order by modifydate desc limit 10",true,false,array($USER->id)));
		break;
	case "repeatingjobs":
		$mergeditems = array_merge($mergeditems,QuickQueryMultiRow("select 'job' as type,status,id, name, modifydate as date,'modifydate' as datetype,type as jobtype, deleted from job where userid=? and deleted != 1 and modifydate is not null and status='repeating' order by modifydate desc limit 10",true,false,array($USER->id)));
		break;
	case "scheduledjobs":
		$mergeditems = array_merge($mergeditems,QuickQueryMultiRow("select 'job' as type,status,id, name, modifydate as date,'modifydate' as datetype, type as jobtype, deleted from job where userid=? and deleted != 1 and finishdate is null and modifydate is not null and status = 'scheduled' order by modifydate desc limit 10",true,false,array($USER->id)));
		break;
	case "activejobs":
		$mergeditems = array_merge($mergeditems,QuickQueryMultiRow("select 'job' as type,status,id, name, modifydate as date,'modifydate' as datetype, type as jobtype, deleted from job where userid=? and deleted != 1 and finishdate is null and modifydate is not null and status in ('processing','procactive','active') order by modifydate desc limit 10",true,false,array($USER->id)));
		break;
	case "cancelledjobs":
		$mergeditems = array_merge($mergeditems,QuickQueryMultiRow("select 'job' as type,status,id, name, modifydate as date,'modifydate' as datetype, type as jobtype, deleted from job where userid=? and deleted != 1 and finishdate is null and modifydate is not null and status in ('cancelled','cancelling') order by modifydate desc limit 10",true,false,array($USER->id)));
		$mergeditems = array_merge($mergeditems,QuickQueryMultiRow("select 'job' as type,status,id, name, finishdate as date,'finishdate' as datetype,type as jobtype, deleted from job where userid=? and deleted != 1 and finishdate is not null and status in ('cancelled','cancelling') order by finishdate desc limit 10",true,false,array($USER->id)));
		break;
	case "completedjobs":
		$mergeditems = array_merge($mergeditems,QuickQueryMultiRow("select 'job' as type,status,id, name, finishdate as date,'finishdate' as datetype,type as jobtype, deleted from job where userid=? and deleted != 1 and finishdate is not null and status = 'complete' order by finishdate desc limit 10",true,false,array($USER->id)));
		$mergeditems = array_merge($mergeditems,QuickQueryMultiRow("select 'job' as type,status,id, name, modifydate as date,'modifydate' as datetype , type as jobtype, deleted from job where userid=? and deleted != 1 and finishdate is null and modifydate is not null and status in ('cancelled','cancelling') order by modifydate desc limit 10",true,false,array($USER->id)));
		$mergeditems = array_merge($mergeditems,QuickQueryMultiRow("select 'job' as type,status,id, name, finishdate as date,'finishdate' as datetype,type as jobtype, deleted from job where userid=? and deleted != 1 and finishdate is not null and status in ('cancelled','cancelling') order by finishdate desc limit 10",true,false,array($USER->id)));
		break;	
	case "savedreports":
		$mergeditems = array_merge($mergeditems, QuickQueryMultiRow("select 'report' as type,'Saved' as status,id, name, modifydate as date from reportsubscription where userid=? and modifydate is not null order by modifydate desc limit 10",true,false,array($USER->id)));
		break;
	case "emailedreports":
		$mergeditems = array_merge($mergeditems, QuickQueryMultiRow("select 'report' as type,'Emailed' as status,id, name, lastrun as date from reportsubscription where userid=? and lastrun is not null order by lastrun desc limit 10",true,false,array($USER->id)));
		break;
	case "systemmessages":
		$mergeditems = array_merge($mergeditems, QuickQueryMultiRow("select 'systemmessage' as type,'' as status,icon, message, modifydate as date from systemmessages where modifydate is not null order by modifydate desc limit 9",true));
		break;	
	default:
		$mergeditems = array_merge($mergeditems,QuickQueryMultiRow("select 'list' as type,'Saved' as status, id, name, modifydate as date, lastused from list where userid=? and deleted != 1 and modifydate is not null order by modifydate desc limit 10",true,false,array($USER->id)));
		$mergeditems = array_merge($mergeditems,QuickQueryMultiRow("select 'message' as type,'Saved' as status,id, name, modifydate as date, type as messagetype, deleted from message where userid=? and deleted != 1 and modifydate is not null order by modifydate desc limit 10",true,false,array($USER->id)));
		$mergeditems = array_merge($mergeditems,QuickQueryMultiRow("select 'job' as type,status,id, name, modifydate as date,'modifydate' as datetype, type as jobtype, deleted from job where userid=? and deleted != 1 and (finishdate is null || status='repeating') and modifydate is not null order by modifydate desc limit 10",true,false,array($USER->id)));
		$mergeditems = array_merge($mergeditems,QuickQueryMultiRow("select 'job' as type,status,id, name, finishdate as date,'finishdate' as datetype,type as jobtype, deleted from job where userid=? and deleted != 1 and status!='repeating' and finishdate is not null order by finishdate desc limit 10",true,false,array($USER->id)));
		$mergeditems = array_merge($mergeditems,QuickQueryMultiRow("select 'report' as type,'Saved' as status,id, name, modifydate as date from reportsubscription where userid=? and modifydate is not null order by modifydate desc limit 10",true,false,array($USER->id)));
		$mergeditems = array_merge($mergeditems,QuickQueryMultiRow("select 'report' as type,'Emailed' as status,id, name, lastrun as date from reportsubscription where userid=? and lastrun is not null order by lastrun desc limit 10",true,false,array($USER->id)));
		$mergeditems = array_merge($mergeditems, QuickQueryMultiRow("select 'systemmessage' as type,'' as status,icon, message, modifydate as date from systemmessages where modifydate is not null order by modifydate desc limit 9",true));
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
			return '&nbsp;-<a style="display:inline;font-weight:bold; color: #000;" href="replies.php?jobid=' . $obj->id . '">&nbsp;'. $played . '&nbsp;Unplayed&nbsp;Response' . ($played>1?'s':'') . '</a>';
		else if($total != 0) {
			return '&nbsp;-<a style="display:inline;color: #000;" href="replies.php?jobid=' . $obj->id . '">&nbsp;' . $total . '&nbsp;Response' . ($total>1?'s':'') . '</a>';
		}
}
function listcontacts ($obj,$name) {
	$lists = array();
	if($name == "job") {
		if(in_array($obj->status,array("active","cancelling","cancelled","complete"))) {
			$calccont = QuickQuery("select count(distinct personid) from reportperson where jobid=? and status='success'",false,array($obj->id));
			$calctotal = QuickQuery("select count(distinct personid) from reportperson where jobid=?",false,array($obj->id));
			return "<b>" . $calccont . "</b> out of <b>" . $calctotal . ($calctotal!=1?"&nbsp;</b>persons contacted":"</b>&nbsp;person&nbsp;contacted");
		} 
		$lists[] = QuickQuery("select listid from job where id=?",false, array($obj->id));
		$lists = array_merge($lists, QuickQueryList("select listid from joblist where jobid = ?",false,false,array($obj->id)));
	} else if($name == "list") {
		$lists[] = $obj;
	}
	$calctotal = 0;
	foreach ($lists as $id) {
		$list = new PeopleList($id);
		$renderedlist = new RenderedList($list);
		$renderedlist->calcStats();
		$calctotal = $calctotal + $renderedlist->total;
	}
	return "<b>" . $calctotal . ($calctotal!=1?"</b>&nbsp;contacts":"</b>&nbsp;contact");
}

function activityfeed($mergeditems,$ajax = false) {
	$actionids = array();
	$activityfeed = $ajax===true?array():"";
	$limit = 10;
	$duplicatejob = array(); 
	
	if(empty($mergeditems)) {
		if(!$ajax)
			$activityfeed .= '		<tr>
									<td valign="top" width="60px"><img src="img/icons/information.gif" /></td>
									<td >
											<div class="feedtitle">
												<a href="">	
												' . _L("No Recent Items.") . '</a>
											</div>											
									</td>
									</tr>';
		else {
			$activityfeed[] = array("itemid" => "",
										"defaultlink" => "",
										"defaultonclick" => "",
										"icon" => "icons/information.gif",
										"title" => _L("No Recent Items."),
										"content" => "",
										"tools" => "");
		}	
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
			$defaultonclick = "";
			if($item["type"] == "job" ) {
				if(array_search($itemid,$duplicatejob) !== false) {
					continue;
				} 
				$status = $item["status"];
				if($status == "completed" || $status == "cancelled") {
					$duplicatejob[] = $itemid;
				}
				
				$job = new Job();
				$job->id = $itemid;
				$job->status = $status;
				$job->deleted = $item["deleted"];
				$job->type = $item["jobtype"];
				$tools = fmt_jobs_actions ($job,$item["name"]);
				$tools = str_replace("&nbsp;|&nbsp;","<br />",$tools);
				
				$jobtype = $item["jobtype"] == "survey" ? _L("Survey") : _L("Job");
				switch($status) {
					case "new":
						$title = _L('%1$s Saved',$jobtype);
						$defaultlink = "job.php?id=$itemid";
						$icon = 'largeicons/folderandfiles.jpg';
						break;
					case "repeating":
						if($item["datetype"]=="finishdate")
							$title = _L("Running Repeating Job");
						else
							$title = _L('Repeating Job Saved');
						$icon = 'largeicons/calendar.jpg';
						$defaultlink = "jobrepeating.php?id=$itemid";					
						break;
					case "complete":
						$title = _L('%1$s Completed Successfully',$jobtype);
						$icon = 'largeicons/' . ($item["jobtype"]=="survey"?"checklist.jpg":"checkedgreen.jpg") .  '"';
						$defaultlink = $item["jobtype"] == "survey" ? "reportsurveysummary.php?jobid=$itemid" : "reportjobsummary.php?jobid=$itemid";									

						break;
					case "cancelled":
						$title = _L('%1$s Cancelled',$jobtype);
						$icon = 'largeicons/checkedgreen.jpg';
						$defaultlink = $item["jobtype"] == "survey" ? "reportsurveysummary.php?jobid=$itemid" : "reportjobsummary.php?jobid=$itemid";
						break;
					case "cancelling":
						$title = _L('%1$s Cancelling',$jobtype);
						$icon = 'largeicons/gear.jpg';
						$defaultlink = $item["jobtype"] == "survey" ? "reportsurveysummary.php?jobid=$itemid" : "reportjobsummary.php?jobid=$itemid";
						break;
					case "active":
						$title = _L('%1$s Submitted, Status: Active',$jobtype);
						$icon = 'largeicons/ping.jpg';
						$defaultlink = "#";
						$defaultonclick = "onclick=\"popup('jobmonitor.php?jobid=$itemid', 500, 450);\"";
						break;
					case "scheduled":
						$title = _L('%1$s Submitted, Status: Scheduled',$jobtype);
						$icon = 'largeicons/clock.jpg';
						$defaultlink = "job.php?id=$itemid";
						break;
					case "procactive" || "processing":
						$title = _L('%1$s Submitted, Status: %2$s',$jobtype,escapehtml(fmt_status($job,$item["name"])));
						$icon = 'largeicons/gear.jpg';
						$defaultlink = "job.php?id=$itemid";
						break;								
					default:
						$title = _L('Job %1$s',escapehtml(fmt_status($job,$item["name"])));
						break;
				}
				$content = '<a href="' . $defaultlink . '" ' . $defaultonclick . '>' .
										$time .  '&nbsp;-&nbsp;<b>' .  escapehtml($item["name"]) . '</b>&nbsp;';
				
				$jobtypes = explode(",",$item["jobtype"]);
				$content .= '</a><div style="margin-right:10px;margin-top:10px;">
							<a href="' . $defaultlink . '" ' . $defaultonclick . '>';
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
				}				
				$content .= "message&nbsp;with&nbsp;" . listcontacts($job,"job") . '</a>';
				$content .= job_responses($job,Null);
				$content .= '</div>';
			} else if($item["type"] == "list" ) {
				$title = "Contact List " . escapehtml($title);
				$defaultlink = "list.php?id=$itemid";
				$content = '<a href="' . $defaultlink . '">' . $time .  ' - <b>' .  $item["name"] . "</b>";
								
				$content .= '&nbsp;-&nbsp;';
				if(isset($item["lastused"]))
					$content .= 'This list was last used: <i>' . date("M j, g:i a",strtotime($item["lastused"])) . "</i>";
				else
					$content .= 'This list has never been used and ';
				$content .= " and has " . listcontacts($itemid,"list") . '</a>';
				$tools = action_links (action_link("Edit", "pencil", "list.php?id=$itemid"),action_link("Preview", "application_view_list", "showlist.php?id=$itemid"));
				$tools = str_replace("&nbsp;|&nbsp;","<br />",$tools);
				$icon = 'largeicons/addrbook.jpg';			
			} else if($item["type"] == "message" ) {
				$messagetype = $item["messagetype"];
				$title = _L('%1$s message %2$s',escapehtml(ucfirst($messagetype)),escapehtml($title));
				$tools = action_links (
					action_link("Edit", "pencil", 'message' . $item["messagetype"] . '.php?id=' . $itemid),
					action_link("Play","diagona/16/131",null,"popup('previewmessage.php?close=1&id=$itemid', 400, 500); return false;")
					);	
				$defaultlink = "message$messagetype.php?id=$itemid";
				$content = '<a href="' . $defaultlink . '" ' . $defaultonclick . '>' . $time .  ' - <b>' .  escapehtml($item["name"]) . '</b>' . '</a>';
				switch($messagetype) {
					case "phone":
						$icon = 'largeicons/phonehandset.jpg';
						break;
					case "email":
						$icon = 'largeicons/email.jpg';
						break;
					case "sms":
						$icon = 'largeicons/smschat.jpg';
						break;
				}
			} else if($item["type"] == "report" ) {
				$title = "Report " . escapehtml($title);				
				$content = '<a href="' . $defaultlink . '" ' . $defaultonclick . '>' . 
								$time .  ' - ' .  escapehtml($item["name"]) . '</a>';
				$icon = 'largeicons/savedreport.jpg';
				$defaultlink = "reportjobsummary.php?id=$itemid";
			} else if($item["type"] == "systemmessage" ) {
				$content = '<a href="' . $defaultlink . '" ' . $defaultonclick . '>' . $item["message"] . '</a>';
				$icon = 'largeicons/news.jpg';
			}
						
			if($ajax===true) {
				$activityfeed[] = array("itemid" => $itemid,
										"defaultlink" => $defaultlink,
										"defaultonclick" => $defaultonclick,
										"icon" => $icon,
										"title" => $title,
										"content" => $content,
										"tools" => $tools);
			} else {	
				
				$activityfeed .= '<tr>	
										<td valign="top" width="60px"><a href="' . $defaultlink . '" ' . $defaultonclick . '><img src="img/' . $icon . '" /></a></td>
										<td >
											<div class="feedtitle">
												<a href="' . $defaultlink . '" ' . $defaultonclick . '>	
												' . $title . '</a>
											</div>
											<span>' . $content . '</span>
											
										</td>';
				if($tools) {
					$activityfeed .= '	<td valign="middle">
											<div id="actionlink_'. $itemid .'" style="cursor:pointer" ><img src="img/largeicons/tiny20x20/tools.jpg" />&nbsp;Tools</div>
											<div id="actions_'. $itemid .'" style="display:none;">' . $tools  . '</div>
										</td>';
					$actionids[] = "'$itemid'";
				
				}
				$activityfeed .= 	'	</tr>';
			}
			$limit--;
		}
	} 
	if($ajax!==true) {
		$activityfeed .= "
				<script>
				var actionids = Array(" . implode(",",$actionids) . ");
				var jobfiltes = Array('none','jobs','savedjobs','scheduledjobs','activejobs','completedjobs','repeatingjobs','messages','phonemessages','emailmessages','smsmessages','lists','savedreports','systemmessages');
				
				function addfeedtools() {	
					for(i=0;i<actionids.length;i++){
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
				}
				function removefeedtools() {
					for(i=0;i<actionids.length;i++){
						Tips.remove('actionlink_' + actionids[i]);
					}
				}
				function applyfilter(filter) {
						new Ajax.Request('start.php?ajax=true&filter=' + filter, {
							method:'get',
							onSuccess: function (response) {
								var result = response.responseJSON;
								if(result) {
									var html = '';
									var size = result.length;			
									
									removefeedtools();
									actionids = Array();									
									for(i=0;i<size;i++){
										var item = result[i];	
										html += '<tr><td valign=\"top\" width=\"60px\"><a href=\"' + item.defaultlink + '\" ' + item.defaultonclick + '><img src=\"img/' + item.icon + '\" /></a></td><td ><div class=\"feedtitle\"><a href=\"' + item.defaultlink + '\" ' + item.defaultonclick + '>' + item.title + '</a></div><span>' + item.content + '</span></td>';
										if(item.tools) {
											html += '<td valign=\"middle\"><div id=\"actionlink_' + item.itemid + '\" style=\"cursor:pointer\" ><img src=\"img/largeicons/tiny20x20/tools.jpg\"/>&nbsp;Tools</div><div id=\"actions_' + item.itemid + '\" style=\"display:none;\">' + item.tools + '</div></td>';
										}
										if(item.tools != '') {
											actionids.push(item.itemid);											
										}
										html += '</tr>';
									}
									$('feeditems').update(html);
									addfeedtools();
									
									var filtercolor = $('filterby').getStyle('color');
									if(!filtercolor)
										filtercolor = '#000';
										
									if(filter.substring(filter.length-4) != 'jobs')
										$('jobsubfilters').hide();
									if(filter.substring(filter.length-8) != 'messages' && filter != 'systemmessages')
										$('messagessubfilters').hide();
									size = jobfiltes.length;
									for(i=0;i<size;i++){
										$(jobfiltes[i] + 'filter').setStyle({color: filtercolor});	
									}
									$(filter + 'filter').setStyle({
	 									 color: '#005BC3'
									});
									
								}
								
							}
						});
				}
				addfeedtools();				
				</script>";
	}
	return $activityfeed;
}

if (isset($_GET['ajax'])) {
	header('Content-Type: application/json');
	$data = activityfeed($mergeditems,true);
	echo json_encode(!empty($data) ? $data : false);
	exit();
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
<?		
			if ($USER->authorize('sendphone') || $USER->authorize('sendemail') || $USER->authorize('sendsms')) {  
?>
			<div style="width:170px;top:0px;text-align:center;">
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
<?			}
			if ($USER->authorize("startstats")) {
?>
		</td>
		<td width="100%" valign="middle">
<?
	startWindow(_L("Jobs Timeline"));	
		include_once("inc/timeline.inc.php");
	endWindow();
?>
		</td>
	</tr>
	<tr>
	<td colspan="2">
	
<? 		
			startWindow(_L('Recent Activity'));
$activityfeed = '
				<table width="100%" name="recentactivity">
				<tr>
					<td class="feed" style="width: 180px;vertical-align: top;font-size: 12px;font-weight: bold;" >
					<div class="feedfilter">					
						<a id="nonefilter" href="start.php?filter=none" onclick="applyfilter(\'none\'); return false;"><img src="img/largeicons/tiny20x20/globe.jpg">Show&nbsp;All</a><br />
					</div>
					<br />		
					<h1 id="filterby">Filter By:</h1>
					<div id="allfilters" class="feedfilter">	
						<a id="jobsfilter" href="start.php?filter=jobs" onclick="applyfilter(\'jobs\'); $(\'jobsubfilters\').toggle(); return false;"><img src="img/largeicons/tiny20x20/ping.jpg">Jobs</a><br />
						<div id="jobsubfilters" style="' . (in_array($filter,array("savedjobs","scheduledjobs","activejobs","completedjobs","repeatingjobs"))?"display:block":"display:none") . ';padding-left:20px;">	
							<a id="savedjobsfilter" href="start.php?filter=savedjobs" onclick="applyfilter(\'savedjobs\'); return false;"><img src="img/largeicons/tiny20x20/folderandfiles.jpg">Saved</a><br />	
							<a id="scheduledjobsfilter" href="start.php?filter=scheduledjobs" onclick="applyfilter(\'scheduledjobs\'); return false;"><img src="img/largeicons/tiny20x20/clock.jpg">Scheduled</a><br />	
							<a id="activejobsfilter" href="start.php?filter=activejobs" onclick="applyfilter(\'activejobs\'); return false;"><img src="img/largeicons/tiny20x20/ping.jpg">Active</a><br />
							<a id="completedjobsfilter" href="start.php?filter=completedjobs" onclick="applyfilter(\'completedjobs\'); return false;"><img src="img/largeicons/tiny20x20/checkedgreen.jpg">Completed</a><br />
							<a id="repeatingjobsfilter" href="start.php?filter=repeatingjobs" onclick="applyfilter(\'repeatingjobs\'); return false;"><img src="img/largeicons/tiny20x20/calendar.jpg">Repeating</a><br />	
						</div>
						<a id="messagesfilter" href="start.php?filter=messages" onclick="applyfilter(\'messages\');  $(\'messagessubfilters\').toggle(); return false;"><img src="img/largeicons/tiny20x20/letter.jpg">Messages</a><br />
						<div id="messagessubfilters" style="' . (in_array($filter,array("phonemessages","emailmessages","smsmessages"))?"display:block":"display:none") . ';padding-left:20px;">	
							<a id="phonemessagesfilter" href="start.php?filter=phonemessages" onclick="applyfilter(\'phonemessages\'); return false;"><img src="img/largeicons/tiny20x20/phonehandset.jpg">Phone</a><br />	
							<a id="emailmessagesfilter" href="start.php?filter=emailmessages" onclick="applyfilter(\'emailmessages\'); return false;"><img src="img/largeicons/tiny20x20/email.jpg">Email</a><br />
							<a id="smsmessagesfilter" href="start.php?filter=smsmessages" onclick="applyfilter(\'smsmessages\'); return false;"><img src="img/largeicons/tiny20x20/smschat.jpg">SMS</a><br />
						</div>
						<a id="listsfilter" href="start.php?filter=lists" onclick="applyfilter(\'lists\'); return false;"><img src="img/largeicons/tiny20x20/addrbook.jpg">Lists</a><br />
						<a id="savedreportsfilter" href="start.php?filter=savedreports" onclick="applyfilter(\'savedreports\'); return false;"><img src="img/largeicons/tiny20x20/savedreport.jpg">Reports</a><br />
						<a id="systemmessagesfilter" href="start.php?filter=systemmessages" onclick="applyfilter(\'systemmessages\'); return false;"><img src="img/largeicons/tiny20x20/news.jpg">System&nbsp;Messages</a><br />		
					</div>
					</td>
					<td width="30px">&nbsp;</td>
					<td class="feed" valign="top" >
					<table id="feeditems">		
				';	
				
$activityfeed .= activityfeed($mergeditems,false);
$activityfeed .= '</table>
			</td>
		</tr>
	</table>';
			echo $activityfeed;
			endWindow();
			
			?> <? 
			}

	?></td>
	</tr>
</table><?

include_once("navbottom.inc.php");
?>
