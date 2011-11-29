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



require_once('thrift/Thrift.php');
require_once $GLOBALS['THRIFT_ROOT'].'/protocol/TBinaryProtocol.php';
require_once $GLOBALS['THRIFT_ROOT'].'/transport/TSocket.php';
require_once $GLOBALS['THRIFT_ROOT'].'/transport/TBufferedTransport.php';
require_once $GLOBALS['THRIFT_ROOT'].'/transport/TFramedTransport.php';
require_once($GLOBALS['THRIFT_ROOT'].'/packages/commsuite/CommSuite.php');
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
$TITLE = _L('Notification Viewer');

include_once("nav.inc.php");
?>
<script src="script/livepipe/livepipe.js" type="text/javascript"></script>
<script src="script/livepipe/window.js" type="text/javascript"></script>
<script src="script/niftyplayer.js.php" type="text/javascript"></script>
<script src="script/modalwrapper.js" type="text/javascript"></script>
<script type="text/javascript" src="script/getMessageGroupPreviewGrid.js"></script>
<?
PreviewModal::includePreviewScript();
startWindow(_L('Job Settings'));
?>
<table>	
	<?
	
	ViewOnlyItem(_L("Name"),$job->name);
	ViewOnlyItem(_L("Description"),$job->description);
	$jobtypes = JobType::getUserJobTypes();
	ViewOnlyItem(_L("Job Type"),$jobtypes[$job->jobtypeid]->name);
	$selectedlists = QuickQueryList("select l.name from joblist jl left join list l on (l.id = jl.id) where jl.jobid=?", false,false,array($job->id));
	
	ViewOnlyItem(_L("Start Date"),date("m/d/Y", strtotime($job->startdate)));
	ViewOnlyItem(_L("Days to run"),((86400 + strtotime($job->enddate) - strtotime($job->startdate) ) / 86400));
	
	ViewOnlyItem(_L("Lists"),implode("<br/>",$selectedlists));
	ViewOnlyItem(_L("Message"),$messagegroup->name);	
	$msg_grid = '<div id="preview"></div>
					<script type="text/javascript">
						document.observe(\'dom:loaded\', function() {
							getMessageGroupPreviewGrid(' . $messagegroup->id . ', \'preview\', null);
						});
				</script>';
	ViewOnlyItem("",$msg_grid);
	?>
</table>
<? 
$fallbackUrl = "jobs.php";
echo icon_button(_L("Done"),"tick","location.href='" . (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $fallbackUrl) . "'");

endWindow();
include_once("navbottom.inc.php");


