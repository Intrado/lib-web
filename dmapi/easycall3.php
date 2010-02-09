<?
include_once("../inc/utils.inc.php");
require_once("../inc/date.inc.php");
include_once("../obj/Job.obj.php");
include_once("../obj/User.obj.php");
include_once("../obj/SpecialTask.obj.php");
include_once("../obj/MessageGroup.obj.php");
include_once("../obj/Message.obj.php");
include_once("../obj/MessagePart.obj.php");
include_once("../obj/AudioFile.obj.php");
require_once("../obj/JobType.obj.php");
require_once("../obj/Access.obj.php");
require_once("../obj/Permission.obj.php");
require_once("../obj/Rule.obj.php");

global $USER, $ACCESS;
$specialtask = new specialtask($_SESSION['specialtaskid']);

if($REQUEST_TYPE == "new"){
		$ERROR .= "Got new when wanted result";
} else if($REQUEST_TYPE == "result"){

	if(($specialtask->getData("origin") == "cisco") && ($specialtask->getData("progress") == "Done")){

		$USER = new User($specialtask->getData('userid'));
		$ACCESS = $_SESSION['access'] = new Access($USER->accessid);

		$name = $specialtask->getData('name');
		if (!$name)
			$name = "EasyCall - " . date("M j, Y g:i a");

		$job = Job::jobWithDefaults();

		$job->name = $name;
		$job->description = "EasyCall - " . date("M j, Y g:i a");
		$type = $specialtask->getData('jobtypeid');
		$job->jobtypeid = $type;
		$job->type = "notification";
		$job->messagegroupid = $_SESSION['messagegroupid'];
		$numdays = $specialtask->getData('jobdays');
		$job->enddate = date("Y-m-d", strtotime($job->startdate) + (($numdays - 1) * 86400));
		$job->setOptionValue("retry",$specialtask->getData('jobretries'));
		$job->create();
		
		if ($job->id) {
			QuickUpdate("insert into joblist (jobid, listid) values (?,?)", false, array($job->id, $specialtask->getData('listid')));
			$specialtask->setData('jobid', $job->id);
			chdir("../");
			$job->runNow();
		}
		$specialtask->update();
	}

	$_SESSION = array();
	?> <ok /> <?

} else {
	?>
		<voice>
			<message>
				<?
					if($specialtask->getData('origin') == "cisco"){
						?><audio cmid="file://prompts/inbound/Goodbye.wav" /><?
					} else {
						?><audio cmid="file://prompts/inbound/Saved.wav" />
						<audio cmid="file://prompts/GoodBye.wav" /><?
					}
				?>
				<hangup />
			</message>
		</voice>
	<?
}
?>
