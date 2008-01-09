<?
$SETTINGS = parse_ini_file("../inc/settings.ini.php",true);

$dialerdbname = "dialer";
$newdbname = "commsuite";

$dbhost = "localhost";
$dbuser = $SETTINGS['db']['user'];
$dbpass = $SETTINGS['db']['pass'];

$customerid = 1;

$starttime = microtime(true);

$db = mysql_connect($dbhost,$dbuser,$dbpass,true);
mysql_select_db($dialerdbname,$db);

$custdb = mysql_connect($dbhost,$dbuser,$dbpass,true);
mysql_select_db($newdbname, $custdb);

function esc ($str,$db) {
	return mysql_real_escape_string($str,$db);
}

function escrow ($row,$db) {
	$newrow = array();
	foreach ($row as $value) {
		if ($value === null)
			$newrow[] = "null";
		else
			$newrow[] = "'" . esc($value,$db) . "'";
	}
	return implode(",",$newrow);
}

function fieldlist ($table,$fields) {
	$fieldlist = array();
	foreach ($fields as $field) {
		$fieldlist[] = "`$table`.`$field`";
	}
	return implode(",",$fieldlist);
}

function copytable ($custid,$table,$fields,$source,$dest,$batch,$joincustomer = false, $groupby = false) {

 $fieldlist = fieldlist($table,$fields);

 $query = "select $fieldlist from `$table` ";

 if ($joincustomer)
  $query .= $joincustomer;
 else
  $query .= " where customerid=$custid";

 if ($groupby)
 	$query .= $groupby;

// echo "$table: $query\n\n";
 $sourceres = mysql_query($query,$source)
    or die ("Failed to query $table :" . mysql_error($source));

 do {
  $count = 0;
  $outrows = array();
  while ($count < $batch && ($row = mysql_fetch_row($sourceres))) {
   $outrows[] = "(". escrow($row,$dest) . ")";
   $count++;
  }

  //ins to dest
  if ($count) {
   $ins = "insert into `$table` ($fieldlist) values "
    . implode(",",$outrows);
   mysql_query($ins,$dest)
    or die ("Failed to insert into $table :" . mysql_error($dest));
  }
 } while ($row);
}


function parseOptions ($options) {
  $temparray = explode(",",$options);
  $optionsarray = array();
  foreach ($temparray as $index => $option) {
    if (strpos($option,"=") !== false) {
	  list($name,$val) = explode("=",$option);
	  $optionsarray[$name] = $val;
    } else {
	  $optionsarray[] = $option;
	}
  }
  return $optionsarray;
}

function restructureJobOptions($custid, $source, $dest, $batch, $joincustomer = false) {
	$query = "select job.id, job.maxcallattempts, job.options from job ";

    if ($joincustomer)
      $query .= $joincustomer;
    else
      $query .= " where customerid=$custid";

	$sourceres = mysql_query($query, $source)
		or die ("Failed to query job :" . mysql_error($source));

	do {
	  $count = 0;
  	  $outrows = array();
  	  while ($count < $batch && ($row = mysql_fetch_row($sourceres))) {
  	  	$jobid = $row[0];
  	  	$maxcallattempts = $row[1];
  	  	$options = $row[2];
  	  	$optionsarray = parseOptions($options);
  	  	$newrow = array();

  	  	$newrow[0] = $jobid;
  	  	$newrow[1] = "maxcallattempts";
  	  	$newrow[2] = $maxcallattempts;
  	  	$outrows[] = "(". escrow($newrow,$dest) . ")";

  	  	foreach ($optionsarray as $index => $option) {
  	  		$newrow[0] = $jobid;
			if (is_int($index)) {
				$newrow[1] = $option;
				$newrow[2] = 1;
			} else {
				$newrow[1] = $index;
				$newrow[2] = $option;
  	  		}
  	  		$outrows[] = "(". escrow($newrow,$dest) . ")";
  	  	}
        $count++;
  	  }

      //ins to dest
      if ($count) {
       $ins = "insert into `jobsetting` (jobid, name, value) values "
        . implode(",",$outrows);
       mysql_query($ins,$dest)
        or die ("Failed to insert into jobsetting :" . mysql_error($dest));
      }
  	} while ($row);
}

