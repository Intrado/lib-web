<?
if($argc < 2)
	exit ("Please specify the file with sql you would like to apply.");

$sqlqueries = explode("$$$",file_get_contents($argv[1]));

$authhost="localhost:3306";
$authuser="root";
$authpass="";
$authdb="authserver";

$auth = mysql_connect($authhost, $authuser, $authpass)
			or die("Could not connect to auth: " . mysql_error($authdb));
mysql_select_db($authdb, $auth);

$query = "select c.id, s.dbhost, s.dbusername, s.dbpassword from shard s inner join customer c on (c.shardid = s.id) order by c.id";
$res = mysql_query($query, $auth);
$data = array();
while($row = mysql_fetch_row($res)){
	$data[] = $row;
}

foreach($data as $customer){
	$custdb = mysql_connect($customer[1], $customer[2], $customer[3])
				or die("Could not connect to customer: " . mysql_error($custdb));
	mysql_select_db("c_$customer[0]", $custdb)
				or die("Could not select customer db: " . mysql_error($custdb));

	printf("Doing % 5d: ",$customer[0]);
	foreach ($sqlqueries as $sqlquery) {

		echo ".";
		if (trim($sqlquery)){
			$sqlquery = str_replace('_$CUSTOMERID_', $customer[0], $sqlquery);
			mysql_query($sqlquery,$custdb)
				or die ("Failed to execute sql: " . mysql_error($custdb));
		}
	}
	echo "\n";
}

?>
