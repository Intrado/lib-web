<?
include_once('inc/common.inc.php');
include_once("inc/securityhelper.inc.php");
include_once('inc/html.inc.php');
include_once("inc/form.inc.php");
include_once('inc/table.inc.php');
include_once('obj/SpecialTask.obj.php');
include_once('obj/Message.obj.php');
include_once('obj/MessagePart.obj.php');
include_once('obj/AudioFile.obj.php');
include_once('obj/Phone.obj.php');
include_once("obj/JobType.obj.php");
include_once("obj/PeopleList.obj.php");
include_once("obj/Job.obj.php");
include_once('obj/RenderedList.obj.php');
include_once('obj/FieldMap.obj.php');
include_once('obj/JobLanguage.obj.php');

// AUTHORIZATION //////////////////////////////////////////////////
if (!$USER->authorize("starteasy")) {
	redirect("unauthorized.php");
}

$specialtask = new SpecialTask($_SESSION['easycallid']);
$messages = array();
$languages = array();
$count = $specialtask->getData("count");
for($i = 0; $i < $count; $i++){
	$messnum = "message" . $i;
	$messages[$i] = $specialtask->getData($messnum);
	$langnum = "language" . $i;
	$languages[$i] = $specialtask->getData($langnum);
}

if (!$specialtask->getData('jobid')) {
	$job = Job::jobWithDefaults();
	//get the job name, type, and messageid

	$name = $specialtask->getData('name');

	if (!$name)
		$name = "EasyCall - " . date("M d, Y g:i a");
	$job->name = $name;
	$job->description = "EasyCall - " . date("M d, Y g:i a");
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
	}
	$specialtask->update();
} else {
	$job = new Job($specialtask->getData('jobid'));
}
?>
<script language="javascript">
	window.opener.document.location='jobconfirm.php?id=<?=$job->id?>';
	window.close();
</script>
