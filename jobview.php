<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("inc/common.inc.php");
require_once("inc/table.inc.php");
require_once("inc/securityhelper.inc.php");
require_once("inc/html.inc.php");
require_once("inc/utils.inc.php");
require_once("inc/date.inc.php");
require_once("obj/Form.obj.php");
require_once("obj/FormItem.obj.php");
require_once("obj/Phone.obj.php");
require_once("obj/Message.obj.php");
require_once("obj/Voice.obj.php");
require_once("obj/FieldMap.obj.php");
require_once("obj/AudioFile.obj.php");
require_once("obj/MessagePart.obj.php");
require_once("obj/Content.obj.php");
require_once("obj/Validator.obj.php");
require_once("obj/MessageGroup.obj.php");
require_once("obj/MessageAttachment.obj.php");
require_once("obj/Language.obj.php");
require_once("inc/previewfields.inc.php");
require_once("inc/appserver.inc.php");
require_once("obj/Job.obj.php");
require_once("obj/JobType.obj.php");
require_once("obj/PreviewModal.obj.php");


///////////////////////////////////////////////////////////////////////////////
// Authorization:
///////////////////////////////////////////////////////////////////////////////
// no messagegroup id

if (!isset($_GET['id']))
	redirect('unauthorized.php');

// check if the user can view this message group
if (!userCanSee("job", $_GET['id']))
	redirect("unauthorized.php");


$job = new Job($_GET['id'] + 0);

$messagegroup = new MessageGroup($job->messagegroupid);

PreviewModal::HandleRequestWithId();


////////////////////////////////////////////////////////////////////////////////
// Display Functions
////////////////////////////////////////////////////////////////////////////////

function ViewOnlyItem($label,$content) {
	echo '<tr>
				<th style="text-align: right;padding-right: 15px;">' . $label . '</th>
				<td>' . $content . '</td>
			</tr>';
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$PAGE = "notifications:jobs";
$TITLE = _L('%s Viewer',getJobTitle());

include_once("nav.inc.php");
?>
<script src="script/niftyplayer.js.php" type="text/javascript"></script>
<script type="text/javascript" src="script/getMessageGroupPreviewGrid.js"></script>
<?
PreviewModal::includePreviewScript();
startWindow(_L('%s Settings',getJobTitle()));
?>
<table>	
	<?
	
	ViewOnlyItem(_L("Name"),$job->name);
	ViewOnlyItem(_L("Description"),$job->description);
	$jobtypes = JobType::getUserJobTypes();
	ViewOnlyItem(_L("%s Type",getJobTitle()),$jobtypes[$job->jobtypeid]->name);
	$selectedlists = QuickQueryList("select l.name from joblist jl left join list l on (l.id = jl.listid) where jl.jobid=?", false,false,array($job->id));
	
	ViewOnlyItem(_L("Start Date"),date("m/d/Y", strtotime($job->startdate)));
	ViewOnlyItem(_L("Days to run"),((86400 + strtotime($job->enddate) - strtotime($job->startdate) ) / 86400));
	
	ViewOnlyItem(_L("Lists"),implode("<br/>",$selectedlists));
	ViewOnlyItem(_L("Message"),$messagegroup->name);	
	$msg_grid = '<div id="preview"></div>
					<script type="text/javascript">
						document.observe(\'dom:loaded\', function() {
							getMessageGroupPreviewGrid(' . $messagegroup->id . ', \'preview\', null,\'' . $job->id . '\');
						});
				</script>';
	ViewOnlyItem("",$msg_grid);
	?>
</table>
<?
if (isset($_GET['iframe'])) {
?>
	<br>
	Click <a href="start.php" target="_blank">here</a> to manage jobs in SchoolMessenger</a>
<?
} else {
	if (isset($_SERVER['HTTP_REFERER'])) {
		if (strpos($_SERVER['HTTP_REFERER'],"index.php") === false) {
			$donelink = $_SERVER['HTTP_REFERER'];
		} else {
			$donelink = "start.php";
		}
	} else {
		$donelink = "jobs.php";
	}

	echo icon_button(_L("Done"),"tick",null,$donelink);
}
endWindow();
include_once("navbottom.inc.php");


