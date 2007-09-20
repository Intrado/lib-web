<?
$commsuitedbname = "commsuite";  //new db name
$olddbname = "dialer"; //old db name
$initpath = "/usr/commsuite/init/";  //path where all init files exist, for linux only

if(stat("../inc/settings.ini.php")){
	$SETTINGS = parse_ini_file("../inc/settings.ini.php",true);
	$dbuser = $SETTINGS['db']['user'];
	$dbpass = $SETTINGS['db']['pass'];
} else {
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

$type = installtype();
if($type == "upgrade"){
	$answer = confirmmangle();
	if($answer != "y"){
		exit("Exiting");
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
	$output = array();
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
		$services = array("httpd", "dialer", "tasksync", "tomcat", "jtapi")
	else
		$services = array("httpd", "redialer", "reportserver", "tomcat", "jtapi")
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

mysql_query("INSERT INTO `customer` VALUES (1,1,'default','','" . $dbuser ."','" . $dbpass . "','','2007-08-23 18:49:30',1)");

if($type == "new"){
	executeSqlFile("commsuitedefaults.sql");
	echo "Defaults loaded.\n";
} else if($type == "upgrade"){
	mysql_select_db($olddbname);
	echo "Mangling\n";
	executeSqlFile("commsuitemangle.sql");
	echo "Extracting old database to new database\n";
	$output = array();
	exec("php extract_customer.php", $output);
	echoarray($output);
	echo "Old database extracted\n";
	echo "Updating Settings File\n";
	$output = array();
	exec("php create_new_settings.php", $output);
	echoarray($output);
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
				or die ("Failed to create tables \n$tablequery\n\nfor $newdbname : " . mysql_error($custdb));
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
?>