<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
include_once("inc/common.inc.php");
include_once("obj/Job.obj.php");
include_once("obj/VoiceReply.obj.php");
include_once("obj/Message.obj.php");
include_once("obj/FieldMap.obj.php");
include_once("obj/Content.obj.php");
require_once("obj/Phone.obj.php");
require_once("obj/Person.obj.php");
include_once("inc/form.inc.php");
include_once("inc/html.inc.php");
require_once("inc/table.inc.php");
require_once("inc/utils.inc.php");
require_once("inc/securityhelper.inc.php");
require_once("inc/formatters.inc.php");
require_once("inc/content.inc.php");
require_once("inc/appserver.inc.php");



////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('leavemessage')) {
	redirect('unauthorized.php');
}

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

$jobidquery = "";
$unheard = "";
$reload = 0;

$pagestart = (isset($_GET['pagestart']) ? $_GET['pagestart'] + 0 : 0);

if(isset($_GET['delete'])){
	$delete = $_GET['delete']+0;
	if(userOwns("voicereply", $delete)){
		$vr = new VoiceReply($delete);
		
		contentDelete($vr->contentid);
		Query("BEGIN");
		$vr->destroy();
		Query("COMMIT");
		
		$job = new Job($vr->jobid);
		notice(_L("The response from %1s for the job, %2s, is now deleted.", escapehtml(Person::getFullName($vr->personid)), escapehtml($job->name)));

		$reload=1;
	}
}

if(isset($_GET['reset']) && $_GET['reset']){
	unset($_SESSION['replies']['jobid']);
	unset($_SESSION['replies']['showonlyunheard']);
	$reload=1;
}

if(isset($_GET['jobid'])){
	if($_GET['jobid']=='all') {
		unset($_SESSION['replies']['jobid']);
	} else {
		$jobid = $_GET['jobid']+0;
		if(!userOwns("job", $jobid)){
			redirect('unauthorized.php');
		}
		$_SESSION['replies']['jobid'] = $jobid;
	}
	$reload=1;
}

if(isset($_SESSION['replies']['jobid'])){
	$jobid = $_SESSION['replies']['jobid'];
	$job = new Job($jobid);
	$jobidquery = "and vr.jobid = '$jobid'";
}

if(isset($_GET['showonlyunheard'])){
	if($_GET['showonlyunheard'] == "true") {
		$_SESSION['replies']['showonlyunheard'] = true;
		$unheard = "and listened = '0'";
	} else {
		$_SESSION['replies']['showonlyunheard'] = false;
	}
} else {
	if(isset($_SESSION['replies']['showonlyunheard'])){
		if($_SESSION['replies']['showonlyunheard'] == true)
			$unheard = "and listened = '0'";
	}
}

