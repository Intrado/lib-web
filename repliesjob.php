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
		redirect();
	}
}

if(isset($_GET['jobid'])){
	if($_GET['jobid'] == 'all'){
		unset($_SESSION['replies']['jobid']);
	} else {
		$jobid = $_GET['jobid']+0;
		if(!userOwns("job", $jobid)){
			redirect('unauthorized.php');
		}
		$_SESSION['replies']['jobid'] = $jobid;
	}
	redirect();
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

if(isset($_GET['deleteall']) && $_GET['deleteall']){
	$deleteheard = "";
} else if(isset($_GET['deleteheard']) && $_GET['deleteheard']){
	$deleteheard = "and listened = '1'";
}
if(isset($deleteheard)){
	$voicereplies = DBFindMany("VoiceReply", "from voicereply vr where vr.userid = '$USER->id' 
								$jobidquery
								$deleteheard");
	
	foreach($voicereplies as $voicereply){
		$content = new Content($voicereply->contentid);
		$content->destroy();
		$voicereply->destroy();
	}
}


$f = "replies";
$s = "responses";

ClearFormData($f);
PutFormData($f, $s, "unheard", isset($_SESSION['replies']['showonlyunheard']) ? $_SESSION['replies']['showonlyunheard'] : false , "bool", 0, 1);


////////////////////////////////////////////////////////////////////////////////
// Functions
////////////////////////////////////////////////////////////////////////////////

function fmt_repliesjob_actions($row, $index) {
	$play = '<a href="" onclick="repliesjobplay('. $row[8] .'); return false;">Play</a>';
	$delete = '<a href="repliesjob.php?delete=' . $row[8]. '" onclick="return confirm(\'Are you sure you want to delete this reply?\');">Delete</a>';
	$buttons = array($play, $delete);
	return implode("&nbsp;|&nbsp;", $buttons);
}

function fmt_repliesjob_heard($row, $index){
	if(!$row[$index]){
		return "<div id=reply" . $row[8] . " style='font-weight:bold'>Unheard</div>";
	} else {
		return "<div id=reply" . $row[8] . ">Heard</div>";
	}
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE = "notifications:replies";
if(isset($job)){
	$TITLE = "Replies to - " . $job->name;
} else {
	$TITLE = "Replies to - All Jobs";
}
include_once("nav.inc.php");

NewForm($f);

buttons(button('refresh',"window.location.reload()"), button('delete_heard', "return confirm('Are you sure you want to delete all heard messages?')", "repliesjob.php?deleteheard=true"),
	button('delete_all', "return confirm('Are you sure you want to delete all messages?')", "repliesjob.php?deleteall=true"),
	button('done',"","replies.php"));
startWindow("My Replies", 'padding: 3px;');

?>
<div> Show only unheard replies <?NewFormItem($f, $s, "unheard", "checkbox", null, null, "onclick=\"window.location='repliesjob.php?showonlyunheard=' + (this.checked ? 'true' : 'false') + '&pagestart=$pagestart';\"");?></div>
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
					"Actions" => "fmt_repliesjob_actions",
					"6" => "fmt_ms_timestamp",
					"3" => "fmt_phone",
					"9" => "fmt_repliesjob_heard"
					);
if(!isset($_SESSION['replies']['jobid']))
	unset($titles[5]);

$query = "select found_rows()";
$total = QuickQuery($query);

showPageMenu($total,$pagestart,500);
echo "\n";
echo '<table width="100%" cellpadding="3" cellspacing="1" class="list">';

showTable($data, $titles, $formatters);
echo "\n</table>";
showPageMenu($total,$pagestart,500);

EndForm();
endWindow();
buttons();

////////////////////////////////////////////////////////////////////////////////
// Scripts
////////////////////////////////////////////////////////////////////////////////
?>
<script>
	function repliesjobplay( voicereplyid ){
	
		popup('repliespreview.php?id=' + voicereplyid + '?close=1', 400, 500);
		var dummy = new getObj('reply' + voicereplyid).obj;
		dummy.style.fontWeight='normal';
		if(document.all){
			dummy.innerText= 'Heard';
		} else {
			dummy.textContent='Heard';
		}
	}
</script>

<?
include("navbottom.inc.php");
?>