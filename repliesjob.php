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

if(isset($_GET['all'])){
	$_SESSION['replies']['all'] = true;
	unset($_SESSION['replies']['jobid']);
	redirect();
} else if(isset($_GET['jobid'])){
	$_SESSION['replies']['jobid'] = $_GET['jobid']+0;
	unset($_SESSION['replies']['all']);
	redirect();
}

if(isset($_SESSION['replies']['all'])){
	$all = QuickQueryList("select id from job where userid = '$USER->id'
			and id in (select distinct jobid from voicereply)
			group by id
			order by finishdate");
	$all = "(" . implode(",", $all) . ")";
}else if(isset($_SESSION['replies']['jobid'])){
	$jobid = $_SESSION['replies']['jobid'];
	if(userOwns("job", $jobid))
		$job = new Job($jobid);
	else
		redirect("replies.php");
}



$pagestart = (isset($_GET['pagestart']) ? $_GET['pagestart'] + 0 : 0);

$f = "replies";
$s = "responses";
$reloadform = 0;

if(CheckFormSubmit($f, $s)){
	//check to see if formdata is valid
	if(CheckFormInvalid($f))
	{
		print '<div class="warning">Form was edited in another window, reloading data.</div>';
		$reloadform = 1;
	}
	else
	{
		MergeSectionFormData($f, $s);
		//do check
		if( CheckFormSection($f, $s) )
		{
			print '<div class="warning">There was a problem trying to save your changes. <br> Please verify that all required field information has been entered properly.</div>';
		} else {
			if(GetFormData($f,$s,"unheard")){
				$extra = "and listened = '0'";
			}else {
				$extra = "";
			}
		}
	}
} else {
	$extra = "";
	$reloadform = 1;
}

if($all){
	$jobquery = "and j.id in $all";
	$jobcount = "and jobid in $all";
	$extra2 = "j.status asc, j.finishdate desc,";
} else {
	$jobquery = "and j.id = '$jobid'";
	$jobcount = "and jobid = '$jobid'";
	$extra2 = "";
}

if($reloadform) {
	PutFormData($f, $s, "unheard", 0, "bool", 0, 1);
}


////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE = "notifications:replies";
if($all)
	$TITLE = "Replies to - All Jobs";
else
	if($job != null)
		$TITLE = "Replies to - " . $job->name;
	else
		$TITLE = "";
		
include_once("nav.inc.php");

NewForm($f);

startWindow("My Replies", 'padding: 3px;', true, true);

?><div> Show only unheard replies <?NewFormItem($f, $s, "unheard", "checkbox");?></div><br><?
buttons(submit($f,$s,"submit", "submit"), button('done',"","replies.php"));

$count = QuickQuery("select count(*) from voicereply where 1 $jobcount");

if($count){
	$firstname = FieldMap::getFirstNameField();
	$lastname = FieldMap::getLastNameField();
	$query = "select j.name, p.pkey, pd.$firstname, pd.$lastname, j.phonemessageid, jt.phone, vr.replytime, vr.contentid, vr.id,
				vr.listened
				from job j 
				left join voicereply vr on (vr.jobid = j.id)
				left join jobtask jt on (jt.id = vr.jobtaskid)
				left join persondata pd on (pd.personid = vr.personid)
				left join person p on (p.id = vr.personid)
				where 1 
				$jobquery
				$extra
				
				order by $extra2 vr.replytime desc
				";
	
	$responses = Query($query);
	
	$data = array();
	while($row = DBGetRow($responses)){
		$data[] = $row;
	}
	
	$titles = array(	"0" => "Job Name",
						"1" => "ID",
						"2" => "Firstname",
						"3" => "Lastname",
						"4" => "Message Name",
						"5" => "Phone",
						"6" => "Date",
						"Actions" => "Actions"
						);
	$formatters = array(
						"1" => "fmt_repliesjob_stuid",
						"Actions" => "fmt_repliesjob_actions",
						"6" => "fmt_repliesjob_date",
						"4" => "fmt_replies_msgname",
						"5" => "fmt_phone"
						);
	if(!$all)
		unset($titles[0]);
	
	showPageMenu(count($data),$pagestart,500);
	echo "\n";
	echo '<table width="100%" cellpadding="3" cellspacing="1" class="list">';
	
	showTable($data, $titles, $formatters);
	echo "\n</table>";
	showPageMenu(count($data),$pagestart,500);
} else {
	?><div style="color: red;"> No replies for this job </div>
	<br><br><?
}
buttons();
EndForm();

include("navbottom.inc.php");
?>