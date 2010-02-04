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
require_once("obj/Person.obj.php");
require_once("obj/RenderedList.obj.php");



////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

$_SESSION['previewfrom'] = 'start.php';

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

$isajax = isset($_GET['ajax']);

$mergeditems = array();
if($isajax === true) {
	
	session_write_close();//WARNING: we don't keep a lock on the session file, any changes to session data are ignored past this point
	
	switch ($filter) {
		case "lists":
			$mergeditems = array_merge($mergeditems,QuickQueryMultiRow("select 'list' as type,'Saved' as status, id, name, modifydate as date, lastused from list where userid=? and deleted = 0 and modifydate is not null order by modifydate desc limit 10",true,false,array($USER->id)));
			break;
		case "messages":
			$mergeditems = array_merge($mergeditems,QuickQueryMultiRow("
			select 'message' as type,'Saved' as status,g.id as id, g.name as name, g.modified as date, g.deleted as deleted,
			 sum(type='phone') as phone, sum(type='email') as email,sum(type='sms') as sms
			from messagegroup g, message m where g.userid=? and g.deleted = 0 and g.modified is not null and m.messagegroupid = g.id
			group by g.id order by g.modified desc limit 10 ",true,false,array($USER->id)));
			break;
		case "jobs":
			$mergeditems = array_merge($mergeditems,QuickQueryMultiRow("select 'job' as type,status,id, name, modifydate as date,'modifydate' as datetype, type as jobtype, deleted from job where userid=? and deleted = 0 and (finishdate is null || status='repeating') and modifydate is not null order by modifydate desc limit 10",true,false,array($USER->id)));
			$mergeditems = array_merge($mergeditems,QuickQueryMultiRow("select 'job' as type,status,id, name, finishdate as date,'finishdate' as datetype, type as jobtype, deleted from job where userid=? and deleted = 0 and status!='repeating' and finishdate is not null order by finishdate desc limit 10",true,false,array($USER->id)));
			break;
		case "savedjobs":
			$mergeditems = array_merge($mergeditems,QuickQueryMultiRow("select 'job' as type,status,id, name, modifydate as date,'modifydate' as datetype, type as jobtype, deleted from job where userid=? and deleted = 0 and finishdate is null and modifydate is not null and status = 'new' order by modifydate desc limit 10",true,false,array($USER->id)));
			break;
		case "repeatingjobs":
			$mergeditems = array_merge($mergeditems,QuickQueryMultiRow("select 'job' as type,status,id, name, modifydate as date,'modifydate' as datetype,type as jobtype, deleted from job where userid=? and deleted = 0 and modifydate is not null and status='repeating' order by modifydate desc limit 10",true,false,array($USER->id)));
			break;
		case "scheduledjobs":
			$mergeditems = array_merge($mergeditems,QuickQueryMultiRow("select 'job' as type,status,id, name, modifydate as date,'modifydate' as datetype, type as jobtype, deleted from job where userid=? and deleted = 0 and finishdate is null and modifydate is not null and status = 'scheduled' order by modifydate desc limit 10",true,false,array($USER->id)));
			break;
		case "activejobs":
			$mergeditems = array_merge($mergeditems,QuickQueryMultiRow("select 'job' as type,status,id, name, modifydate as date,'modifydate' as datetype, type as jobtype,percentprocessed, deleted from job where userid=? and deleted = 0 and finishdate is null and modifydate is not null and status in ('processing','procactive','active') order by modifydate desc limit 10",true,false,array($USER->id)));
			break;
		case "cancelledjobs":
			$mergeditems = array_merge($mergeditems,QuickQueryMultiRow("select 'job' as type,status,id, name, modifydate as date,'modifydate' as datetype, type as jobtype, deleted from job where userid=? and deleted = 0 and finishdate is null and modifydate is not null and status in ('cancelled','cancelling') order by modifydate desc limit 10",true,false,array($USER->id)));
			$mergeditems = array_merge($mergeditems,QuickQueryMultiRow("select 'job' as type,status,id, name, finishdate as date,'finishdate' as datetype,type as jobtype, deleted from job where userid=? and deleted = 0 and finishdate is not null and status in ('cancelled','cancelling') order by finishdate desc limit 10",true,false,array($USER->id)));
			break;
		case "completedjobs":
			$mergeditems = array_merge($mergeditems,QuickQueryMultiRow("select 'job' as type,status,id, name, finishdate as date,'finishdate' as datetype,type as jobtype, deleted from job where userid=? and deleted = 0 and finishdate is not null and status = 'complete' order by finishdate desc limit 10",true,false,array($USER->id)));
			$mergeditems = array_merge($mergeditems,QuickQueryMultiRow("select 'job' as type,status,id, name, modifydate as date,'modifydate' as datetype , type as jobtype, deleted from job where userid=? and deleted = 0 and finishdate is null and modifydate is not null and status in ('cancelled','cancelling') order by modifydate desc limit 10",true,false,array($USER->id)));
			$mergeditems = array_merge($mergeditems,QuickQueryMultiRow("select 'job' as type,status,id, name, finishdate as date,'finishdate' as datetype,type as jobtype, deleted from job where userid=? and deleted = 0 and finishdate is not null and status in ('cancelled','cancelling') order by finishdate desc limit 10",true,false,array($USER->id)));
			break;
		case "savedreports":
			$mergeditems = array_merge($mergeditems, QuickQueryMultiRow("select 'report' as type,'Saved' as status,id, name, modifydate as date from reportsubscription where userid=? and modifydate is not null order by modifydate desc limit 10",true,false,array($USER->id)));
			break;
		case "emailedreports":
			$mergeditems = array_merge($mergeditems, QuickQueryMultiRow("select 'report' as type,'Emailed' as status,id, name, lastrun as date from reportsubscription where userid=? and lastrun is not null order by lastrun desc limit 10",true,false,array($USER->id)));
			break;
		case "systemmessages":
			$mergeditems = array_merge($mergeditems, QuickQueryMultiRow("select 'systemmessage' as type,'' as status,id,icon, message, modifydate as date from systemmessages where modifydate is not null order by modifydate desc limit 10",true));
			break;
		default:
			$mergeditems = array_merge($mergeditems,QuickQueryMultiRow("select 'list' as type,'Saved' as status, id, name, modifydate as date, lastused from list where userid=? and deleted = 0  and modifydate is not null order by modifydate desc limit 10",true,false,array($USER->id)));
			$mergeditems = array_merge($mergeditems,QuickQueryMultiRow("
			select 'message' as type,'Saved' as status,g.id as id, g.name as name, g.modified as date, g.deleted as deleted,
			 sum(type='phone') as phone, sum(type='email') as email,sum(type='sms') as sms
			from messagegroup g, message m where g.userid=? and g.deleted = 0 and g.modified is not null and m.messagegroupid = g.id
			group by g.id order by g.modified desc limit 10 ",true,false,array($USER->id)));

			$mergeditems = array_merge($mergeditems,QuickQueryMultiRow("select 'job' as type,status,id, name, modifydate as date,'modifydate' as datetype, type as jobtype,percentprocessed, deleted from job where userid=? and deleted = 0  and (finishdate is null || status='repeating') and modifydate is not null order by modifydate desc limit 10",true,false,array($USER->id)));
			$mergeditems = array_merge($mergeditems,QuickQueryMultiRow("select 'job' as type,status,id, name, finishdate as date,'finishdate' as datetype,type as jobtype,percentprocessed, deleted from job where userid=? and deleted = 0  and status!='repeating' and finishdate is not null order by finishdate desc limit 10",true,false,array($USER->id)));
			$mergeditems = array_merge($mergeditems,QuickQueryMultiRow("select 'report' as type,'Saved' as status,id, name, modifydate as date from reportsubscription where userid=? and modifydate is not null order by modifydate desc limit 10",true,false,array($USER->id)));
			$mergeditems = array_merge($mergeditems,QuickQueryMultiRow("select 'report' as type,'Emailed' as status,id, name, lastrun as date from reportsubscription where userid=? and lastrun is not null order by lastrun desc limit 10",true,false,array($USER->id)));
			$mergeditems = array_merge($mergeditems, QuickQueryMultiRow("select 'systemmessage' as type,'' as status,id,icon, message, modifydate as date from systemmessages where modifydate is not null order by modifydate desc limit 10",true));
			break;
	}

	uasort($mergeditems, 'itemcmp');
	
	header('Content-Type: application/json');
	$data = activityfeed($mergeditems,true);
	echo json_encode(!empty($data) ? $data : false);
	exit();
}

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
		if(in_array($obj->status,array("active","cancelling"))) {
			$result = QuickQueryRow("select
				sum(rc.type='phone' and rc.result not in ('duplicate', 'blocked')) as total_phone,
            	sum(rc.type='email' and rc.result not in ('duplicate', 'blocked')) as total_email,
            	sum(rc.type='sms' and rc.result not in ('duplicate', 'blocked')) as total_sms,
            	j.type LIKE '%phone%' AS has_phone,
				j.type LIKE '%email%' AS has_email,
				j.type LIKE '%sms%' AS has_sms,
            	sum(rc.result not in ('A', 'M', 'duplicate', 'nocontacts', 'blocked') and rc.type='phone' and rc.numattempts < js.value) as remaining_phone,
            	sum(rc.result not in ('sent', 'duplicate', 'nocontacts') and rc.type='email' and rc.numattempts < 1) as remaining_email,
            	sum(rc.result not in ('sent', 'duplicate', 'nocontacts', 'blocked') and rc.type='sms' and rc.numattempts < 1) as remaining_sms,
            	j.percentprocessed as percentprocessed
				from job j
           		left join reportcontact rc on j.id = rc.jobid
      			left join jobsetting js on (js.jobid = j.id and js.name = 'maxcallattempts')
            	where j.id=? group by j.id",true,false,array($obj->id));
			$content = "";
			if($result["has_phone"] && $result["total_phone"] != 0)
				$content .= $result["total_phone"] . " Phone" . ($result["total_phone"]!=1?"s":"") . " (" .  sprintf("%0.2f",(100*$result["remaining_phone"]/$result["total_phone"])) . "%	Remaining), ";
			if($result["has_email"] && $result["total_email"] != 0)
				$content .= $result["total_email"] . " Email" . ($result["total_email"]!=1?"s":"") . " (" .  sprintf("%0.2f",(100*$result["remaining_email"]/$result["total_email"])) . "%	Remaining), ";
			if($result["has_sms"]  && $result["total_sms"] != 0)
				$content .= $result["total_sms"] . " SMS (" .  sprintf("%0.2f",(100*$result["remaining_sms"]/$result["total_sms"])) . "% Remaining)";
			return trim($content,", ");
		} else if(in_array($obj->status,array("cancelled","complete"))) {
			$content = "";
			$result = Query("select rp.type,
							sum(rp.numcontacts and rp.status != 'duplicate') as total,
							100 * sum(rp.numcontacts and rp.status='success') / (sum(rp.numcontacts and rp.status != 'duplicate') +0.00) as success_rate
							from reportperson rp where rp.jobid = ?	group by rp.jobid, rp.type",false,array($obj->id));
			while ($row = DBGetRow($result)) {
				if($row[0] == "phone")
					$content .= $row[1] . " Phone" . ($row[1]!=1?"s":"") . " (" . sprintf("%0.2f",(isset($row[2]) ? $row[2] : "")) . "% Contacted), ";
				else if($row[0] == "email")
					$content .= $row[1] . " Email" . ($row[1]!=1?"s":"") . " (" . sprintf("%0.2f",(isset($row[2]) ? $row[2] : "")) . "% Contacted), ";
				else if($row[0] == "sms")
					$content .= $row[1] . " SMS (" . sprintf("%0.2f",(isset($row[2]) ? $row[2] : "")) . "% Contacted)";

			}
			return trim($content,", ");
		} else {
			$lists = QuickQueryList("select listid from joblist where jobid = ?",false,false,array($obj->id));

		}
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

function typestring($str) {
		$jobtypes = explode(",",$str);
		$typesstr = "";
		foreach($jobtypes as $jobtype) {
			if($jobtype == "sms")
				$alt = strtoupper($jobtype);
			else
				$alt = escapehtml(ucfirst($jobtype));
			$typesstr .= $alt . ", ";
		}
		$typesstr = trim($typesstr,', ');
		$andpos = strrpos($typesstr,',');
		if($andpos !== false)
			return substr_replace($typesstr," and ",$andpos,1);
		return $typesstr;
}

function activityfeed($mergeditems,$ajax = false) {
	$actioncount = 0;
	$activityfeed = $ajax===true?array():"";
	$limit = 10;
	$duplicatejob = array();

	if($ajax===true) {
		if(empty($mergeditems)) {
				$activityfeed[] = array("itemid" => "",
											"defaultlink" => "",
											"defaultonclick" => "",
											"icon" => "largeicons/information.jpg",
											"title" => _L("No Recent Items."),
											"content" => "",
											"tools" => "");
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
					$job->type = $item["jobtype"];
					$job->status = $status;
					$job->deleted = $item["deleted"];
					$tools = fmt_jobs_actions ($job,$item["name"]);
					$tools = str_replace("&nbsp;|&nbsp;","<br />",$tools);

					$jobtype = $item["jobtype"] == "survey" ? _L("Survey") : _L("Job");
					switch($status) {
						case "new":
							$title = _L('%1$s Saved',$jobtype);
							$defaultlink = "job.php?id=$itemid";
							$icon = 'largeicons/folderandfiles.jpg';
							$jobcontent = typestring($item["jobtype"]) . "&nbsp;message&nbsp;with&nbsp;" . listcontacts($job,"job");
							break;
						case "repeating":
							if($item["datetype"]=="finishdate")
								$title = _L("Running Repeating Job");
							else
								$title = _L('Repeating Job Saved');
							$icon = 'largeicons/calendar.jpg';
							$defaultlink = "jobrepeating.php?id=$itemid";
							$jobcontent = typestring($item["jobtype"]) . "&nbsp;message&nbsp;with&nbsp;" . listcontacts($job,"job");
							break;
						case "complete":
							$title = _L('%1$s Completed Successfully',$jobtype);
							$icon = 'largeicons/' . ($item["jobtype"]=="survey"?"checklist.jpg":"checkedgreen.jpg") .  '"';
							$defaultlink = $item["jobtype"] == "survey" ? "reportsurveysummary.php?jobid=$itemid" : "reportjobsummary.php?jobid=$itemid";
							$jobcontent = listcontacts($job,"job");
							break;
						case "cancelled":
							$title = _L('%1$s Cancelled',$jobtype);
							$icon = 'largeicons/checkedbluegreen.jpg';
							$jobcontent = listcontacts($job,"job");
							$defaultlink = $item["jobtype"] == "survey" ? "reportsurveysummary.php?jobid=$itemid" : "reportjobsummary.php?jobid=$itemid";
							break;
						case "cancelling":
							$title = _L('%1$s Cancelling',$jobtype);
							$icon = 'largeicons/gear.jpg';
							$jobcontent = listcontacts($job,"job");
							$defaultlink = $item["jobtype"] == "survey" ? "reportsurveysummary.php?jobid=$itemid" : "reportjobsummary.php?jobid=$itemid";
							break;
						case "active":
							$title = _L('%1$s Submitted, Status: Active',$jobtype);
							$icon = 'largeicons/ping.jpg';
							$defaultlink = "#";
							$jobcontent = listcontacts($job,"job");
							$defaultonclick = "onclick=\"popup('jobmonitor.php?jobid=$itemid', 650, 450);\"";
							break;
						case "scheduled":
							$title = _L('%1$s Submitted, Status: Scheduled',$jobtype);
							$icon = 'largeicons/clock.jpg';
							$defaultlink = "job.php?id=$itemid";
							$jobcontent = typestring($item["jobtype"]) . "&nbsp;message&nbsp;with&nbsp;" . listcontacts($job,"job");
							break;
						case "procactive" || "processing":
							$job->percentprocessed = $item["percentprocessed"];
							$title = _L('%1$s Submitted, Status: %2$s',$jobtype,escapehtml(fmt_status($job,$item["name"])));
							$icon = 'largeicons/gear.jpg';
							$defaultlink = "job.php?id=$itemid";
							$jobcontent = typestring($item["jobtype"]) . "&nbsp;message&nbsp;with&nbsp;" . listcontacts($job,"job");
							break;
						default:
							$title = _L('Job %1$s',escapehtml(fmt_status($job,$item["name"])));
							break;
					}
					$content = '<a href="' . $defaultlink . '" ' . $defaultonclick . '>' .
											$time .  '&nbsp;-&nbsp;<b>' .  escapehtml($item["name"]) . '</b>&nbsp;';

					$content .= '</a><div style="margin-right:10px;margin-top:10px;">
								<a href="' . $defaultlink . '" ' . $defaultonclick . '>';
					$content .= $jobcontent . '</a>';
					$content .= job_responses($job,Null);
					$content .= '</div>';
				} else if($item["type"] == "list" ) {
					$title = "Contact List " . escapehtml($title);
					$defaultlink = "list.php?id=$itemid";
					$content = '<a href="' . $defaultlink . '">' . $time .  ' - <b>' .   escapehtml($item["name"]) . "</b>";

					$content .= '&nbsp;-&nbsp;';
					if(isset($item["lastused"]))
						$content .= 'This list was last used: <i>' . date("M j, g:i a",strtotime($item["lastused"])) . "</i>";
					else
						$content .= 'This list has never been used ';
					$content .= " and has " . listcontacts($itemid,"list") . '</a>';
					$tools = action_links (action_link("Edit", "pencil", "list.php?id=$itemid"),action_link("Preview", "application_view_list", "showlist.php?id=$itemid"));
					$tools = str_replace("&nbsp;|&nbsp;","<br />",$tools);
					$icon = 'largeicons/addrbook.jpg';
				} else if($item["type"] == "message") {
					$types = $item["phone"] > 0?"," . _L("phone"):"";
					$types .= $item["email"] > 0?"," . _L("email"):"";
					$types .= $item["sms"] > 0?"," . _L("sms"):"";

					$title = _L('Message %1$s with %2$s Content',escapehtml($title),typestring(substr($types,1)));

					$defaultlink = "messagegroup.php?id=$itemid";
					$content = '<a href="' . $defaultlink . '" ' . $defaultonclick . '>' . $time .  ' - <b>' .  escapehtml($item["name"]) . '</b>' . '</a>';

					$icon = 'largeicons/letter.jpg';
					$tools = action_links (action_link("Edit", "pencil", 'messagegroup.php?id=' . $itemid));
				} else if($item["type"] == "report" ) {
					$title = "Report " . escapehtml($title);
					$content = '<a href="' . $defaultlink . '" ' . $defaultonclick . '>' .
									$time .  ' - ' .  escapehtml($item["name"]) . '</a>';
					$icon = 'largeicons/savedreport.jpg';
					$defaultlink = "reportjobsummary.php?id=$itemid";
				} else if($item["type"] == "systemmessage" ) {
					$content = $item["message"];
					$icon = 'largeicons/news.jpg';
				}
				$activityfeed[] = array("itemid" => $itemid,
											"defaultlink" => $defaultlink,
											"defaultonclick" => $defaultonclick,
											"icon" => $icon,
											"title" => $title,
											"content" => $content,
											"tools" => $tools);

				$limit--;
			}
		}
	} else {
		$activityfeed .= '<tr>
									<td valign="top" width="60px"><img src="img/ajax-loader.gif" /></td>
									<td >
											<div class="feedtitle">
												<a href="">
												' . _L("Loading Recent Activity") . '</a>
											</div>
									</td>
									</tr>';
		$activityfeed .= "
				<script>
				var actionids = $actioncount;

				var jobfiltes = Array('none','jobs','savedjobs','scheduledjobs','activejobs','completedjobs','repeatingjobs','messages','phonemessages','emailmessages','smsmessages','lists','savedreports','systemmessages');

				function addfeedtools() {
					for(var id=0;id<actionids;id++){
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
					for(var id=0;id<actionids;id++){
						Tips.remove('actionlink_' + id);
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
									actionids = 0;
									for(i=0;i<size;i++){
										var item = result[i];
										html += '<tr><td valign=\"top\" width=\"60px\"><a href=\"' + item.defaultlink + '\" ' + item.defaultonclick + '><img src=\"img/' + item.icon + '\" /></a></td><td ><div class=\"feedtitle\"><a href=\"' + item.defaultlink + '\" ' + item.defaultonclick + '>' + item.title + '</a></div><span>' + item.content + '</span></td>';
										if(item.tools) {
											html += '<td valign=\"middle\" width=\"100px\"><div id=\"actionlink_' + actionids + '\" style=\"cursor:pointer\" ><img src=\"img/largeicons/tiny20x20/tools.jpg\"/>&nbsp;Tools</div><div id=\"actions_' + actionids + '\" style=\"display:none;\">' + item.tools + '</div></td>';
											actionids++;
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
									size = jobfiltes.length;
									for(i=0;i<size;i++){
										$(jobfiltes[i] + 'filter').setStyle({color: filtercolor, fontWeight: 'normal'});
									}
									$(filter + 'filter').setStyle({
	 									 color: '#000000',
										 fontWeight: 'bold'
									});

								}

							}
						});
				}
				document.observe('dom:loaded', function() {
					applyfilter('none');
				});
				</script>";

	}
	return $activityfeed;
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
				$allowedjobtypes = QuickQueryRow("select sum(jt.systempriority = 1) as Emergency, sum(jt.systempriority != 1) as Other from jobtype jt where jt.deleted = 0 and jt.issurvey = 0",true);
				$jobtypes = QuickQueryList("select jt.systempriority from jobtype jt,userjobtypes ujt where ujt.jobtypeid = jt.id and ujt.userid=? and jt.deleted=0 and jt.issurvey = 0",false,false,array($USER->id));
				$jobtypescount = count($jobtypes);
?>
			<div style="width:170px;top:0px;text-align:center;">
			<?
				$hasnewjob = false;
				if($allowedjobtypes["Other"] > 0) {
					if($jobtypescount === 0)
						$hasnewjob = true;
					else {
						$hasnewjob = (in_array(2,$jobtypes) || in_array(3,$jobtypes));
					}
				}
				if($hasnewjob) {
?>
						<img style="cursor:pointer;" src="img/newjob.jpg" align="middle" alt="Start a new job" title="Create a new notification job"
								onclick="window.location = 'jobwizard.php?new&jobtype=normal'"
								onmouseover="this.src='img/newjob_over.jpg'"
								onmouseout="this.src='img/newjob.jpg'" />
<?

				}

				$hasemergency = false;
				if($allowedjobtypes["Emergency"] > 0) {
					if($jobtypescount === 0)
						$hasemergency = true;
					else {
						$hasemergency = in_array(1,$jobtypes);
					}
				}
				if($hasemergency) {
			 ?>
			<br />
					<img style="cursor:pointer;" src="img/newemergency.jpg" align="middle" alt="Start a new emergency job" title="Create a new emergency notification job"
							onclick="window.location = 'jobwizard.php?new&jobtype=emergency'"
							onmouseover="this.src='img/newemergency_over.jpg'"
							onmouseout="this.src='img/newemergency.jpg'" />
			<? } ?>
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
				<table width="100%" name="recentactivity" style="padding-top: 7px;">
				<tr>
					<td class="feed" style="width: 180px;vertical-align: top;font-size: 12px;" >
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
							<a id="messagesfilter" href="start.php?filter=messages" onclick="applyfilter(\'messages\'); return false;"><img src="img/largeicons/tiny20x20/letter.jpg">Messages</a><br />
							<a id="listsfilter" href="start.php?filter=lists" onclick="applyfilter(\'lists\'); return false;"><img src="img/largeicons/tiny20x20/addrbook.jpg">Lists</a><br />
							<a id="savedreportsfilter" href="start.php?filter=savedreports" onclick="applyfilter(\'savedreports\'); return false;"><img src="img/largeicons/tiny20x20/savedreport.jpg">Reports</a><br />
							<a id="systemmessagesfilter" href="start.php?filter=systemmessages" onclick="applyfilter(\'systemmessages\'); return false;"><img src="img/largeicons/tiny20x20/news.jpg">System&nbsp;Messages</a><br />
						</div>
					</td>
					<td width="10px" style="border-left: 1px dotted gray;" >&nbsp;</td>
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
