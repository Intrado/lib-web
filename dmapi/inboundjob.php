<?
// phone inbound, job options and confirmation to submit

include_once("inboundutils.inc.php");
include_once("../inc/utils.inc.php"); // for jobdefaults getSystemSetting()
include_once("../obj/User.obj.php");
include_once("../obj/Access.obj.php");
include_once("../obj/Job.obj.php");
include_once("../obj/JobLanguage.obj.php");
include_once("../obj/JobType.obj.php");
include_once("../obj/Permission.obj.php");

global $SESSIONDATA, $BFXML_VARS;


function jobOptions()
{
	global $SESSIONDATA;
	$maxdays = QuickQuery("SELECT permission.value FROM permission, user WHERE permission.accessid = user.accessid and permission.name='maxjobdays' and user.id=".$SESSIONDATA['userid']);
	glog("maxdays".$maxdays);

	global $SESSIONID;
?>
<voice sessionid="<?= $SESSIONID ?>">
	<message name="joboptions">
		<field name="priority" type="menu" timeout="5000" sticky="true">
			<prompt repeat="2">
				<audio cmid="file://prompts/inbound/SelectPriority.wav" />
			</prompt>

			<choice digits="1" />
			<choice digits="2" />

			<default>
				<audio cmid="file://prompts/ImSorry.wav" />
			</default>
			<timeout>
				<goto message="error" />
			</timeout>
		</field>

<?		if ($maxdays > 1) { ?>
			<sub message="numdays" />
<?		} else { ?>
			<setvar name="numdays" value="1" />
<?		} ?>

	</message>

	<message name="numdays">
		<field name="numdays" type="menu" timeout="5000" sticky="true">
			<prompt repeat="2">
				<audio cmid="file://prompts/inbound/SelectDays.wav" />
				<audio cmid="file://prompts/inbound/<?= $maxdays ?>.wav" />

<?				if ($maxdays > 1) { ?>
					<audio cmid="file://prompts/inbound/DaysPlural.wav" />
<?				} else { ?>
					<audio cmid="file://prompts/inbound/DaySingle.wav" />
<?				} ?>

			</prompt>

<?			for ($i=1; $i<=$maxdays; $i++) { ?>
				<choice digits="<?= $i ?>" />
<?			} ?>

			<default>
				<audio cmid="file://prompts/ImSorry.wav" />
			</default>
			<timeout>
				<goto message="error" />
			</timeout>
		</field>
	</message>

	<message name="error">
		<audio cmid="file://prompts/inbound/Error.wav" />
		<hangup />
	</message>
</voice>
<?
}

function jobConfirm($listname, $priority, $numdays=1)
{
	global $SESSIONID;
?>
<voice sessionid="<?= $SESSIONID ?>">
	<message name="jobconfirm">

		<field name="sendjob" type="menu" timeout="5000" sticky="true">
			<prompt repeat="1">
				<audio cmid="file://prompts/inbound/Confirmation1.wav" />
				<tts gender="female"><?= htmlentities($listname) ?></tts>
				<audio cmid="file://prompts/inbound/Confirmation2.wav" />
				<tts gender="female"><?= htmlentities($priority) ?></tts>
				<audio cmid="file://prompts/inbound/Confirmation3.wav" />
				<audio cmid="file://prompts/inbound/<?= $numdays ?>.wav" />

<?				if ($numdays > 1) { ?>
					<audio cmid="file://prompts/inbound/DaysPlural.wav" />
<?				} else { ?>
					<audio cmid="file://prompts/inbound/DaySingle.wav" />
<?				} ?>

				<audio cmid="file://prompts/inbound/Confirmation4.wav" />

			</prompt>

			<choice digits="1" />
			<choice digits="2" />
			<choice digits="3" />

			<default>
				<audio cmid="file://prompts/ImSorry.wav" />
			</default>
			<timeout>
				<goto message="error" />
			</timeout>
		</field>
	</message>

	<message name="error">
		<audio cmid="file://prompts/inbound/Error.wav" />
		<hangup />
	</message>
</voice>
<?
}

function commitJob()
{
	global $SESSIONDATA;

	$numdays = $SESSIONDATA['numdays'];
	$priority = $SESSIONDATA['priority'];

	$userid = $SESSIONDATA['userid'];

	$now = QuickQuery("select now()");

	global $USER, $ACCESS;

	$USER = new User($SESSIONDATA['userid']);
	$ACCESS = new Access($USER->accessid);

	// now create the job
	$job= Job::jobWithDefaults();

	$job->name = "Call In - ".$now;
	$job->type = "phone";

	$job->createdate = $now;
	$job->startdate = date("Y-m-d", strtotime("today"));
	$job->enddate = date("Y-m-d", strtotime($job->startdate) + (($numdays - 1) * 86400));

	$job->listid = $SESSIONDATA['listid'];
	$job->phonemessageid = $SESSIONDATA['messageid'];

	$VALIDJOBTYPES = JobType::getUserJobTypes();
	foreach ($VALIDJOBTYPES as $t) {
		if ($t->name == $SESSIONDATA['priority']) {
			$job->jobtypeid = $t->id;
			$job->description = $t->name;
			break;
		}
	}
	glog("priority: ".$SESSIONDATA['priority']."   id: ".$job->jobtypeid);

	glog("about to create the job!!!");
	$job->create();
	$jobid = $job->id;
	if ($jobid) {
		// now create any additional language messages for this job
		$msglangmap = $SESSIONDATA['msglangmap'];
		if ($msglangmap) foreach($msglangmap as $lang => $msgid) {
			glog($lang.$msgid);
			$joblang = new JobLanguage();
			$joblang->jobid = $jobid;
			$joblang->messageid = $msgid;
			$joblang->type = "phone";
			$joblang->language = $lang;
			$joblang->create();
			glog("created joblang");
		}

		// now we submit this job
		glog("now submit the job to process ".$jobid);


		chdir("../"); //bph
		$job->runNow(); //bph

		return true;
	}

	return false;
}


//////////////////////////////

	// if they entered the job options
	if (isset($BFXML_VARS['numdays'])) {

			$SESSIONDATA['numdays'] = $BFXML_VARS['numdays'];

			global $USER;
			$USER = new User($SESSIONDATA['userid']);
			$VALIDJOBTYPES = array_values(JobType::getUserJobTypes());

			// if user selected "1" then use highest priority, else use default (aka lowest)
			if ($BFXML_VARS['priority'] == 1) {
				$priority = $VALIDJOBTYPES[0]->name;
			} else {
				$priority = $VALIDJOBTYPES[count($VALIDJOBTYPES)-1]->name;
			}

			$SESSIONDATA['priority'] = $priority; // this is a string, not an int

			$listname = $SESSIONDATA['listname'];
			$numdays = $SESSIONDATA['numdays'];

			jobConfirm($listname, $priority, $numdays);

	// if they listed to confirmation
	} else if (isset($BFXML_VARS['sendjob'])) {

			// send the job
			if ($BFXML_VARS['sendjob'] == "1" &&
				commitJob())
			{
				forwardToPage("inboundgoodbye.php");
			}
			// replay list selection
			else if ($BFXML_VARS['sendjob'] == "2")
			{
				forwardToPage("inboundlist.php");
			}
			// replay job options
			else if ($BFXML_VARS['sendjob'] == "3")
			{
				jobOptions();
			}
	// play the job options
	} else {
		jobOptions();
	}


?>