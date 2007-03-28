<?
$SETTINGS = parse_ini_file("inc/settings.ini.php",true);
$IS_COMMSUITE = $SETTINGS['feature']['is_commsuite'];

require_once("inc/db.inc.php");
require_once("inc/DBMappedObject.php");
require_once("inc/DBRelationMap.php");
require_once("inc/utils.inc.php");
require_once("inc/date.inc.php");
require_once("obj/User.obj.php");
require_once("obj/Access.obj.php");
require_once("obj/Permission.obj.php");
require_once("obj/Job.obj.php");
require_once("obj/JobLanguage.obj.php");
require_once("obj/JobType.obj.php");
require_once("obj/Rule.obj.php");
require_once("obj/FieldMap.obj.php");
require_once("obj/SurveyQuestionnaire.obj.php");


set_time_limit(0);

$st = microtime_float();

$jobid = $argv[1] +0;

if ($jobid == 0)
	exit(-1);

$job = new Job($jobid);
$USER = new User($job->userid);
$jobtype = new JobType($job->jobtypeid);

$priority = $jobtype->priority ? $jobtype->priority : 30000;
$systempriority = $jobtype->systempriority ? $jobtype->systempriority : 3;
$timeslices = $jobtype->timeslices;

//check the user permissions
$ACCESS = new Access($USER->accessid);
$_SESSION['access'] = $ACCESS;
if (!$USER->enabled) {
	exit(-4);
}

$timezone = QuickQuery("select timezone from customer where id=$USER->customerid");
date_default_timezone_set($timezone);
QuickUpdate("set time_zone='" . $timezone . "'");

