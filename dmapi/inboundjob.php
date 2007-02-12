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
	global $SESSIONID, $SESSIONDATA;
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

				<tts gender="female">between the hours of</tts>
				<tts gender="female"><?= $SESSIONDATA['starttime'] ?></tts>
				<tts gender="female">and</tts>
				<tts gender="female"><?= $SESSIONDATA['stoptime'] ?></tts>

				<!-- TODO add info about changing call window into conf4.wav -->
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

function confirmCallWindow()
{
	global $SESSIONID, $SESSIONDATA;

	loadUser();
	global $USER, $ACCESS;

	$SESSIONDATA['starttime'] = $USER->getCallEarly();
	$SESSIONDATA['stoptime'] = $USER->getCallLate();

?>
<voice sessionid="<?= $SESSIONID ?>">
	<message name="confirmcallwindow">
		<field name="usecallwin" type="menu" timeout="5000" sticky="true">
			<prompt repeat="1">
				<tts gender="female">The call window for this job is currently set between the hours of </tts>

				<tts gender="female"><?= $USER->getCallEarly(); ?></tts>

				<tts gender="female"> and </tts>

				<tts gender="female"><?= $USER->getCallLate(); ?></tts>

				<tts gender="female">To accept these settings press 1 to change the call times press 2</tts>

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
	</message>
</voice>
<?
}

