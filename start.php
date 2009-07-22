<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("inc/common.inc.php");
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

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

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

$listsdata = DBFindMany("PeopleList"," from list where userid=$USER->id and deleted=0");


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
		$mergeditems = array_merge($mergeditems,QuickQueryMultiRow("select 'job' as type,status,id, name, finishdate as date,type as jobtype, deleted from job where deleted != 1 and finishdate is not null and status in ('cancelled','cancelling') order by finishdate desc limit 10",true,false,array($USER->id)));
		break;
	case "completedjob":
		error_log("completedjob");
		$mergeditems = array_merge($mergeditems,QuickQueryMultiRow("select 'job' as type,status,id, name, finishdate as date,type as jobtype, deleted from job where userid=? and deleted != 1 and finishdate is not null and status = 'complete' order by finishdate desc limit 10",true,false,array($USER->id)));
		break;	
	case "savedreports":
		$mergeditems = array_merge($mergeditems, QuickQueryMultiRow("select 'report' as type,'Saved' as status,id, name, modifydate as date from reportsubscription where userid=? and modifydate is not null order by modifydate desc limit 10",true,array($USER->id)));
		break;
	case "emailedreports":
		$mergeditems = array_merge($mergeditems, QuickQueryMultiRow("select 'report' as type,'Emailed' as status,id, name, lastrun as date from reportsubscription where userid=? and lastrun is not null order by lastrun desc limit 10",true,array($USER->id)));
		break;
	default:
		
		$mergeditems = array_merge($mergeditems,QuickQueryMultiRow("select 'list' as type,'Saved' as status, id, name, modifydate as date, lastused from list where userid=? and deleted != 1 and modifydate is not null order by modifydate desc limit 10",true,false,array($USER->id)));
		$mergeditems = array_merge($mergeditems,QuickQueryMultiRow("select 'message' as type,'Saved' as status,id, name, modifydate as date, type as messagetype, deleted from message where userid=? and deleted != 1 and modifydate is not null order by modifydate desc limit 10",true,false,array($USER->id)));
		$mergeditems = array_merge($mergeditems,QuickQueryMultiRow("select 'job' as type,status,id, name, modifydate as date, type as jobtype, deleted from job where userid=? and deleted != 1 and finishdate is null and modifydate is not null order by modifydate desc limit 10",true,false,array($USER->id)));
		$mergeditems = array_merge($mergeditems,QuickQueryMultiRow("select 'job' as type,status,id, name, finishdate as date,type as jobtype, deleted from job where userid=? and deleted != 1 and finishdate is not null order by finishdate desc limit 10",true,false,array($USER->id)));
		$mergeditems = array_merge($mergeditems,QuickQueryMultiRow("select 'report' as type,'Saved' as status,id, name, modifydate as date from reportsubscription where userid=? and modifydate is not null order by modifydate desc limit 10",true,false,array($USER->id)));
		$mergeditems = array_merge($mergeditems,QuickQueryMultiRow("select 'report' as type,'Emailed' as status,id, name, lastrun as date from reportsubscription where userid=? and lastrun is not null order by lastrun desc limit 10",true,false,array($USER->id)));
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
$TIMELINE = true;

include_once("nav.inc.php");