if ($job->status=="repeating") {
	if (getSystemSetting("disablerepeat"))
		exit(-4);

	//update the finishdate (reused as last run for repeating jobs)
	QuickUpdate("update job set finishdate=now() where id='$jobid'");

	//make a copy of this job and run it
	$newjob = new Job($jobid);
	$newjob->id = NULL;
	$newjob->name .= " - " . date("M j, g:i a");
	$newjob->status = "new";
	$newjob->assigned = NULL;
	$newjob->scheduleid = NULL;
	$newjob->finishdate = NULL;

	$newjob->createdate = QuickQuery("select now()");

	//refresh the dates to present
	$daydiff = strtotime($newjob->enddate) - strtotime($newjob->startdate);

	$newjob->startdate = date("Y-m-d", time());
	$newjob->enddate = date("Y-m-d", time() + $daydiff);

	if (getSystemSetting('retry') != "")
		$newjob->setOptionValue("retry",getSystemSetting('retry'));

	$newjob->create();

	//copy all the job language settings
	QuickUpdate("insert into joblanguage (jobid,messageid,type,language)
				select $newjob->id, messageid, type,language
				from joblanguage where jobid=$job->id");

	$jobid = $newjob->id;
	$job = $newjob;
	$isrepeating = true;
} else {
	$isrepeating = false;
}

//assign the job to this instance of jobprocess
$code = DBSafe(md5(microtime().mt_rand()));
$query = "update job set assigned='$code' where assigned is null and status='new' and id=$jobid";
QuickUpdate($query);
if (!QuickQuery("select count(*) from job where assigned='$code' and id=$jobid"))
	exit(-2); //not me!

$usersql = $USER->userSQL("p","pd");
//get and compose list rules
$listrules = DBFindMany("Rule","from listentry le, rule r where le.type='R'
		and le.ruleid=r.id and le.listid='" . $job->listid .  "' order by le.sequence", "r");
if (count($listrules) > 0)
	$listsql = "1" . Rule::makeQuery($listrules, "pd");
else
	$listsql = "0";//dont assume anyone is in the list if there are no rules

//get all the rules based people that dont have add or remove records
//then union all with all the people that have add records

$langfield = FieldMap::getLanguageField();
$count = 0;

if ($job->type == "survey") {


	$questionnaire = new SurveyQuestionnaire($job->questionnaireid);

	// we need to make a copy of this questionnaire for this job so that if they edit it, it won't affect reporting
	$questionnaire->id = null;
	$questionnaire->deleted = 1; //default to deleted so it doesnt show up in UI
	$questionnaire->create(); //save a copy w/ new ID

	//update the job reference
	$oldquestionnaireid = $job->questionnaireid;
	$job->questionnaireid = $questionnaire->id;
	$job->update(array("questionnaireid"));

	//now copy over the questions
	QuickUpdate("insert into surveyquestion (questionnaireid, questionnumber,
						webmessage, phonemessageid, reportlabel, validresponse)
				select $questionnaire->id as questionnaireid, questionnumber, webmessage, phonemessageid, reportlabel, validresponse
				from surveyquestion where questionnaireid = $oldquestionnaireid");

	$surveytypes = array("phone" => $questionnaire->hasphone,
						"email" => $questionnaire->hasweb);

	foreach ($surveytypes as $type => $hastype) {
		if (!$hastype)
			continue;

		//insert the workitems with no messageid, messagedigester will pick up that this is a survey and go from there
		echo "$type\n";
		$query = "
			insert into jobworkitem (jobid, customerid, type, personid, priority, systempriority, status)
			(select $jobid as jobid, $USER->customerid as customerid, '$type' as type, p.id as personid,
				$priority as priority, $systempriority as systempriority, 'new' as status
			from person p left join persondata pd on (p.id=pd.personid)
			left join listentry le on (p.id=le.personid and le.listid=$job->listid)
			where $usersql and $listsql and le.type is null and p.userid is null order by p.id)

			union all

			(select $jobid as jobid, $USER->customerid as customerid, '$type' as type, p.id as personid,
				$priority as priority, $systempriority as systempriority, 'new' as status
			from listentry le
			straight_join person p on (p.id=le.personid)
			left join persondata pd on (le.personid=pd.personid)
			left join joblanguage jl on (pd.$langfield=jl.language and jl.jobid=$jobid and jl.type='$type')
			where p.customerid = $USER->customerid
			and le.listid=$job->listid and le.type='A'
			order by le.id)
		";

		$res = QuickUpdate($query);

		if ($res === false) {
			error_log("Problem inserting survey job $jobid: " . mysql_error() . " Query was:\n\n" . $query . "\n\n");
		} else if ($res > 0) {

			$numbuckets = (int) max(1,min($timeslices ,$res/2));
			$query = "update jobworkitem set priority = priority + (id % $numbuckets) where jobid=$jobid";
			QuickUpdate($query);
		}

		$count += $res;
	}
} else {
	//do regular notification job
	$defaultmessages = array("phone" => $job->phonemessageid,
							"email" => $job->emailmessageid,
							"print" => $job->printmessageid);

	foreach ($defaultmessages as $type => $defmsgid) {

		if ($defmsgid != NULL) {
			$query = "
				insert into jobworkitem (jobid, customerid, type,personid, messageid, priority, systempriority, status)
				(select $jobid as jobid, $USER->customerid as customerid, '$type' as type, p.id as personid,
					coalesce(jl.messageid,$defmsgid) as messageid, $priority as priority, $systempriority as systempriority,
					'new' as status
				from person p left join persondata pd on (p.id=pd.personid)
				left join listentry le on (p.id=le.personid and le.listid=$job->listid)
				left join joblanguage jl on (pd.$langfield=jl.language and jl.jobid=$jobid and jl.type='$type')
				where $usersql and $listsql and le.type is null and p.userid is null
				order by p.id)

				union all

				(select $jobid as jobid, $USER->customerid as customerid, '$type' as type, p.id as personid,
					coalesce(jl.messageid,$defmsgid) as messageid, $priority as priority, $systempriority as systempriority,
					'new' as status
				from listentry le
				straight_join person p on (p.id=le.personid)
				left join persondata pd on (le.personid=pd.personid)
				left join joblanguage jl on (pd.$langfield=jl.language and jl.jobid=$jobid and jl.type='$type')
				where p.customerid = $USER->customerid
				and le.listid=$job->listid and le.type='A'
				order by le.id)
			";

			$res = QuickUpdate($query);

			if ($res === false) {
				error_log("Problem inserting job $jobid: " . mysql_error() . " Query was:\n\n" . $query . "\n\n");
			} else if ($res > 0) {

				$numbuckets = (int) max(1,min($timeslices ,$res/2));
				$query = "update jobworkitem set priority = priority + (id % $numbuckets) where jobid=$jobid";
				QuickUpdate($query);
			}
			$count += $res;
		}
	}
}

//if this is an empty repeating job, then silently delete it so it doesn't clog the system (like on hollidays)
if ($count == 0 && $isrepeating) {
	QuickUpdate("delete from joblanguage where jobid=$deletedid");
	$job->destroy();
} else {
	QuickUpdate("update job set status='active', assigned = null where id=$jobid");

	//update the listlastused field
	//FIXME there is no list.lastused field and modified is not used so we are going to use that instead
	QuickUpdate("update list set modified=now() where id=$job->listid");
}
?>
