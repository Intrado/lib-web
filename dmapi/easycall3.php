<?
include_once("../inc/settings.ini.php");
include_once("../inc/utils.inc.php");
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

$specialtask = new specialtask($SESSIONDATA['specialtaskid']);

if($REQUEST_TYPE == "new"){
	?>
	<error>Easycall3: wanted result, got new </error>
	<?
} else if($REQUEST_TYPE == "result"){

	if($specialtask->getData('origin') == 'cisco'){
	
		$USER = new User($specialtask->getData('userid'));
		$ACCESS = new Access($USER->accessid);
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
			$name = "EasyCall - " . date("M d, Y G:i:s");
		$job->name = $name;
		$job->description = "EasyCall - " . date("M d, Y G:i:s");
		$type = $specialtask->getData('jobtypeid');
		$job->listid = $specialtask->getData('listid');
		$job->jobtypeid = $type;
		$job->sendphone = true;
		$job->type = "phone";
		
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
			$specialtask->setData('jobid', $job->id);
			chdir("../");
			$job->runNow();
		}
		$specialtask->update();
	}

	$SESSIONDATA = null;
	?> <ok /> <?
		

} else {
	?> 
		<voice sessionid="<?= $SESSIONID ?>">
			<message>
				<audio cmid="file://prompts/GoodBye.wav" />
				<hangup />
			</message>
		</voice>
	<?
}
?>