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
require_once("obj/Rule.obj.php");
require_once("obj/FieldMap.obj.php");

set_time_limit(0);

$st = microtime_float();

$jobid = $argv[1] +0;

if ($jobid == 0)
	exit(-1);

$job = new Job($jobid);
$USER = new User($job->userid);

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

	//make a copy of this job and run it
	$newjob = new Job($jobid);
	$newjob->id = NULL;
	$newjob->name .= " - " . date("F jS, Y");
	$newjob->status = "new";
	$newjob->assigned = NULL;
	$newjob->scheduleid = NULL;

	$newjob->createdate = QuickQuery("select now()");

	//refresh the dates to present
	$daydiff = strtotime($newjob->enddate) - strtotime($newjob->startdate);

	$newjob->startdate = date("Y-m-d", time());
	$newjob->enddate = date("Y-m-d", time() + $daydiff);

	if (getSystemSetting('retry') != "")
		$job->setOptionValue("retry",getSystemSetting('retry'));

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
//echo $query;
QuickUpdate($query);
if (!QuickQuery("select count(*) from job where assigned='$code' and id=$jobid"))
	exit(-2); //not me!
//echo "got job";
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

$defaultmessages = array("phone" => $job->phonemessageid,
						"email" => $job->emailmessageid,
						"print" => $job->printmessageid);

$count = 0;
foreach ($defaultmessages as $type => $defmsgid) {

//TODO disable this for now so that locked down profiles still work (ex attendance)
//	if (!$USER->authorize('send' . $type))
//		continue;

	if ($defmsgid != NULL) {

//skip dedupe for new dedupe in messagedigester
/*
		//should we immediately start rendering messages or do we need to check first (ie dedupe)?
		if ($type == "phone" && $job->isOption("skipduplicates")) {
			$status = "checking";
		} else {
			$status = "new";
		}
*/
	$status = "new";
		$query = "
		insert into jobworkitem (jobid, type,personid, messageid, priority, systempriority, status)
		(select $jobid as jobid, '$type' as type, p.id as personid,
			coalesce(jl.messageid,$defmsgid) as messageid, ifnull(jt.priority,100000) as priority, ifnull(jt.systempriority,3) as systempriority,
			'$status' as status
		from person p left join persondata pd on (p.id=pd.personid)
		left join listentry le on (p.id=le.personid and le.listid=$job->listid)
		left join joblanguage jl on (pd.$langfield=jl.language and jl.jobid=$job->id and jl.type='$type')
		left join job j on (j.id = $jobid)
		left join jobtype jt on (jt.id = j.jobtypeid)
		where $usersql and $listsql and le.type is null and p.userid is null)

		union all

		(select $jobid as jobid, '$type' as type, p.id as personid,
			coalesce(jl.messageid,$defmsgid) as messageid, ifnull(jt.priority,100000) as priority, ifnull(jt.systempriority,3) as systempriority,
			'$status' as status
		from person p left join persondata pd on (p.id=pd.personid)
		inner join listentry le on (le.listid=$job->listid and p.id=le.personid and le.type='A')
		left join joblanguage jl on (pd.$langfield=jl.language and jl.jobid=$job->id and jl.type='$type')
		left join job j on (j.id = $jobid)
		left join jobtype jt on (jt.id = j.jobtypeid)
		where p.customerid = $USER->customerid)
		";

		$count += QuickUpdate($query);

//skip dedupe for new dedupe in messagedigester
/*
		//do we need to dedupe?
		if ($type == "phone" && $job->isOption("skipduplicates")) {

			//find everyone that has a duplicate phone number.

			$query = "
			select wi.id,p.phone
			from jobworkitem wi
			left join phone p on (wi.personid = p.personid)
			left join phone p2  on (p.phone=p2.phone and p2.personid != wi.personid)
			left join jobworkitem wi2 on (p2.personid = wi2.personid)
			where wi.jobid = $jobid and wi.type='phone'
			and wi2.jobid = $jobid and wi2.type='phone'
			and p.sequence = 0 and p2.sequence = 0 and p.phone != ''
			group by p.phone, wi.id
			";
			$result = Query($query);

			//go through results and let the first one though, set the others to duplicate
			$lastphone = false;
			$dedupedid = false;
			while ($row = DBGetRow($result)) {
				if ($lastphone != $row[1]) {
					$lastphone = $row[1];
					$dedupedpersonid = $row[0];
					$query = "update jobworkitem wi set wi.status='new' where wi.id=$dedupedpersonid";
					QuickUpdate($query);
				} else {
					$query = "update jobworkitem wi set wi.status='duplicate',wi.duplicateid=$dedupedpersonid where wi.id=" . $row[0];
					QuickUpdate($query);
				}
			}
		}

		//do we need to update the rest from "checking" status?
		if ($status == "checking") {
			QuickUpdate("update jobworkitem wi set wi.status='new' where wi.jobid=$jobid and wi.status='checking'");
		}
*/
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