function promptStartTime($playinvalid=false, $playmismatch=false)
{
	global $SESSIONID;

	// check user restriction
	loadUser();
	global $USER, $ACCESS;

	glog("access early ".$ACCESS->getValue("callearly"));
	glog("access late  ".$ACCESS->getValue("calllate"));

	$playrestriction = ($ACCESS->getValue("callearly") | $ACCESS->getValue("calllate"));
	glog("playrestrict: ".$playrestriction);
	// if one restricted but the other is not, set default
	$early = $ACCESS->getValue("callearly");
	if (!$early) {
		$early = "12:00am";
	}
	$late = $ACCESS->getValue("calllate");
	if (!$late) {
		$late = "11:59pm";
	}

?>
<voice sessionid="<?= $SESSIONID ?>">
	<message name="promptstarttime">

<?	if ($playinvalid) { ?>
		<tts gender="female">I am sorry but you entered an invalid time </tts>
<?	} ?>

<?	if ($playmismatch) { ?>
		<tts gender="female">The stop time must be after the start time </tts>
<?	} ?>

<?	if ($playrestriction) { ?>
		<tts gender="female">You may enter start and stop times between the following hours </tts>

		<tts gender="female"><?= $early ?></tts>

		<tts gender="female"> and </tts>

		<tts gender="female"><?= $late ?></tts>
<?	} ?>

		<field name="starttime" type="dtmf" timeout="5000" max="20">
			<prompt repeat="2">
				<tts gender="female">Enter the time you want your calls to begin followed by the pound key.  For example to start your calls at 5 in the afternoon enter 5 0 0 pound. </tts>

			</prompt>

			<timeout>
				<goto message="error" />
			</timeout>
		</field>

		<field name="startampm" type="menu" timeout="5000" sticky="true">
			<prompt repeat="1">
				<tts gender="female">press 1 for am or 2 for pm</tts>

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
	</message>

</voice>
<?
}
function promptStopTime()
{
	global $SESSIONID;
?>
<voice sessionid="<?= $SESSIONID ?>">
	<message name="promptstoptime">
		<field name="stoptime" type="dtmf" timeout="5000" max="20">
			<prompt repeat="2">
				<tts gender="female">Enter the time you want your calls to stop followed by the pound key</tts>

			</prompt>

			<timeout>
				<goto message="error" />
			</timeout>
		</field>

		<field name="stopampm" type="menu" timeout="5000" sticky="true">
			<prompt repeat="1">
				<tts gender="female">press 1 for am or 2 for pm</tts>

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
	</message>

</voice>
<?
}
function commitJob()
{
	global $SESSIONDATA;

	$numdays = $SESSIONDATA['numdays'];
	$priority = $SESSIONDATA['priority'];

	loadUser();
	global $USER, $ACCESS;

	loadTimezone();
	$now = QuickQuery("select now()");

	// now create the job
	$job= Job::jobWithDefaults();

	$job->name = "Call In - ".$now;
	$job->type = "phone";

	$job->createdate = $now;
	$job->startdate = date("Y-m-d", strtotime("today"));
	$job->enddate = date("Y-m-d", strtotime($job->startdate) + (($numdays - 1) * 86400));
	if (isset($SESSIONDATA['starttime'])) {
		$job->starttime = date("H:i", strtotime($SESSIONDATA['starttime']));
	}
	if (isset($SESSIONDATA['stoptime'])) {
		$job->endtime = date("H:i", strtotime($SESSIONDATA['stoptime']));
	}


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
		if (isset($_SERVER['WINDIR'])) {
			$cmd = "start php ..\jobprocess.php $jobid";
			pclose(popen($cmd,"r"));
		} else {
			$cmd = "php ../jobprocess.php $jobid > /dev/null &";
			exec($cmd);
		}

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

			confirmCallWindow();

	// if they listened to their call window options
	} else if (isset($BFXML_VARS['usecallwin'])) {

			if ($BFXML_VARS['usecallwin'] == "1") {
				$listname = $SESSIONDATA['listname'];
				$priority = $SESSIONDATA['priority'];
				$numdays = $SESSIONDATA['numdays'];
				jobConfirm($listname, $priority, $numdays);
			} else {
				promptStartTime();
			}

	// if they entered their start time
	} else if (isset($BFXML_VARS['starttime'])) {

			// validate start time
			$starttime = $BFXML_VARS['starttime'];
			$starttime = substr($starttime, 0, strlen($starttime)-2) . ":" . substr($starttime, strlen($starttime)-2);
			if ($BFXML_VARS['startampm'] == "1") {
				$starttime = $starttime."am";
			} else {
				$starttime = $starttime."pm";
			}
			glog("starttime: ".$starttime);

			$isValid = strtotime($starttime);

			if ($isValid) {
				// check user call restriction
				loadUser();
				global $USER, $ACCESS;
				if ($ACCESS->getValue("callearly")) {
					$isValid = ((strtotime($starttime) - strtotime($ACCESS->getValue("callearly"))) >= 0);
				}
			}

			if ($isValid) {
				$SESSIONDATA['starttime'] = $starttime;
				promptStopTime();
			} else {
				promptStartTime(true);
			}


	// if they entered their stop time
	} else if (isset($BFXML_VARS['stoptime'])) {

			// validate stop time
			$stoptime = $BFXML_VARS['stoptime'];
			$stoptime = substr($stoptime, 0, strlen($stoptime)-2) . ":" . substr($stoptime, strlen($stoptime)-2);
			if ($BFXML_VARS['stopampm'] == "1") {
				$stoptime = $stoptime."am";
			} else {
				$stoptime = $stoptime."pm";
			}
			glog("stoptime: ".$stoptime);

			$isMismatch = false;
			$isValid = strtotime($stoptime);

			if ($isValid) {
				// check user call restriction
				loadUser();
				global $USER, $ACCESS;
				if ($ACCESS->getValue("calllate")) {
					$isValid = ((strtotime($stoptime) - strtotime($ACCESS->getValue("calllate"))) <= 0);
				}
				if ($isValid) {
					// check that start is earlier than stop
					$isValid = ((strtotime($SESSIONDATA['starttime']) - strtotime($stoptime)) < 0);
					$isMismatch = !$isValid;
				}
			}

			if ($isValid) {
				$SESSIONDATA['stoptime'] = $stoptime;
				$listname = $SESSIONDATA['listname'];
				$priority = $SESSIONDATA['priority'];
				$numdays = $SESSIONDATA['numdays'];
				jobConfirm($listname, $priority, $numdays);
			} else {
				promptStartTime(true, $isMismatch);
			}

	// if they listened to confirmation
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

	// they already entered job options, but returned to select a different list
	// so keep their options and reply the confirm
	} else if ( isset($SESSIONDATA['listname']) &&
				isset($SESSIONDATA['priority']) &&
				isset($SESSIONDATA['numdays']) &&
				isset($SESSIONDATA['starttime']) &&
				isset($SESSIONDATA['stoptime'])) {

				$listname = $SESSIONDATA['listname'];
				$priority = $SESSIONDATA['priority'];
				$numdays = $SESSIONDATA['numdays'];
				jobConfirm($listname, $priority, $numdays);

	// play the job options
	} else {
		jobOptions();
	}


?>