<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
include_once("inc/common.inc.php");
include_once("obj/Job.obj.php");
include_once("obj/VoiceReply.obj.php");
include_once("obj/Message.obj.php");
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

if(isset($_GET['delete'])){
	$jobid = $_GET['delete']+0;
	if(userOwns("job", $jobid)){
		$replies = DBFindMany("VoiceReply", "from voicereply where jobid = '$jobid'");
		foreach($replies as $reply){
			$content = new Content($reply->contentid);
			$content->destroy();
			$reply->destroy();
		}
	}
}

////////////////////////////////////////////////////////////////////////////////
// Display Functions
////////////////////////////////////////////////////////////////////////////////

function fmt_replies_actions($obj, $name) {
	$view = '<a href="repliesjob.php?jobid=' . $obj->id . '">View Replies</a>';
	$buttons = array($view);
	return implode("&nbsp;|&nbsp;", $buttons);
}

function fmt_replies_unheard($obj, $name) {
	$count = QuickQuery("Select count(*) from voicereply where listened = '0' and jobid = '$obj->id'");
	$total = QuickQuery("Select count(*) from voicereply where jobid = '$obj->id'");
	if($count > 0)
		return "<div id=" . $obj->id ." style='font-weight:bold'>". $count . "/". $total ."</div>";
	else
		return $count . "/". $total;
}


////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE = "notifications:replies";
$TITLE = "Replies";

include_once("nav.inc.php");
buttons(button("showall", "", "repliesjob.php?jobid=all"));

$data = DBFindMany("Job", "from job
						where userid = '$USER->id'
						and id in (select distinct jobid from voicereply)
						group by id
						order by status asc, finishdate desc
						");

$titles = array(	"name" => "#Name",
					"description" => "#Description",
					"startdate" => "Start date",
					"enddate" => "End Date",
					"Status" => "#Status",
					"Not Played" => "Not Played",
					"Actions" => "Actions"
					);
$formatters = array("Actions" => "fmt_replies_actions",
					'Status' => 'fmt_status',
					"startdate" => "fmt_job_startdate",
					"enddate" => "fmt_job_enddate",
					"Not Played" => "fmt_replies_unheard");

startWindow('Replies to Jobs', 'padding: 3px;');

$scrollThreshold = 20;
$scroll = false;
if (count($data) > $scrollThreshold) {
	$scroll = true;
}

showObjects($data, $titles, $formatters, $scroll, true);
endWindow();
echo "<br>";

buttons();
include_once("navbottom.inc.php");

?>