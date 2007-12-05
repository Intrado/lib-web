<?
// phone inbound, job options and confirmation to submit

include_once("inboundutils.inc.php");
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
	global $SESSIONID, $SESSIONDATA;

	loadUser(); // must load user before rendering list
	global $USER, $ACCESS;

	// find list size
	$list = new PeopleList($SESSIONDATA['listid']);
	$renderedlist = new RenderedList($list);
	$renderedlist->mode = "preview";
	$renderedlist->renderList();
	$listsize = $renderedlist->total;
	glog("number of people in list: ".$listsize);

	// if job is one day, and stop time is in the past... warn them about a job that is ineffective
	// NOTE this case should not exist, should be handled by checkExpirationThenConfirm() method
	$isValid = true;
	if ($numdays == 1) {
		loadTimezone();
		$now = QuickQuery("select now()");
		$nowtime = substr($now, 11);
		$isValid = ((strtotime($nowtime) - strtotime($SESSIONDATA['stoptime'])) < 0);
	}

?>
<voice sessionid="<?= $SESSIONID ?>">

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
				<tts gender="female"><?= htmlentities($listname, ENT_COMPAT, "UTF-8") ?></tts>
				<audio cmid="file://prompts/inbound/Confirmation2.wav" />
				<tts gender="female"><?= htmlentities($priority, ENT_COMPAT, "UTF-8") ?></tts>
				<audio cmid="file://prompts/inbound/Confirmation3.wav" />

<?				if ($numdays > 1) { ?>
					<audio cmid="file://prompts/inbound/<?= $numdays ?>Days.wav" />
<?				} else { ?>
					<audio cmid="file://prompts/inbound/1Day.wav" />
<?				} ?>

				<audio cmid="file://prompts/inbound/BetweenTheHoursOf.wav" />
				<tts gender="female"><?= $SESSIONDATA['starttime'] ?></tts>
				<audio cmid="file://prompts/inbound/And.wav" />
				<tts gender="female"><?= $SESSIONDATA['stoptime'] ?></tts>

				<goto message="jobconfirm" />
	</message>
<?	} ?>

	<message name="jobconfirm">
		<field name="sendjob" type="menu" timeout="5000" sticky="true">
			<prompt repeat="1">

				<audio cmid="file://prompts/inbound/ConfirmJob.wav" />
				<tts gender="female">Press the star key to hear these options again.</tts>
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
	global $SESSIONID;
?>
<voice sessionid="<?= $SESSIONID ?>">
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
	global $SESSIONDATA;

	$numdays = $SESSIONDATA['numdays'];
	$priority = $SESSIONDATA['priority'];

	loadUser();
	global $USER, $ACCESS;

	loadTimezone();

	// now create the job
	$job= Job::jobWithDefaults();

	$job->name = "Call In - " . date("M d, Y g:i a");
	$job->type = "phone";

	$job->createdate = date("Y-m-d");
	$job->startdate = date("Y-m-d");
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
		$job->runNow();

		return true;
	}
	return false;
}

function checkExpirationThenConfirm($playback=true)
{
	global $SESSIONDATA;

	$invalidreason = "none";
	$isValid = true;
	if ($SESSIONDATA['numdays'] == "1") {
		// check that current time is earlier than stop time, if numdays=1
		loadUser();
		global $USER, $ACCESS;

		loadTimezone();
		$now = QuickQuery("select now()");
		$nowtime = substr($now, 11);

		$isValid = ((strtotime($nowtime) - strtotime($SESSIONDATA['stoptime'])) < 0);
		if (!$isValid) $invalidreason = "past";
	}
	if ($isValid) {
		$listname = $SESSIONDATA['listname'];
		$priority = $SESSIONDATA['priority'];
		$numdays = $SESSIONDATA['numdays'];
		jobConfirm($listname, $priority, $numdays, $playback);
	} else {
		promptStartTime(true, $invalidreason);
	}
}

//////////////////////////////

if($REQUEST_TYPE == "new"){
	?>
	<error>inboundjob: wanted continue, got new </error>
	<?
} else if($REQUEST_TYPE == "continue") {
	glog("job continue...");

	// if they entered the job options
	if (isset($BFXML_VARS['numdays'])) {

			$SESSIONDATA['numdays'] = $BFXML_VARS['numdays'];

			// if they are reentering job options, jump ahead to job confirm
			if (isset($SESSIONDATA['starttime']) &&
				isset($SESSIONDATA['stoptime'])) {

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
					$isValid = ((strtotime($SESSIONDATA['starttime']) - strtotime($stoptime)) < 0);
					if (!$isValid) $invalidreason = "mismatch";
				}
			}

			if ($isValid) {
				$SESSIONDATA['stoptime'] = $stoptime;
				checkExpirationThenConfirm();
			} else {
				promptStartTime(true, $invalidreason);
			}

	// if they listened to confirmation
	} else if (isset($BFXML_VARS['sendjob'])) {
			glog("sendjob ".$BFXML_VARS['sendjob']);

			// send the job
			if ($BFXML_VARS['sendjob'] == "1" &&
				commitJob())
			{
				$SESSIONDATA['jobSubmit'] = true;
				forwardToPage("inboundgoodbye.php");
			}
			// replay list selection
			else if ($BFXML_VARS['sendjob'] == "2")
			{
				unset($SESSIONDATA['currentListPage']); // reset paging
				forwardToPage("inboundlist.php");
			}
			// replay job type selection
			else if ($BFXML_VARS['sendjob'] == "3")
			{
				unset($SESSIONDATA['currentJobtypePage']); // reset paging
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
	} else if ( isset($SESSIONDATA['listname']) &&
				isset($SESSIONDATA['priority']) &&
				isset($SESSIONDATA['numdays']) &&
				isset($SESSIONDATA['starttime']) &&
				isset($SESSIONDATA['stoptime'])) {

				checkExpirationThenConfirm();

	// play the job options
	} else {
		jobOptions();
	}

} else {
	//huh, they must have hung up
	$SESSIONDATA = null;
	?>
	<ok />
	<?
}

?>