if ($listsdata) {
?>
	<table border=0 cellpadding=0 cellspacing=5>
	<tr>
		<td valign="top">
			<table width="300px" border=0 cellpadding=0 cellspacing=0>
<?
		  	if ($USER->getSetting("whatsnewversion") != $CURRENTVERSION) {
			?><tr><td><?
			  	$startCustomTitle = _L("What's New");
				startWindow($startCustomTitle,NULL);

?>				<div align="center" style="margin: 5px;">
					<div style="text-align: left; padding: 5px;">
					<p>Update 6.2</p>
					<a href="#" onclick="window.open('help/schoolmessenger_help.htm#getting_started/new_features.htm', '_blank', 'width=750,height=500,location=no,menubar=yes,resizable=yes,scrollbars=yes,status=no,titlebar=no,toolbar=yes');">
					<img src="img/bug_lightbulb.gif" >Click here to see what's new.</a>
					</div>
					<BR>
				</div>

				<table align="center"><tr><td>
<?				echo button(_L('Close'), null, 'start.php?closewhatsnew'); ?>
				</td></tr></table>

<?
				endWindow();
			?><br></td></tr><?
			}
			if ( (( ($USER->authorize("starteasy") && $USER->authorize('sendphone'))
				|| $USER->authorize('sendphone')
				|| $USER->authorize('sendemail')
				|| $USER->authorize('sendprint')
				|| $USER->authorize('sendsms')) && $listsdata)
				|| $USER->authorize('createlist')) {
				$theme = getBrandTheme();
			?><tr><td><?
				startWindow(_L('Quick Start ') . help('Start_EasyCall'),NULL);
				?>
				<table border="0" cellpadding="3" cellspacing="0" width=100%>
				<?
				if ($USER->authorize("starteasy") && $USER->authorize('sendphone')) {
					?>
					<tr>
						<td align="right" valign="center" class="bottomBorder"><div NOWRAP class="destlabel">Basic<?=help('Start_EasyCall', '', 'small')?></div></td>
						<td class="bottomBorder" style="padding: 5px;" valign="center">
							<img src="img/themes/<?=$theme?>/b1_easycall2.gif" onclick="window.location = 'jobwizard.php?new'"
							onmouseover="this.src='img/themes/<?=$theme?>/b2_easycall2.gif'"
							onmouseout="this.src='img/themes/<?=$theme?>/b1_easycall2.gif'">
						</td>
					</tr>
					<?
				}
				if ($USER->authorize('sendphone') || $USER->authorize('sendemail') || $USER->authorize('sendprint') || $USER->authorize('sendsms')) {
					?>
					<tr>
						<td align="right" valign="center" class="bottomBorder"><div NOWRAP class="destlabel">Advanced<?=help('Jobs_AddStandardJob', '', 'small')?></div></td>
						<td class="bottomBorder" style="padding: 5px;" valign="center"><?=button_bar(button(_L('Create New Job'), NULL,"job.php?origin=start&id=new"))?></td>
					</tr>
					<?
				}
				if ($USER->authorize('createlist')) {
					?>
					<tr>
						<td align="right" valign="center"><div NOWRAP class="destlabel">List<?=help('Lists_AddList', '', 'small')?></div></td>
						<td style="padding: 5px;" valign="center"><?=button_bar(button(_L('Create New List'), NULL,"list.php?origin=start&id=new"))?></td>
					</tr>
				<?
				}
				?>
				</table>
				<?
				endWindow();
			?><br></td><?
			}
			?></tr>
			</table><?
			
			
			if ($USER->authorize("startstats")) {
?>
			</td>
			<td width="100%" valign="top">
<?
			startWindow('Job Timeline ',NULL,true);
				button_bar(button('Refresh', 'window.location.reload()'));
				include_once("inc/timeline.inc.php");
			endWindow();
?>
			</td>
			</tr>
			<tr>
			<td colspan="2">
<? 
			
			startWindow(_L('Recent Activity'),NULL,true);
			
				$limit = 5;
				
				$duplicatejob = array(); 
				
				$activityfeed = '<table width="100%" style="border: none;">
				<tr>
					<td rowspan="10" valign="top" width="270px" >
					<a href="start.php?filter=none">Show&nbsp;All</a><br />
					<br />
					Filter By:<br />
					<a href="start.php?filter=activejob">Active&nbsp;Jobs</a><br />
					<a href="start.php?filter=completedjob">Completed&nbsp;Jobs</a><br />
					<a href="start.php?filter=scheduledjob">Scheduled&nbsp;Jobs</a><br />
					<a href="start.php?filter=savedreports">Saved&nbsp;Reports</a><br />
					</td>
					<td rowspan="10" width="50px">&nbsp;</td>
				</tr>
				';	
				
				while(!empty($mergeditems) && $limit > 0) {
					$item = array_shift($mergeditems);
					$time = date("M j, g:i a",strtotime($item["date"]));	
					$title = $item["status"];
					$content = "";
					$actions = "";
					$itemid = $item["id"];
					$icon = "";
					
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

						$content = $time .  ' - ' .  $item["name"];
						
						$job = new Job();
						$job->id = $itemid;
						$job->status = $status;
						$job->deleted = $item["deleted"];
						$job->type = $item["jobtype"];
						
						$actions = fmt_jobs_actions ($job,$item["name"]);
						if($status == "new")
							$itemhead = _L("Saved");
						else
							$itemhead = escapehtml(fmt_status($job,$item["name"]));
						
						$title = _L('Job %1$s',$itemhead);
						$jobtypes = explode(",",$item["jobtype"]);
						$icon = "";
						foreach($jobtypes as $jobtype) {
							$icon .= '<img src="img/themes/' . getBrandTheme() . '/icon_' . $jobtype . '.gif".gif">';
						}
						
						//$icon = '<img src="img/themes/' . getBrandTheme() . '/icon_' . $job->type . '.gif".gif" alt="'.escapehtml($title).'">';
					} else if($item["type"] == "list" ) {
						$title = "List " . $title;
						$content = $time .  ' - ' .  $item["name"];
						if(isset($item["lastused"]))
							$content .= ' - Last used: ' . date("M j, g:i a",strtotime($item["lastused"]));
						else
							$content .= ' - Never used';
						
						$actions = action_links (
						action_link("Edit", "pencil", "list.php?id=$itemid"),action_link("Preview", "application_view_list", "showlist.php?id=$itemid")
							);
						$icon = '<img src="img/icons/application_view_list.gif".gif">';			
					} else if($item["type"] == "message" ) {
						$title = "Message " . $title;
						$content = $time .  ' - ' .  $item["name"];
						$actions = action_links (
							action_link("Edit", "pencil", 'message' . $item["messagetype"] . '.php?id=' . $itemid),
							action_link("Play","diagona/16/131",null,"popup('previewmessage.php?close=1&id=$itemid', 400, 500); return false;")
							);	
						$icon = '<img src="img/icons/application_view_list.gif".gif">';					
					} else if($item["type"] == "report" ) {
						$title = "Report " . $title;				
						$content = $time .  ' - ' .  $item["name"];
						$icon = '<img src="img/icons/application_view_list.gif".gif">';
					} 
					
					$tdstyle = $limit>1?'class="bottomBorder"':"";
					$activityfeed .= '<tr>	
											<td ' . $tdstyle. ' valign="top">' . $icon . '</td>
											<td ' . $tdstyle. ' width="30px">&nbsp;</td>
											<td ' . $tdstyle. ' valign="top">';
					$activityfeed .= 			'<h3>' . $title . '</h3>
												<span>' . $content . '</ spam>';
					$activityfeed .= 		'</td>
											<td ' . $tdstyle. ' valign="top">' . $actions  . '</td></tr>';
					$limit--;
				}
				$activityfeed .= '	</table>';
				echo $activityfeed;
			endWindow();
			
			}


	?></td></tr></table><?

} else {
	if ($USER->getSetting("whatsnewversion") != $CURRENTVERSION) {
		QuickUpdate("delete from usersetting where userid=$USER->id and name='whatsnewversion'");
		QuickUpdate("insert into usersetting (userid, name, value) values ($USER->id, 'whatsnewversion', $CURRENTVERSION)");
	}
?>
	<table border=0 width="500px"><tr><td><?
	startWindow(_L('Getting Started '),NULL);
	?>
	<table border="0" cellpadding="3" cellspacing="0" width=100%>
		<tr>
			<td NOWRAP align="right" valign="center" class="bottomBorder"><div class="destlabel">Help:&nbsp;&nbsp; </div></td>
			<td class="bottomBorder">Need Assistance? Try the comprehensive online help system by clicking the button to the right or by using the Help link in the top right of the page.</td>
			<td class="bottomBorder"><?=button_bar(button(_L('Go To Help'), "window.open('help/index.php', '_blank', 'width=750,height=500,location=no,menubar=yes,resizable=yes,scrollbars=yes,status=no,titlebar=no,toolbar=yes');"))?>
		</tr>
		<tr>
			<td NOWRAP align="right" valign="center" class="bottomBorder"><div class="destlabel">New User:&nbsp;&nbsp; </div></td>
			<td class="bottomBorder">This printable PDF training guide teaches product basics in an simple step-by-step format.</td>
			<td class="bottomBorder"><?=button_bar(button(_L('Training Guide'), NULL, "help/getting_started_online.pdf"))?>
		</tr>
	<?
	if ($USER->authorize('createlist')) {
	?>
		<tr>
			<td NOWRAP align="right" valign="center" class="bottomBorder"><div class="destlabel">List:&nbsp;&nbsp; </div></td>
			<td class="bottomBorder">Ready to start? Before sending a job you'll need to create a list.</td>
			<td class="bottomBorder"><?=button_bar(button(_L('Create New List'), NULL,"list.php?origin=start&id=new"))?>
		</tr>
	<?
	}
	?>
	</table>
	<?
	endWindow();
	?></td></tr></table><?
}

include_once("navbottom.inc.php");
?>
