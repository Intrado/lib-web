<?
$commsuitedbname = "commsuite";  //new db name
$olddbname = "dialer"; //old db name
$initpath = "/usr/commsuite/init/";  //path where all init files exist, for linux only


$tmpPathArray = explode("/",$argv[0]);
if (count($tmpPathArray) < 2)
	$tmpPathArray = explode("\\",$argv[0]);

array_pop($tmpPathArray); // remove the php script
array_pop($tmpPathArray); // remove the build directory

$wwwpath = implode('/', $tmpPathArray);

$error = 1;

if(!isset($argv[1])){
	$type = installtype();
	if($type == "upgrade"){
		$answer = confirmmangle();
		if($answer != "y"){
			exit("Exiting");
		}
	}
} else {
	$type = $argv[1];
}

if(isset($argv[2])){
	$dbuser = $argv[2];
	$dbpass = $argv[3];
} else {
	if(stat("../inc/settings.ini.php")){
		$SETTINGS = parse_ini_file("../inc/settings.ini.php",true);
		if(isset($SETTINGS['db']['user'])){
			$dbuser = $SETTINGS['db']['user'];
			$dbpass = $SETTINGS['db']['pass'];
			$error = 0;
		}
	}
	if($error){
		//if file cant be found, prompt user for db connection info
		$confirm = "n";
		while($confirm != "y"){
			echo "\nEnter DB User:\n";
			$dbuser = trim(fread(STDIN, 1024));
			echo "\nEnter DB Pass:\n";
			$dbpass = trim(fread(STDIN, 1024));
			echo "DBUSER: " . $dbuser . "\n";
			echo "DBPASS: " . $dbpass . "\n";
			$confirm = generalMenu(array("Is this information correct?", "y or n"), array("y", "n"));
		}
	}
}

echo "Connecting to mysql\n";
$custdb = mysql_connect("127.0.0.1", $dbuser, $dbpass)
	or die("Failed to connect to database");

if($type == "upgrade"){
	mysql_select_db($olddbname);
	echo "Checking for active jobs\n";
	$res = mysql_query("select count(*) from job where status = 'active'", $custdb);
	$count = mysql_fetch_row($res);
	if($count[0] > 0){
		echo "There are active jobs.  Exiting script.\n";
		exit();
	}
	echo "No active jobs found.\n";
}

if(isset($_ENV['WINDIR'])){
	echo "Windows Machine\n";
	echo "Shutting down services\n";
	if($type == "upgrade")
		$services = array("Apache2", "csDialer", "csTasksync", "csTomcat", "csjtapi");
	else
		$services = array("Apache2", "csRedialer", "csReportServer", "csTomcat", "csjtapi");
	foreach( $services as $service){
		$output = array();
		echo "Stopping $service\n";
		exec("net stop $service", $output);
		echoarray($output);
	}
} else {
	echo "Linux Machine\n";
	echo "Shutting down services\n";
	if($type == "upgrade")
		$services = array("httpd", "dialer", "tasksync", "tomcat", "jtapi");
	else
		$services = array("httpd", "redialer", "reportserver", "tomcat", "jtapi");
	foreach($services as $service){
		$output = array();
		echo "Stopping $service\n";
		exec($initpath . $service . " stop", $output);
		echoarray($output);
	}
}


echo "Creating database\n";
mysql_query("create database $commsuitedbname",$custdb)
	or die ("Failed to create new DB $commsuitedbname : " . mysql_error($custdb));
mysql_select_db($commsuitedbname,$custdb);
echo "$commsuitedbname has been created\n";

echo "Creating new tables, triggers, and procedures\n";

executeSqlFile("$wwwpath/db/commsuite.sql", true);

mysql_query("INSERT INTO `shard` VALUES (1,'commsuite','commsuite','localhost','" . $dbuser ."','" . $dbpass ."')");

mysql_query("INSERT INTO `customer` VALUES (1,1,'default','0000000000','" . $dbuser ."','" . $dbpass . "','','2007-08-23 18:49:30',1)");

if($type == "new"){
	executeSqlFile("$wwwpath/db/commsuitedefaults.sql");
	echo "Defaults loaded.\n";
} else if($type == "upgrade"){
	mysql_select_db($olddbname);
	mysql_query("delete from setting where name = 'checkpassword'");

	echo "Mangling\n";
	executeSqlFile("$wwwpath/db/commsuitemangle.sql");

	echo "Running Import Updater\n";
	$output=array();
	$returncode=1;
	exec("php import_extractor.php", $output, $returncode);
	if($returncode)
		exit("Error occurred with the importextractor");

	echo "Extracting old database to new database\n";
	$output = array();
	$returncode=1;
	exec("php extract_customer.php", $output, $returncode);
	echoarray($output);
	if($returncode)
		exit("Error occurred with the extractor");

	mysql_select_db($commsuitedbname, $custdb);

	echo "Cleaning up the database\n";
	executeSqlFile("$wwwpath/db/database_cleanup.sql");

	echo "Adding New Defaults\n";
	addNewDefaults();

	echo "Updating metadata fields\n";
	exec("php update_metadata.php");

	echo "Updating Settings File\n";
	$output = array();
	exec("php create_new_settings.php", $output);
	echoarray($output);

}

