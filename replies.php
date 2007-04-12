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
include_once("inc/form.inc.php");
include_once("inc/html.inc.php");
require_once("inc/table.inc.php");
require_once("inc/utils.inc.php");
require_once("inc/securityhelper.inc.php");
require_once("inc/formatters.inc.php");



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
		$content = new Content($vr->contentid);
		$content->destroy();
		$vr->destroy();
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
	
	foreach($voicereplies as $voicereply){
		$content = new Content($voicereply->contentid);
		$content->destroy();
		$voicereply->destroy();
	}
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
	$play = '<a href="" onclick="repliesplay('. $row[8] .'); return false;">Play</a>';
	$delete = '<a href="replies.php?delete=' . $row[8]. '" onclick="return confirm(\'Are you sure you want to delete this reply?\');">Delete</a>';
	$buttons = array($play, $delete);
	return implode("&nbsp;|&nbsp;", $buttons);
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
	$TITLE = "Responses to: " . $job->name;
	$warning = "Are you sure you want to delete all played messages for: " . $job->name . "?";
} else {
	$TITLE = "Responses to: All Jobs";
	$warning = "Are you sure you want to delete all played messages for all jobs?";
}
include_once("nav.inc.php");

NewForm($f);
	
buttons(button('refresh',"window.location.reload()"), 
		button('delete_all_played', "return confirm('$warning')", "replies.php?deleteplayed=true"));
	
startWindow("Display Options", "padding: 3px;");	
?>

<table border="0" cellpadding="3" cellspacing="0" width="100%">
<tr>
	<th align="right" class="windowRowHeader" valign="top" style="padding-top: 6px;">Job:</th>
	<td>
<?
	NewFormItem($f, $s, 'jobselect', 'selectstart', NULL, NULL, "onchange=\"location.href='?jobid=' + this.value\"");
	NewFormItem($f, $s, 'jobselect', 'selectoption', ' - All - ', "all");
	$jobs = QuickQueryList("select j.id, j.name from job j where j.id in 
						(select jobid from voicereply where userid = '$USER->id' group by jobid)", true);
	foreach ($jobs as $id => $name) {
		NewFormItem($f, $s, 'jobselect', 'selectoption', $name, $id);
	}
?>
	NewFormItem($f, $s, 'jobselect', 'selectend');
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
echo "<br>";
startWindow("Responses", "padding: 3px;");

?>
<?

$firstname = FieldMap::getFirstNameField();
$lastname = FieldMap::getLastNameField();
$detailedquery = "select SQL_CALC_FOUND_ROWS
			p.pkey, pd.$firstname, pd.$lastname, jt.phone, coalesce(m.name, s.name), j.name, vr.replytime, vr.contentid, vr.id,
			vr.listened, j.type
			from voicereply vr 
			inner join job j on (vr.jobid = j.id)
			join jobtask jt on (jt.id = vr.jobtaskid)
			join persondata pd on (pd.personid = vr.personid)
			join person p on (p.id = vr.personid)
			join jobworkitem wi on (vr.jobworkitemid = wi.id)
			left join message m on (m.id = wi.messageid)
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
					"0" => "ID",
					"1" => "Firstname",
					"2" => "Lastname",
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
	
		popup('repliespreview.php?id=' + voicereplyid + '?close=1', 400, 500);
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