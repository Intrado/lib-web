<?
/**
 * Run a snippet of php against all or a subset of customer ids
 * Requires a class which implements the ApplyCustomerCallable interface with the same name as it's filename
 *
 * USAGE: php apply_customer_php.php <php class>.php [<cid1> ... <cidN>]
 */
//
////////////////////////////////
// authserver variables must be set!!!
$authhost="localhost:3306";
$authuser="root";
$authpass="";
$authdb="authserver";
////////////////////////////////

// must supply sql filename as first argument
if($argc < 2)
	exit ("Please specify the file with php class you would like to execute.");

array_shift($argv); //remove argv[0] which is this script name
$phpClassFile = array_shift($argv); //remove argv[1] which is the php class filename

require_once($phpClassFile);

// connect to authserver db
$auth = mysql_connect($authhost, $authuser, $authpass)
			or die("Could not connect to auth: " . mysql_error($authdb));
mysql_select_db($authdb, $auth);

// customer db connection data
$data = array();

// if no more params, or the next param is 'all'
if (!count($argv) || $argv[0] == "all") {
	//all mode
	$query = "select c.id, s.dbhost, s.dbusername, s.dbpassword from shard s inner join customer c on (c.shardid = s.id)  order by c.id";
	$res = mysql_query($query, $auth);
	while($row = mysql_fetch_row($res)){
		$data[] = $row;
	}
} else {
	//customer id list
	while (count($argv)) {
		$arg = array_shift($argv);
		if ($arg + 0 == 0) {
			echo "invalid id: $arg \n";
			continue;
		}
		$cid = $arg + 0;
		// find db connection
		$query = "select c.id, s.dbhost, s.dbusername, s.dbpassword from shard s inner join customer c on (c.shardid = s.id) where c.id=$cid";
		$res = mysql_query($query, $auth);
		if (!$res || mysql_num_rows($res) == 0) {
			echo "invalid id: $cid \n";
			continue;
		}
		$data[] = mysql_fetch_row($res); // there should only be one row for a single customer id
	}
}

// now apply sql to each customer
foreach($data as $customer){
	$dsn = 'mysql:dbname=c_'.$customer[0].';host='.$customer[1];
	$custdb = new PDO($dsn, $customer[2], $customer[3]);
	$custdb->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);

	// TODO set charset
	$setcharset = "SET character_set_results = 'utf8', character_set_client = 'utf8', character_set_connection = 'utf8', character_set_database = 'utf8', character_set_server = 'utf8'";
	$custdb->query($setcharset);
	
	printf("Doing % 5d: ",$customer[0]);

	$phpClassName = basename($phpClassFile, '.php');

	QuickQuery("BEGIN", $custdb);

	$theClass = new $phpClassName($custdb);
	$theClass->call();

	QuickQuery("COMMIT", $custdb);

	echo "\n";
}

?>