if(isset($_GET['deleteplayed']) && $_GET['deleteplayed']){
	$voicereplies = DBFindMany("VoiceReply", "from voicereply vr where vr.userid = '$USER->id'
								$jobidquery
								and listened = '1'");
	Query("BEGIN");
		foreach($voicereplies as $voicereply){
			$content = new Content($voicereply->contentid);
			contentDelete($voicereply->contentid);
			$voicereply->destroy();
		}
	Query("COMMIT");
	notice(_L("%s played responses are now deleted.", count($voicereplies)));

	if($jobidquery){
		$count = QuickQuery("select count(*) from voicereply vr where vr.userid = '$USER->id'
							$jobidquery");
		if($count ==0)
			unset($_SESSION['replies']['jobid']);
	}
	$reload=1;
}

if($reload)
	redirect();

$f = "replies";
$s = "responses";

ClearFormData($f);
PutFormData($f, $s, "unheard", isset($_SESSION['replies']['showonlyunheard']) ? $_SESSION['replies']['showonlyunheard'] : false , "bool", 0, 1);
PutFormData($f, $s, "jobselect", isset($_SESSION['replies']['jobid']) ? $_SESSION['replies']['jobid'] : "all");

////////////////////////////////////////////////////////////////////////////////
// Functions
////////////////////////////////////////////////////////////////////////////////

function fmt_replies_actions($row, $index) {
	return action_links(
		action_link(_L("Play"),"control_play","#","repliesplay($row[8]); return false;"),
		action_link(_L("Delete"),"cross","replies.php?delete=$row[8]","return confirm('Are you sure you want to delete this reply?');")
	);
}

function fmt_replies_status($row, $index){
	if(!$row[$index]){
		return "<div id=reply" . $row[8] . " style='font-weight:bold'>Unplayed</div>";
	} else {
		return "<div id=reply" . $row[8] . ">Played</div>";
	}
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE = "notifications:responses";
if(isset($job)){
	$TITLE = "Responses to: " . escapehtml($job->name);
	$warning = "Are you sure you want to delete all played messages for: " . $job->name . "?";
} else {
	$TITLE = "Responses to: All Jobs";
	$warning = "Are you sure you want to delete all played messages for all jobs?";
}
include_once("nav.inc.php");

NewForm($f);

buttons(button('Refresh',"window.location.reload()"),
		button('Delete All Played Responses', "if (confirm('$warning')) {window.location.href='replies.php?deleteplayed=true';}"));

startWindow("Display Options" . help('Replies_DisplayOptions'), "padding: 3px;");
?>

<table class="usagelist">
<tr>
	<th align="right" class="windowRowHeader" valign="top" style="padding-top: 6px;">Job:</th>
	<td>
<?
	NewFormItem($f, $s, 'jobselect', 'selectstart', NULL, NULL, "onchange=\"location.href='?jobid=' + this.value\"");
	NewFormItem($f, $s, 'jobselect', 'selectoption', ' -- All -- ', "all");
	$jobids = QuickQueryList("select vr.jobid from voicereply vr where vr.userid = '$USER->id' group by vr.jobid");
	if(count($jobids) > 0) {
		$jobids = "(" . implode(",",$jobids) . ")";
		$jobs = QuickQueryList("select j.id, j.name from job j where j.id in $jobids", true);
		foreach ($jobs as $id => $name) {
			NewFormItem($f, $s, 'jobselect', 'selectoption', $name, $id);
		}
	}
	NewFormItem($f, $s, 'jobselect', 'selectend');
?>
	</td>
</tr>
<tr>
<th align="right" class="windowRowHeader" valign="top" style="padding-top: 6px;">Unplayed:</th>
	<td>
		<div><?NewFormItem($f, $s, "unheard", "checkbox", null, null, "onclick=\"window.location='replies.php?showonlyunheard=' + (this.checked ? 'true' : 'false') + '&pagestart=$pagestart';\"");?>
		Show only unplayed responses </div>
	</td>
</tr>
</table>

<?
endWindow();


startWindow("Responses"  . help('Replies_Responses'), "padding: 3px;");

?>
<?

$firstname = FieldMap::getFirstNameField();
$lastname = FieldMap::getLastNameField();
$detailedquery = "select SQL_CALC_FOUND_ROWS
			rp.pkey, rp.$firstname, rp.$lastname, rc.phone, coalesce(mg.name, s.name), j.name, vr.replytime, vr.contentid, vr.id,
			vr.listened, j.type
			from voicereply vr
			inner join job j on (vr.jobid = j.id)
			inner join reportperson rp on(vr.personid = rp.personid and vr.jobid = rp.jobid and rp.type ='phone')
			left join reportcontact rc on (rp.personid = rc.personid and rp.jobid = rc.jobid and rp.type = rc.type and rc.sequence = vr.sequence)
			left join messagegroup mg on (mg.id = j.messagegroupid)
			left join surveyquestionnaire s on (s.id = j.questionnaireid)
			where vr.userid = '$USER->id'
			$unheard
			$jobidquery
			order by j.status asc, j.finishdate desc, vr.replytime desc
			limit $pagestart, 500
			";

$responses = Query($detailedquery);

$data = array();
while($row = DBGetRow($responses)){
	$data[] = $row;
}

$titles = array(
					"0" => "ID#",
					"1" => "First Name",
					"2" => "Last Name",
					"3" => "Phone",
					"4" => "Message Name",
					"5" => "Job Name",
					"6" => "Date",
					"9" => "Status",
					"Actions" => "Actions"
					);
$formatters = array(
					"Actions" => "fmt_replies_actions",
					"6" => "fmt_ms_timestamp",
					"3" => "fmt_phone",
					"9" => "fmt_replies_status"
					);

$query = "select found_rows()";
$total = QuickQuery($query);

showPageMenu($total,$pagestart,500);
echo "\n";
echo '<table width="100%" cellpadding="3" cellspacing="1" class="list">';

showTable($data, $titles, $formatters);
echo "\n</table>";
showPageMenu($total,$pagestart,500);


endWindow();
buttons();
EndForm();
////////////////////////////////////////////////////////////////////////////////
// Scripts
////////////////////////////////////////////////////////////////////////////////
?>
<script>
	function repliesplay( voicereplyid ){

		popup('repliespreview.php?id=' + voicereplyid + '&close=1', 450, 600);
		var status = new getObj('reply' + voicereplyid).obj;
		status.style.fontWeight='normal';
		if(document.all){
			status.innerText= 'Played';
		} else {
			status.textContent='Played';
		}
	}
</script>

<?
include("navbottom.inc.php");
?>