function restructureScheduleDay($custid, $source, $dest) {

	/*
	$join = "inner join schedule s on (s.id = scheduleid)
		inner join user u on (userid=u.id and u.customerid=$customerid)";

	copytable($customerid,"scheduleday",array("id", "scheduleid", "dow"),$db,$custdb,1000,$join);
	*/

	$join = " inner join schedule s on (s.id = scheduleid)
		inner join user u on (userid=u.id and u.customerid=$custid)";

// TODO orderby scheduleid
	$query = "select scheduleday.scheduleid, scheduleday.dow from scheduleday " . $join;

	$sourceres = mysql_query($query, $source)
		or die ("Failed to query job :" . mysql_error($source));

    $scheduleid = 0;
    $dow = array();
    $i = 0;

	while ($row = mysql_fetch_row($sourceres)) {
 	  if ($scheduleid != 0 && $scheduleid != $row[0]) {
	  	// we have all the dow fields, now update the schedule record
	  	$ins = "update schedule set daysofweek='" . implode(",", $dow) . "' where id=".$scheduleid;
	  	mysql_query($ins,$dest)
	  	  or die ("Failed to update schedule daysofweek : " . mysql_error($dest));
	  	// reset dow list
	  	$dow = array();
	  	$i = 0;
 	  }
	  $scheduleid = $row[0];
	  $dow[$i] = $row[1];
      $i++;
  	}
  	if ($scheduleid != 0) {
	  	// we have all the dow fields, now update the schedule record
	  	$ins = "update schedule set daysofweek='" . implode(",", $dow) . "' where id=".$scheduleid;
	  	mysql_query($ins,$dest)
	  	  or die ("Failed to update schedule daysofweek : " . mysql_error($dest));
 	}

}

