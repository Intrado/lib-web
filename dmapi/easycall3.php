<?
include_once("../inc/utils.inc.php");
require_once("../inc/date.inc.php");
include_once("../obj/Job.obj.php");
include_once("../obj/User.obj.php");
include_once("../obj/SpecialTask.obj.php");
include_once("../obj/Message.obj.php");
include_once("../obj/MessagePart.obj.php");
include_once("../obj/AudioFile.obj.php");
include_once("../obj/JobType.obj.php");
include_once("../obj/Access.obj.php");
include_once("../obj/Permission.obj.php");
include_once("../obj/JobLanguage.obj.php");
include_once("../obj/Rule.obj.php");


global $USER, $ACCESS;
$specialtask = new specialtask($_SESSION['specialtaskid']);

if($REQUEST_TYPE == "new"){
		$ERROR .= "Got new when wanted result";
} else if($REQUEST_TYPE == "result"){

	if(($specialtask->getData("origin") == "cisco") && ($specialtask->getData("progress") == "Done")){

		$USER = new User($specialtask->getData('userid'));
		$ACCESS = $_SESSION['access'] = new Access($USER->accessid);
		$messages = array();
		$languages = array();
		$count = $specialtask->getData("count");
		for($i = 0; $i < $count; $i++){
			$messnum = "message" . $i;
			$messages[$i] = $specialtask->getData($messnum);
			$langnum = "language" . $i;
			$languages[$i] = $specialtask->getData($langnum);
		}

		$job = Job::jobWithDefaults();
		//get the job name, type, and messageid

		$name = $specialtask->getData('name');

		if (!$name)
			$name = "EasyCall - " . date("M j, Y g:i a");
		$job->name = $name;
		$job->description = "EasyCall - " . date("M j, Y g:i a");
		$type = $specialtask->getData('jobtypeid');
		$job->jobtypeid = $type;
		$job->type = "phone";
		$numdays = $specialtask->getData('jobdays');
		$job->enddate = date("Y-m-d", strtotime($job->startdate) + (($numdays - 1) * 86400));
		$job->setOptionValue("retry",$specialtask->getData('jobretries'));

		if($messages) {

			foreach($messages as $index => $message){
				if($languages[$index] == "Default"){
					$job->phonemessageid = $message;
					$job->create();
				} else {
					$joblang = new JobLanguage();
					$joblang->type = "phone";
					$joblang->language = $languages[$index];
					$joblang->messageid = $message;
					$joblang->jobid = $job->id;
					if ($joblang->language && $joblang->messageid) {
						$joblang->create();
					}
				}
			}
		}
		if($job->id){
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
						?><audio cmid="file://prompts/inbound/Goodbye.wav" /> <?
					} else {
						?><audio cmid="file://prompts/GoodBye.wav" /> <?
					}
				?>
				<hangup />
			</message>
		</voice>
	<?
}
?>