echo "Done.\n";


function executeSqlFile($sqlfile, $replace = false){
	global $custdb;
	$tablequeries = explode("$$$",file_get_contents($sqlfile));
	foreach ($tablequeries as $tablequery) {
		if (trim($tablequery)) {
			if($replace){
				$tablequery = str_replace('_$CUSTOMERID_', 1, $tablequery);
			}
			mysql_query($tablequery,$custdb)
				or die ("Failed to create tables \n$tablequery\n\n : " . mysql_error($custdb));
		}
	}
}

function confirmmangle(){
	$questions = array();
	$questions[] = "The old db will be mangled to convert data and then extracted to the new db.";
	$questions[] = "If you did not back-up the database, hit n";
	$questions[] = "Are you sure you want to continue? y or n";
	return generalmenu($questions, array("y", "n"));
}

function installtype(){
	$questions = array();
	$questions[] = "Is this 'new' or 'upgrade'?";
	return generalmenu($questions, array("new", "upgrade"));
}
function echoarray($somearray){
	foreach($somearray as $line){
		echo $line . "\n";
	}
}

function generalmenu($questions = array(), $validresponses = array()){
	echoarray($questions);
	$response = fread(STDIN, 1024);
	$response = trim($response);
	while(!in_array($response, $validresponses)){
		echo "\nThat was not an option\n";
		$response = fread(STDIN, 1024);
		$response = trim($response);
	}
	return $response;
}

function addNewDefaults(){
	global $custdb;

	echo "Adding new default values\n";

	mysql_query("BEGIN", $custdb);

	$query = "INSERT INTO `jobtype` (name, systempriority, info, issurvey,deleted) VALUES ('Survey',3,'Surveys',1,0)";
	mysql_query($query, $custdb)
		or die("Failed to run this query: " . $query . "\n, with this error " . mysql_error($custdb));

	$res = mysql_query("select value from setting where name = 'maxphones'", $custdb);
	$row = mysql_fetch_row($res);
	if($row[0]){
		$maxphones = $row[0];
	} else {
		$maxphones = 3;
	}

	$res = mysql_query("select value from setting  where name = 'maxemails'", $custdb);
	$row = mysql_fetch_row($res);
	if($row[0]){
			$maxemails = $row[0];
		} else {
			$maxemails = 2;
	}

	$res = mysql_query("select id, systempriority from jobtype where not deleted", $custdb);
	$jobtypes= array();
	while($row = mysql_fetch_row($res)){
		$jobtypes[$row[0]] = $row[1];
	}

	$desttype = array("phone" => $maxphones,
						"email" => $maxemails);
	$values = array();
	foreach($desttype as $type => $max){
		foreach($jobtypes as $index => $jobtypepriority){
				mysql_query("delete from jobtypepref where jobtypeid = '" . $index . "'", $custdb);
			for($i=0; $i < $max; $i++){
				$enabled = 0;
				switch($jobtypepriority){
					case "1":
						$enabled = 1;
						break;
					default:
						if($i < 1)
							$enabled = 1;
						else
							$enabled = 0;
						break;
				}
				$values[] = "('" . $index . "','" . $type . "','" . $i . "','" . $enabled . "')";
			}
		}
	}
	mysql_query("INSERT INTO jobtypepref (jobtypeid, type, sequence, enabled) VALUES ". implode(",", $values), $custdb);

	mysql_query("UPDATE jobtype set info = 'General Announcements' where not deleted and not issurvey and systempriority = '3'", $custdb);
	mysql_query("UPDATE jobtype set info = 'Time Critical Announcements' where not deleted and not issurvey and systempriority = '2'", $custdb);
	mysql_query("UPDATE jobtype set info = 'Emergencies Only' where not deleted and not issurvey and systempriority = '1'", $custdb);
	mysql_query("UPDATE jobtype set info = 'Surveys' where not deleted and issurvey and systempriority = '3'", $custdb);


	mysql_query("insert into content (contenttype, data) values ('image/gif', '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')", $custdb);
	$logocontentid= mysql_insert_id();

	mysql_query("insert into setting (name, value) values ('_logocontentid', '" . $logocontentid . "')", $custdb);

	mysql_query("insert into content (contenttype, data) values ('image/jpg', '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')", $custdb);
	$loginpicturecontentid = mysql_insert_id();
	mysql_query("insert into setting (name, value) values ('_loginpicturecontentid', '" . $loginpicturecontentid . "')", $custdb);

	mysql_query("insert into setting (name, value) values ('_productname', 'SchoolMessenger'), ('_brandtheme', '3dblue'), ('_brandtheme1', '89A3CE'), ('_brandtheme2', '89A3CE'), ('_brandprimary', '26477D'), ('_brandratio', '.3'), ('_supportphone', '8009203897'), ('_supportemail', 'support@schoolmessenger.com')", $custdb);

	mysql_query("COMMIT", $custdb);

}
?>
