<?
$commsuitedbname = "commsuite";  //new db name
$olddbname = "dialer"; //old db name
$initpath = "/commsuite/init/";  //path where all init files exist, for linux only

if($argc < 2)
	exit("Usage: dbuser, dbpassword");
	
$type = installtype();
$type = trim($type);
if(!in_array($type, array("upgrade", "new"))){
	exit("That was not an option");
}

$dbuser = $argv[1];
$dbpass = $argv[2];
echo "Connecting to mysql\n";
$custdb = mysql_connect("127.0.0.1", $dbuser, $dbpass);

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

$system = systemtype();
$system = trim($system);

if($system == "linux"){
	echo "Shutting down services\n";
	foreach(array("httpd", "dialer", "tasksync", "tomcat", "jtapi") as $service){
		exec($initpath . $service . " stop");
	}
} elseif($system == "windows"){
	echo "Shutting down services\n";
	$output = array();
	foreach(array("Apache2", "csDialer", "csTasksync", "csTomcat", "csjtapi") as $service){
		echo "Stopping $service\n";
		exec("net stop $service", $output);
		echoarray($output);
	}
} else {
	exit("That was not an option\n");
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
	$answer = confirmmangle();
	$answer = trim($answer);
	if($answer == "y"){
		mysql_select_db($olddbname);
		echo "Mangling\n";
		executeSqlFile("commsuitemangle.sql");
		echo "Extracting old database to new database\n";
		$output = array();
		exec("php extract_customer.php", $output);
		echoarray($output);
		echo "Old database extracted\n";
	} else {
		exit("Exiting.\n");
	}
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
	echo "Mangling old db to convert data and then extracting to new db.\n";
	echo "If you did not back-up the database, hit n\n";
	echo "Are you sure you want to continue? y or n\n";
	return fread(STDIN, 1024);
}

function systemtype(){
	echo "Is this a 'windows' or 'linux'?\n";
	return fread(STDIN, 1024);
}

function installtype(){
	echo "Is this 'new' or 'upgrade'?\n";
	return fread(STDIN, 1024);
}
function echoarray($somearray){
	foreach($somearray as $line){
		echo $line . "\n";
	}
}
?>