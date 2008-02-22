<?
$commsuitedbname = "commsuite";  //new db name
$olddbname = "dialer"; //old db name
$initpath = "/usr/commsuite/init/";  //path where all init files exist, for linux only

$error = 1;

if(!$argv[1]){
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

if($argv[2]){
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

executeSqlFile("commsuite.sql", true);

mysql_query("INSERT INTO `shard` VALUES (1,'commsuite','commsuite','localhost','" . $dbuser ."','" . $dbpass ."')");

mysql_query("INSERT INTO `customer` VALUES (1,1,'default','0000000000','" . $dbuser ."','" . $dbpass . "','','2007-08-23 18:49:30',1)");

if($type == "new"){
	executeSqlFile("commsuitedefaults.sql");
	echo "Defaults loaded.\n";
	echo "Updating Settings File\n";
	$output = array();
	exec("php create_new_settings.php", $output);
} else if($type == "upgrade"){
	mysql_select_db($olddbname);
	mysql_query("delete from setting where name = 'checkpassword'");

	echo "Mangling\n";
	executeSqlFile("commsuitemangle.sql");

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
	addNewDefaults();

	echo "Updating metadata fields\n";
	exec("php update_metadata.php");

	echo "Updating Settings File\n";
	$output = array();
	exec("php create_new_settings.php", $output);
	echoarray($output);
	echo "Cleaning up the database\n";
	executeSqlFile("database_cleanup.sql");
}

echo "Done.\n";


function executeSqlFile($sqlfile, $replace = false){
	global $custdb;
	$tablequeries = explode("$$$",file_get_contents("../db/" . $sqlfile));
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

	mysql_query("BEGIN");

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

	mysql_query("UPDATE jobtype set info = 'General Announcements' where not deleted and not issurvey and systempriority = '3'");
	mysql_query("UPDATE jobtype set info = 'Time Critical Announcements' where not deleted and not issurvey and systempriority = '2'");
	mysql_query("UPDATE jobtype set info = 'Emergencies Only' where not deleted and not issurvey and systempriority = '1'");
	mysql_query("UPDATE jobtype set info = 'Surveys' where not deleted and issurvey and systempriority = '3'");

	mysql_query("COMMIT");

}
?>