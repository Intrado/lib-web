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
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE = "notifications:replies";
$TITLE = "Replies";

include_once("nav.inc.php");

$data = DBFindMany("Job", "from job
						where userid = '$USER->id'
						and id in (select distinct vr.jobid from voicereply vr left join
										(select jobid, count(*) as count from voicereply  where listened = '0'
										group by jobid) as foo
										on (foo.jobid = vr.jobid)
										where foo.count > 0)
						group by id
						order by finishdate desc
						");

$titles = array(	"name" => "#Name",
					"description" => "#Description",
					"startdate" => "#Start date",
					"enddate" => "#End Date",
					"Status" => "#Status",
					"Actions" => "Actions"
					);
$formatters = array("name" => "fmt_replies_jobname",
					"Actions" => "fmt_replies_actions",
					'Status' => 'fmt_status',
					"startdate" => "fmt_job_startdate",
					"enddate" => "fmt_job_enddate");

startWindow('Unread Replies to Jobs', 'padding: 3px;', true, true);
$scrollThreshold = 8;
$scroll = false;
if (count($data) > $scrollThreshold) {
	$scroll = true;
}

showObjects($data, $titles, $formatters, $scroll, true);
endWindow();
echo "<br><br>";


$data = DBFindMany("Job", "from job
						where userid = '$USER->id'
						and id in (select distinct jobid from voicereply where jobid not in (select jobid from
										(select jobid, count(*) as count from voicereply where listened = '0'
										group by jobid) as foo
										where foo.count > 0))
						group by id
						order by finishdate desc, status asc
						");

$titles = array(	"name" => "#Name",
					"description" => "#Description",
					"startdate" => "#Start date",
					"enddate" => "#End Date",
					"Status" => "#Status",
					"Actions" => "Actions"
					);
$formatters = array("name" => "fmt_replies_jobname",
					"Actions" => "fmt_replies_actions",
					'Status' => 'fmt_status',
					"startdate" => "fmt_job_startdate",
					"enddate" => "fmt_job_enddate");

startWindow('Read Replies to Jobs', 'padding: 3px;', true, true);
$scrollThreshold = 8;
$scroll = false;
if (count($data) > $scrollThreshold) {
	$scroll = true;
}

showObjects($data, $titles, $formatters, $scroll, true);

endWindow();

echo "<br>";
print buttons(button("showall", "", "repliesjob.php?all=1"));

include_once("navbottom.inc.php");

?>