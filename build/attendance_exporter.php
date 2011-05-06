<?
//report data exporter

$outputfile="attendance_result.csv";

$dbhost="localhost";
$dbusername="cswww";
$dbpassword="";

$logfile="attendance_exporter.log";

if(!$logfp = fopen($logfile, "a")){
	wlog("Failed to open log file, exiting");
	exit();
}

if(!$outfp = fopen($outputfile, "w")){
	wlog("Failed to open output file, exiting");
	exit();
}

if(count($argv) != 3){
	wlog("Usage: php attendance_exporter.php *jobtype name* *date*");
	wlog("Ex: php attendance_exporter.php attendance yesterday");
	exit();
}

$jobtypename = strtolower($argv[1]);
$date = $argv[2];

wlog("arguments: $jobtypename, $date");

//Connect to DB
if(!$db_con = mysql_connect($dbhost, $dbusername, $dbpassword)){
	wlog("Failed to connect to db: " . mysql_error());
	exit();
}
mysql_select_db("commsuite", $db_con);


//get field number for first name
$firstnameresult = mysql_query("select fieldnum from fieldmap where options like '%firstname%'",$db_con);
$fnrow = mysql_fetch_row($firstnameresult);
if(!$fnrow[0]){
	$firstname = "f01";
} else {
	$firstname = $fnrow[0];
}

//get field number for last name
$lastnameresult = mysql_query("select fieldnum from fieldmap where options like '%lastname%'",$db_con);
$lnrow = mysql_fetch_row($lastnameresult);
if(!$lnrow[0]){
	$lastname = "f02";
} else {
	$lastname = $lnrow[0];
}
//get jobtype id
$jobtyperesult = mysql_query("select id from jobtype where name = '" . mysql_escape_string($jobtypename) . "'",$db_con);
$jtrow = mysql_fetch_row($jobtyperesult);
if(!$jtrow[0]){
	wlog("JobType does not exist, exiting");
	exit();
} else {
	$jobtypeid = $jtrow[0];
}


//get job list to search on index
$startdate = date("Y-m-d", strtotime($date));
$enddate = date("Y-m-d", strtotime($date));

$joblistquery = "select j.id from job j
						where 1
						and
							(
								(j.startdate >= '$startdate' and j.startdate < date_add('$enddate',interval 1 day))
								or
								(ifnull(j.finishdate, j.enddate) >= '$startdate' and ifnull(j.finishdate, j.enddate) < date_add('$enddate',interval 1 day))
							)
						and j.jobtypeid = $jobtypeid
						and j.status in ('complete', 'active')";
//echo $joblistquery . "\n";

$jobidarray = array();
$jobidresult = mysql_query($joblistquery,$db_con);
while($row = mysql_fetch_row($jobidresult)){
	$jobidarray[] = $row[0];
}
$jobidlist = implode("','", $jobidarray);

//main Query
$query="select SQL_CALC_FOUND_ROWS
			rp.pkey,
			rp." . $firstname . " as firstname,
			rp." . $lastname . " as lastname,
			mg.name as messagename,
			rc.phone,
			rc.numattempts,
			from_unixtime(rc.starttime/1000) as lastattempt,
			coalesce(rc.result, rp.status) as result
			from reportperson rp
			inner join job j on (rp.jobid = j.id)
			left join	reportcontact rc on (rc.jobid = rp.jobid and rc.type = rp.type and rc.personid = rp.personid)
			left join	messagegroup mg on
							(mg.id = j.messagegroupid)
			where 1
			and rp.jobid in ('" . $jobidlist . "')
			and date(from_unixtime(rc.starttime/1000)) >= $startdate
			and date(from_unixtime(rc.starttime/1000)) < date_add('$enddate',interval 1 day)
			and rp.type = 'phone'
			order by rp." . $firstname . ", rp." . $lastname .", rp.pkey";

//echo $query . "\n";

$result = mysql_query($query, $db_con) or die("Error in main query: " . mysql_error());
$data = array();
while($row = mysql_fetch_row($result)){
	$row[7] = fmt_result($row[7]);
	$data[] = $row;
}

foreach($data as $line){
	writeLine($outfp, $line);
}

fclose($outfp);
fclose($logfp);


//Functions

function writeLine ($stufile, $row) {
	$sisline = '"' . implode('","',$row) . "\"\n";
	if (!fwrite($stufile,$sisline)) {
		fclose($stufile);
		wlogDie("failed to write student line to import file: $sisline");
	}
}

function wlog ($str) {
	global $logfp;
	echo $str . "\n";
	fwrite($logfp, date("Y-m-d H:i:s") . " - $str\r\n");
}

function fmt_result ($result) {
	switch($result) {
		case "A":
			return "Answered";
		case "M":
			return "Machine";
		case "B":
			return "Busy";
		case "N":
			return "No Answer";
		case "X":
			return "Disconnect";
		case "F":
			return "Unknown";
		case "C":
			return "In Progress";
		case "blocked":
			return "Blocked";
		case "duplicate":
			return "Duplicate";
		case "nocontacts":
			return "No Contacts";
		case "Sent":
			return "Sent";
		case "unent":
			return "Unsent";
		case "notattempted":
			return "Not Attempted";
		case "undelivered":
			return "Not Contacted";
		case "declined":
			return "No Destination Selected";
		case "confirmed":
			return "Confirmed";
		case "notconfirmed":
			return "Not Confirmed";
		case "noconfirmation":
			return "No Confirmation Response";
		default:
			return ucfirst($row[$index]);
	}
}
?>