function customerinfo($custid, $source, $dest){
	$query = "select inboundnumber, timezone, name from customer where id = '$custid'";
	$sourceres = mysql_query($query, $source)
				or die ("Failed to query customer: " . mysql_error($source));

	$row = mysql_fetch_row($sourceres);
	$query = "select count(*) from user where customerid = '$custid' and enabled='1' and login != 'schoolmessenger'";
	$sourceres2 = mysql_query($query, $source)
				or die ("Failed to query customer: " . mysql_error($source));
	$row2 = mysql_fetch_row($sourceres2);

	$destres = mysql_query("insert into setting (name, value) values
								('inboundnumber', '$row[0]'),
								('timezone', '$row[1]'),
								('displayname', '" . esc($row[2],$dest) . "'),
								('_maxusers' , '$row2[0]')", $dest)
							or die ("Failed to insert into setting: " . mysql_error($dest));
}

function genpassword() {
	$digits = 15;
	$passwd = "";
	$chars = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz";
	while ($digits--) {
		$passwd .= $chars[mt_rand(0,strlen($chars)-1)];
	}
	return $passwd;
}


//-------------------------------------------------------------------

$result = mysql_query("select inboundnumber from customer where id = '$customerid'", $db)
			or die ("Failed to query customer: " . mysql_error($db));

$row = mysql_fetch_row($result) or die ("Failed to query customer: $customerid");

echo "Doing $customerid\n";

$custpass = genpassword();
$destres = mysql_query("update customer set inboundnumber = '$row[0]' where id = '$customerid'", $custdb)
						or die("Failed to update customer inboundnumber: " . mysql_error($custdb));

//SETTING
copytable($customerid,"setting",array("id", "name", "value"),$db,$custdb,1000,false);

//Customer fields
customerinfo($customerid, $db, $custdb);

//ACCESS
copytable($customerid,"access",array("id","name","description"),$db,$custdb,1000,false);

//ADDRESS
$join = "inner join person p on (personid = p.id and p.customerid=$customerid)";
copytable($customerid,"address",array("id", "personid", "addressee", "addr1", "addr2", "city", "state", "zip"),$db,$custdb,1000,$join);

//AUDIOFILE
$join = "inner join user u on (userid=u.id and u.customerid=$customerid)";
copytable($customerid,"audiofile",array("id", "userid", "name", "description", "contentid", "recorddate", "deleted"),$db,$custdb,1000,$join);

//BLOCKEDNUMBER
copytable($customerid,"blockednumber",array("id","userid","description","pattern"),$db,$custdb,1000,false);

//CONTENT (audio files)
$join = "
inner join audiofile on (contentid=content.id)
inner join user u on (userid=u.id and u.customerid=$customerid)";
$groupby = " group by contentid ";
copytable($customerid,"content",array("id","contenttype","data"),$db,$custdb,1,$join, $groupby);

//CONTENT (voice reply)
$join = "
inner join voicereply on (contentid=content.id)
inner join user u on (userid=u.id and u.customerid=$customerid)";
copytable($customerid,"content",array("id","contenttype","data"),$db,$custdb,1,$join);

//EMAIL
$join = "inner join person p on (personid = p.id and p.customerid=$customerid)";
copytable($customerid,"email",array("id", "personid", "email", "sequence", "editlock"),$db,$custdb,1000,$join);

//FIELDMAP
copytable($customerid,"fieldmap",array("id", "fieldnum", "name", "options"),$db,$custdb,1000,false);

//IMPORT
copytable($customerid,"import",array("id", "uploadkey", "userid", "listid", "name", "description", "status", "type", "scheduleid", "ownertype", "updatemethod", "lastrun", "data", "datamodifiedtime"),$db,$custdb,1000,false);

//IMPORTFIELD
$join = "inner join import i on (importid = i.id and i.customerid=$customerid)";
copytable($customerid,"importfield",array("id", "importid", "mapto", "mapfrom"),$db,$custdb,1000,$join);

//IMPORTJOB
$join = "inner join import i on (importid = i.id and i.customerid=$customerid)";
copytable($customerid,"importjob",array("id", "importid", "jobid"),$db,$custdb,1000,$join);

//JOB
$join = "inner join user u on (userid=u.id and u.customerid=$customerid)";
copytable($customerid,"job",array("id", "userid", "scheduleid", "jobtypeid", "name", "description", "listid", "phonemessageid", "emailmessageid", "printmessageid", "questionnaireid", "type", "createdate", "startdate", "enddate", "starttime", "endtime", "finishdate", "status", "deleted", "ranautoreport", "priorityadjust", "cancelleduserid"),$db,$custdb,1000,$join);

// JOBSETTINGS
restructureJobOptions($customerid,$db,$custdb,1000,$join);

//JOBLANGUAGE
$join = "inner join job j on (jobid=j.id)
inner join user u on (userid=u.id and u.customerid=$customerid)";
copytable($customerid,"joblanguage",array("id", "jobid", "messageid", "type", "language"),$db,$custdb,1000,$join);

//JOBTYPE
copytable($customerid,"jobtype",array("id", "name", "systempriority", "deleted"),$db,$custdb,1000,false);

//LANGUAGE
copytable($customerid,"language",array("id", "name"),$db,$custdb,1000,false);

//LIST
$join = "inner join user u on (userid=u.id and u.customerid=$customerid)";
copytable($customerid,"list",array("id", "userid", "name", "description", "lastused", "deleted"),$db,$custdb,1000,$join);

//LISTENTRY (rule)
$join = "inner join list l on (listid = l.id)
inner join user u on (userid=u.id and u.customerid=$customerid)
where listentry.type ='R'";
copytable($customerid,"listentry",array("id", "listid", "type", "ruleid", "personid"),$db,$custdb,1000,$join);

//LISTENTRY (add/negate)
$join = "inner join person p on (listentry.personid = p.id and p.customerid=$customerid)
inner join list l on (listid = l.id)inner join user u on (l.userid=u.id and u.customerid=$customerid)
where listentry.type !='R'";
copytable($customerid,"listentry",array("id", "listid", "type", "ruleid", "personid"),$db,$custdb,1000,$join);

//MESSAGE
$join = "inner join user u on (userid=u.id and u.customerid=$customerid)";
copytable($customerid,"message",array("id", "userid", "name", "description", "type", "data", "lastused", "deleted"),$db,$custdb,1000,$join);

//MESSAGEPART
$join = "inner join message m on (messageid = m.id)
inner join user u on (userid=u.id and u.customerid=$customerid)";
copytable($customerid,"messagepart",array("id", "messageid", "type", "audiofileid", "txt", "fieldnum", "defaultvalue", "voiceid", "sequence"),$db,$custdb,1000,$join);

//PERMISSION
$join = "inner join access a on (accessid = a.id and a.customerid=$customerid)";
copytable($customerid,"permission",array("id", "accessid", "name", "value"),$db,$custdb,1000,$join);

//PERSON
copytable($customerid,"person",array("id", "userid", "pkey", "importid", "lastimport", "type", "deleted", "f01", "f02", "f03", "f04", "f05", "f06", "f07", "f08", "f09", "f10", "f11", "f12", "f13", "f14", "f15", "f16", "f17", "f18", "f19", "f20"),$db,$custdb,1000,false);

//PERSONDATAVALUES
copytable($customerid,"persondatavalues",array("id", "fieldnum", "value", "refcount"),$db,$custdb,1000,false);

//PHONE
$join = "inner join person p on (personid = p.id and p.customerid=$customerid)";
$phonefields = array("id", "personid", "phone", "sequence", "editlock");
copytable($customerid,"phone",$phonefields,$db,$custdb,1000,$join);

//REPORTCONTACT
copytable($customerid,"reportcontact",array("jobid", "personid", "type", "sequence", "numattempts", "userid", "starttime", "result", "participated", "duration", "resultdata", "attemptdata", "phone", "email", "addressee", "addr1", "addr2", "city", "state", "zip"),$db,$custdb,1000,false);

//REPORTPERSON
copytable($customerid,"reportperson",array("jobid", "personid", "type", "userid", "messageid", "status", "numcontacts", "numduperemoved", "numblocked", "pkey", "f01", "f02", "f03", "f04", "f05", "f06", "f07", "f08", "f09", "f10", "f11", "f12", "f13", "f14", "f15", "f16", "f17", "f18", "f19", "f20"),$db,$custdb,1000,false);

//RULE (list)
$join = "inner join listentry le on (le.ruleid = rule.id)
inner join list l on (le.listid = l.id)
inner join user u on (l.userid = u.id and u.customerid=$customerid)";
copytable($customerid,"rule",array("id", "logical", "fieldnum", "op", "val"),$db,$custdb,1000,$join);

//RULE (userrule)
$join = "inner join userrule ur on (ur.ruleid = rule.id)
inner join user u on (ur.userid = u.id and u.customerid=$customerid)";
copytable($customerid,"rule",array("id", "logical", "fieldnum", "op", "val"),$db,$custdb,1000,$join);

//SCHEDULE
$join = "inner join user u on (userid=u.id and u.customerid=$customerid)";
copytable($customerid,"schedule",array("id", "userid", "time", "nextrun"),$db,$custdb,1000,$join);

//SCHEDULEDAY table removed, dow field added to schedule table
restructureScheduleDay($customerid, $db, $custdb);

//SPECIALTASK
//dont copy

//SURVEYWEB
copytable($customerid,"surveyweb",array("code", "jobid", "personid", "status", "dateused", "loggedip", "resultdata"),$db,$custdb,1000,false);

//SURVEYQUESTION
$join = "inner join surveyquestionnaire sq on (sq.id = questionnaireid)
inner join user u on (userid=u.id and u.customerid=$customerid)";
copytable($customerid,"surveyquestion",array("id", "questionnaireid", "questionnumber", "webmessage", "phonemessageid", "reportlabel", "validresponse"),$db,$custdb,1000,$join);

//SURVEYQUESTIONNAIRE
$join = "inner join user u on (userid=u.id and u.customerid=$customerid)";
copytable($customerid,"surveyquestionnaire",array("id", "userid", "name", "description", "hasphone", "hasweb", "dorandomizeorder", "machinemessageid", "emailmessageid", "intromessageid", "exitmessageid", "webpagetitle", "webexitmessage", "usehtml", "leavemessage", "deleted"),$db,$custdb,1000,$join);

//SURVEYRESPONSE
$join = "inner join job j on (j.id = jobid)
inner join user u on (userid=u.id and u.customerid=$customerid)";
copytable($customerid,"surveyresponse",array("jobid", "questionnumber", "answer", "tally"),$db,$custdb,1000,$join);

//TTSVOICE
$join = "where 1";
copytable($customerid,"ttsvoice",array("id", "language", "gender"),$db,$custdb,1000,$join);

//USER
copytable($customerid,"user",array("id", "accessid", "login", "password", "accesscode", "pincode", "firstname", "lastname", "phone", "email", "enabled", "lastlogin", "deleted", "ldap"),$db,$custdb,1000,false);

//USERJOBTYPES
$join = "inner join user u on (userid=u.id and u.customerid=$customerid)";
copytable($customerid,"userjobtypes",array("userid", "jobtypeid"),$db,$custdb,1000,$join);

//USERRULE
$join = "inner join user u on (userid=u.id and u.customerid=$customerid)";
copytable($customerid,"userrule",array("userid", "ruleid"),$db,$custdb,1000,$join);

//USERSETTING
$join = "inner join user u on (userid=u.id and u.customerid=$customerid)";
copytable($customerid,"usersetting",array("id", "userid", "name", "value"),$db,$custdb,1000,$join);

//VOICEREPLY
$join = "inner join user u on (userid=u.id and u.customerid=$customerid)";
copytable($customerid,"voicereply",array("id", "personid", "jobid", "userid", "contentid", "replytime", "listened"),$db,$custdb,1000,$join);


?>
