<?
require_once("../inc/DBMappedObject.php");
require_once("../obj/Phone.obj.php");

////////////////////////////////
// authserver variables must be set!!!
$authhost="localhost:3306";
$authuser="root";
$authpass="";
$authdb="authserver";
////////////////////////////////

array_shift($argv); //remove argv[0] which is this script name

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



// now fix each customer
foreach ($data as $customer) {
	$custdb = mysql_connect($customer[1], $customer[2], $customer[3])
				or die("Could not connect to customer: " . mysql_error($custdb));
	mysql_select_db("c_$customer[0]", $custdb)
				or die("Could not select customer db: " . mysql_error($custdb));

	printf("Doing % 5d: ",$customer[0]);
	
	$tablenames = array('setting', 'jobsetting', 'usersetting');
	foreach ($tablenames as $tablename) {
		echo ".";
		$res = mysql_query("select id, value from $tablename where name='callerid' and length(value)>10", $custdb);
		if (!$res) continue;
		while ($row = mysql_fetch_row($res)) {
			mysql_query("update $tablename set value='".Phone::parse($row[1])."' where id=".$row[0], $custdb);
		}
	}
	echo "\n";
}

?>
