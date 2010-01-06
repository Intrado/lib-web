<?
// phone inbound, job options and confirmation to submit

include_once("../inc/utils.inc.php"); // for jobdefaults getSystemSetting()
require_once("../inc/date.inc.php");
include_once("../obj/User.obj.php");
include_once("../obj/Rule.obj.php");
include_once("../obj/Access.obj.php");
include_once("../obj/Job.obj.php");
include_once("../obj/JobLanguage.obj.php");
include_once("../obj/JobType.obj.php");
include_once("../obj/Permission.obj.php");
include_once("../obj/PeopleList.obj.php");
include_once("../obj/RenderedList.obj.php");
include_once("../obj/FieldMap.obj.php");
include_once("inboundutils.inc.php");


global $BFXML_VARS;


function jobOptions()
{
	$maxdays = QuickQuery("SELECT permission.value FROM permission, user WHERE permission.accessid = user.accessid and permission.name='maxjobdays' and user.id=".$_SESSION['userid']);
	//error_log("maxdays".$maxdays);

?>
<voice>
	<message name="joboptions">

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

<?				if ($maxdays > 1) { ?>
					<audio cmid="file://prompts/inbound/<?= $maxdays ?>Days.wav" />
<?				} else { ?>
					<audio cmid="file://prompts/inbound/1Day.wav" />
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

function jobConfirm($listname, $priority, $numdays=1, $playback=true)
{

	loadUser(); // must load user before rendering list
	global $USER, $ACCESS;

	// find list size
	$list = new PeopleList($_SESSION['listid']);
	$renderedlist = new RenderedList($list);
	$renderedlist->calcStats();
	$listsize = $renderedlist->total;
	$jobtype = new JobType($priority);

	//error_log("number of people in list: ".$listsize);

	// if job is one day, and stop time is in the past... warn them about a job that is ineffective
	// NOTE this case should not exist, should be handled by checkExpirationThenConfirm() method
	$isValid = true;
	if ($numdays == 1) {
		loadTimezone();
		$now = QuickQuery("select now()");
		$nowtime = substr($now, 11);
		$isValid = ((strtotime($nowtime) - strtotime($_SESSION['stoptime'])) < 0);
	}

?>
<voice>

<?	if (!$isValid) { ?>
		<message name="jobexpired">
		<audio cmid="file://prompts/inbound/ExpiredCallWindowWarning.wav" />
		</message>
<?	} ?>

<?	if ($playback) { ?>
	<message name="jobplayback">
				<audio cmid="file://prompts/inbound/SubmitJobTo.wav" />
				<tts gender="female"><?= $listsize ?> </tts>
<?				if ($listsize == 1) { ?>
					<audio cmid="file://prompts/inbound/PersonUsingList.wav" />
<?				} else { ?>
					<audio cmid="file://prompts/inbound/PeopleUsingList.wav" />
<? 				} ?>
				<tts gender="female"><?= escapehtml($listname) ?></tts>
				<audio cmid="file://prompts/inbound/Confirmation2.wav" />
				<tts gender="female"><?= escapehtml($jobtype->name) ?></tts>
				<audio cmid="file://prompts/inbound/Confirmation3.wav" />

<?				if ($numdays > 1) { ?>
					<audio cmid="file://prompts/inbound/<?= $numdays ?>Days.wav" />
<?				} else { ?>
					<audio cmid="file://prompts/inbound/1Day.wav" />
<?				} ?>

				<audio cmid="file://prompts/inbound/BetweenTheHoursOf.wav" />
				<tts gender="female"><?= $_SESSION['starttime'] ?></tts>
				<audio cmid="file://prompts/inbound/And.wav" />
				<tts gender="female"><?= $_SESSION['stoptime'] ?></tts>

				<goto message="jobconfirm" />
	</message>
<?	} ?>

	<message name="jobconfirm">
		<field name="sendjob" type="menu" timeout="5000" sticky="true">
			<prompt repeat="1">

				<audio cmid="file://prompts/inbound/ConfirmJob.wav" />
			</prompt>

			<choice digits="1" />
			<choice digits="2" />
			<choice digits="3" />
			<choice digits="4" />
			<choice digits="5" />
			<choice digits="*" />

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
	loadUser();
	global $USER, $ACCESS;

	$_SESSION['starttime'] = $USER->getCallEarly();
	$_SESSION['stoptime'] = $USER->getCallLate();

?>
<voice>
	<message name="confirmcallwindow">
		<field name="usecallwin" type="menu" timeout="5000" sticky="true">
			<prompt repeat="1">
				<audio cmid="file://prompts/inbound/CallWindowSet.wav" />
				<tts gender="female"><?= $USER->getCallEarly(); ?></tts>
				<audio cmid="file://prompts/inbound/And.wav" />
				<tts gender="female"><?= $USER->getCallLate(); ?></tts>
				<audio cmid="file://prompts/inbound/AcceptTimes.wav" />
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

function promptStartTime($playinvalid=false, $invalidreason="none")
{
	// check user restriction
	loadUser();
	global $USER, $ACCESS;

	//error_log("access early ".$ACCESS->getValue("callearly"));
	//error_log("access late  ".$ACCESS->getValue("calllate"));

	$playrestriction = ($ACCESS->getValue("callearly") | $ACCESS->getValue("calllate"));
	//error_log("playrestrict: ".$playrestriction);
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
<voice>
	<message name="promptstarttime">
		<field name="starttime" type="dtmf" timeout="5000" max="20">
			<prompt repeat="2">

<?	if ($playinvalid) { ?>
		<audio cmid="file://prompts/inbound/InvalidTime.wav" />
<?	} ?>

<?	if (!strcmp($invalidreason, "mismatch")) { ?>
		<audio cmid="file://prompts/inbound/StopTimeAfterStartTime.wav" />
<?	} ?>

<?	if (!strcmp($invalidreason, "past")) { ?>
		<audio cmid="file://prompts/inbound/StopTimeAfterCurrentTime.wav" />
<?	} ?>

<?	if ($playrestriction) { ?>
		<audio cmid="file://prompts/inbound/EnterTimeBetweenAllowedHours.wav" />
		<tts gender="female"><?= $early ?></tts>
		<audio cmid="file://prompts/inbound/And.wav" />
		<tts gender="female"><?= $late ?></tts>
<?	} ?>

				<audio cmid="file://prompts/inbound/EnterStartTime.wav" />
			</prompt>

			<timeout>
				<goto message="error" />
			</timeout>
		</field>

		<field name="startampm" type="menu" timeout="5000" sticky="true">
			<prompt repeat="1">
				<audio cmid="file://prompts/inbound/EnterAMorPM.wav" />

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
?>
<voice>
	<message name="promptstoptime">
		<field name="stoptime" type="dtmf" timeout="5000" max="20">
			<prompt repeat="2">
				<audio cmid="file://prompts/inbound/EnterStopTime.wav" />
			</prompt>

			<timeout>
				<goto message="error" />
			</timeout>
		</field>

		<field name="stopampm" type="menu" timeout="5000" sticky="true">
			<prompt repeat="1">
				<audio cmid="file://prompts/inbound/EnterAMorPM.wav" />
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

	$numdays = $_SESSION['numdays'];

	loadUser();
	global $USER, $ACCESS;

	loadTimezone();

	// now create the job
	$job= Job::jobWithDefaults();

	$job->name = "Call In - " . date("M j, Y g:i a");
	$job->type = "phone";

	$job->createdate = date("Y-m-d");
	$job->startdate = date("Y-m-d");
	$job->enddate = date("Y-m-d", strtotime($job->startdate) + (($numdays - 1) * 86400));
	if (isset($_SESSION['starttime'])) {
		$job->starttime = date("H:i", strtotime($_SESSION['starttime']));
	}
	if (isset($_SESSION['stoptime'])) {
		$job->endtime = date("H:i", strtotime($_SESSION['stoptime']));
	}


	$job->messagegroupid = $_SESSION['messagegroupid'];

	$jobtype = new JobType($_SESSION['priority']);

	$job->jobtypeid = $jobtype->id;
	$job->description = $jobtype->name;

	//error_log("priority: ".$jobtype->name."   id: ".$job->jobtypeid);

	//error_log("about to create the job!!!");
	$job->create();
	$jobid = $job->id;
	if ($jobid) {
		// associate the list for this job
		QuickUpdate("insert into joblist (jobid, listid) values (?,?)", false, array($job->id, $_SESSION['listid']));

		// now we submit this job
		//error_log("now submit the job to process ".$jobid);
		$job->runNow();

		return true;
	}
	return false;
}

function checkExpirationThenConfirm($playback=true)
{

	$invalidreason = "none";
	$isValid = true;
	if ($_SESSION['numdays'] == "1") {
		// check that current time is earlier than stop time, if numdays=1
		loadUser();
		global $USER, $ACCESS;

		loadTimezone();
		$now = QuickQuery("select now()");
		$nowtime = substr($now, 11);

		$isValid = ((strtotime($nowtime) - strtotime($_SESSION['stoptime'])) < 0);
		if (!$isValid) $invalidreason = "past";
	}
	if ($isValid) {
		$listname = $_SESSION['listname'];
		$priority = $_SESSION['priority'];
		$numdays = $_SESSION['numdays'];
		jobConfirm($listname, $priority, $numdays, $playback);
	} else {
		promptStartTime(true, $invalidreason);
	}
}

//////////////////////////////

if($REQUEST_TYPE == "new"){
	?>
	<error>inboundjob: wanted continue, got new </error>
	<hangup />
	<?
} else if($REQUEST_TYPE == "continue") {
	//error_log("job continue...");

	// if they entered the job options
	if (isset($BFXML_VARS['numdays'])) {

			$_SESSION['numdays'] = $BFXML_VARS['numdays'] +0;

			// if they are reentering job options, jump ahead to job confirm
			if (isset($_SESSION['starttime']) &&
				isset($_SESSION['stoptime'])) {

				checkExpirationThenConfirm();

			// otherwise confirm call window and proceed
			} else {
				confirmCallWindow();
			}

	// if they listened to their call window options
	} else if (isset($BFXML_VARS['usecallwin'])) {

			if ($BFXML_VARS['usecallwin'] == "1") {
				checkExpirationThenConfirm();
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
			//error_log("starttime: ".$starttime);

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
				$_SESSION['starttime'] = $starttime;
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
			//error_log("stoptime: ".$stoptime);

			$invalidreason = "none";
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
					$isValid = ((strtotime($_SESSION['starttime']) - strtotime($stoptime)) < 0);
					if (!$isValid) $invalidreason = "mismatch";
				}
			}

			if ($isValid) {
				$_SESSION['stoptime'] = $stoptime;
				checkExpirationThenConfirm();
			} else {
				promptStartTime(true, $invalidreason);
			}

	// if they listened to confirmation
	} else if (isset($BFXML_VARS['sendjob'])) {
			//error_log("sendjob ".$BFXML_VARS['sendjob']);

			// send the job
			if ($BFXML_VARS['sendjob'] == "1" &&
				commitJob())
			{
				$_SESSION['jobSubmit'] = true;
				forwardToPage("inboundgoodbye.php");
			}
			// replay list selection
			else if ($BFXML_VARS['sendjob'] == "2")
			{
				unset($_SESSION['currentListPage']); // reset paging
				forwardToPage("inboundlist.php");
			}
			// replay job type selection
			else if ($BFXML_VARS['sendjob'] == "3")
			{
				unset($_SESSION['currentJobtypePage']); // reset paging
				forwardToPage("inboundjobtype.php");
			}
			// replay numdays option
			else if ($BFXML_VARS['sendjob'] == "4")
			{
				jobOptions();
			}
			// replay call window
			else if ($BFXML_VARS['sendjob'] == "5")
			{
				promptStartTime();
			}
			// replay confirmation options
			else if ($BFXML_VARS['sendjob'] == "*")
			{
				checkExpirationThenConfirm(false); // do not playback job settings, just options
			}

	// they already entered job options, but returned to select a different list
	// so keep their options and replay the confirm
	} else if ( isset($_SESSION['listname']) &&
				isset($_SESSION['priority']) &&
				isset($_SESSION['numdays']) &&
				isset($_SESSION['starttime']) &&
				isset($_SESSION['stoptime'])) {

				checkExpirationThenConfirm();

	// play the job options
	} else {
		jobOptions();
	}

} else {
	//huh, they must have hung up
	$_SESSION = array();
	?>
	<ok />
	<?
}

?>