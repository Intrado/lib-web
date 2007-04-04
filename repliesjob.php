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

$all = 0;
$nojobs = 1;
$reload = 0;
$extra = "";

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
		$all = QuickQueryList("select id from job where userid = '$USER->id'
			and id in (select distinct jobid from voicereply)
			group by id
			order by finishdate");
		if(count($all) > 0){
			$all = implode(",", $all);
			$_SESSION['replies']['jobids'] = $all;
		} else {
			unset($_SESSION['replies']['jobids']); //reset the jobids
		}
		$_SESSION['replies']['all'] = true;
	} else {
		$_SESSION['replies']['jobids'] = $_GET['jobid']+0;
		$_SESSION['replies']['all'] = false;
	}
	redirect();
}

if(isset($_SESSION['replies']['all']) && $_SESSION['replies']['all'])
	$all = 1;

if(isset($_SESSION['replies']['jobids'])){
	$nojobs=0;
	$jobids = $_SESSION['replies']['jobids'];
	$joblist = explode(",", $jobids);
	foreach($joblist as $jobid){
		if(!userOwns("job", $jobids)){
			redirect('unauthorized.php');
		}
	}
	if(count($joblist) == 1){
		$job = new Job($joblist[0]);
	}
}

if(isset($_GET['showonlyunheard'])){
	if($_GET['showonlyunheard'] == "true") {
		$_SESSION['replies']['showonlyunheard'] = true;
		$extra = "and listened = '0'";
	} else {
		$_SESSION['replies']['showonlyunheard'] = false;
	}
} else {
	if(isset($_SESSION['replies']['showonlyunheard'])){
		if($_SESSION['replies']['showonlyunheard'] == true)
			$extra = "and listened = '0'";
	}
}

if(isset($_GET['deleteall']) && $_GET['deleteall']){
	$deleteextra = "";
	$deleteall=true;
} else if(isset($_GET['deleteheard']) && $_GET['deleteheard']){
	$deleteextra = "and listened = '1'";
}
if(isset($_GET['deleteall']) || isset($_GET['deleteheard'])){
	$voicereplies = DBFindMany("VoiceReply", "from voicereply where jobid in ($jobids) $deleteextra");
	
	foreach($voicereplies as $voicereply){
		$content = new Content($voicereply->contentid);
		$content->destroy();
		$voicereply->destroy();
	}
	if(isset($deleteall))
		redirect("replies.php");
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
if($all)
	$TITLE = "Replies to - All Jobs";
else {
	if($job != null)
		$TITLE = "Replies to - " . $job->name;
	else
		$TITLE = "";
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

if(!$nojobs){
	$firstname = FieldMap::getFirstNameField();
	$lastname = FieldMap::getLastNameField();
	$detailedquery = "select SQL_CALC_FOUND_ROWS
				p.pkey, pd.$firstname, pd.$lastname, jt.phone, coalesce(m.name, s.name), j.name, vr.replytime, vr.contentid, vr.id,
				vr.listened, j.type
				from job j 
				inner join voicereply vr on (vr.jobid = j.id)
				left join jobtask jt on (jt.id = vr.jobtaskid)
				left join persondata pd on (pd.personid = vr.personid)
				left join person p on (p.id = vr.personid)
				left join message m on (m.id = j.phonemessageid)
				left join surveyquestionnaire s on (s.id = j.questionnaireid)
				where 1 
				and j.id in ($jobids)
				$extra
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
	if(!$all)
		unset($titles[5]);
	
	$query = "select found_rows()";
	$total = QuickQuery($query);
	
	showPageMenu($total,$pagestart,500);
	echo "\n";
	echo '<table width="100%" cellpadding="3" cellspacing="1" class="list">';
	
	showTable($data, $titles, $formatters);
	echo "\n</table>";
	showPageMenu($total,$pagestart,500);
} else {
	?><div style="color: red;"> No Replies Found </div>
	<br><br><?
}

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