<?
/*
	Simple php script to create X customers and import default data from base_customer.sql
	requires customer.sql to be local

	query used to dump default db:
	mysqldump --no-create-db --no-create-info --single-transaction -u root -opt --skip-triggers c_4 > base_customer.sql
	settings table insert moved to top of sql file because triggers read data from it

*/

function genpassword() {
	$digits = 15;
	$passwd = "";
	$chars = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz";
	while ($digits--) {
		$passwd .= $chars[mt_rand(0,strlen($chars)-1)];
	}
	return $passwd;
}

//Sets how many customers you want to generate
$count=1;


$base_customer_sql = "base_customer.sql";
$customer_schema = "../db/customer.sql";
$authserverdb="authserver";
$authserverhost="localhost";
$authserveruser="root";
$authserverpass="";

$shardid=1;


if(!file_exists($customer_schema)){
	exit("Cannot open customer.sql file");
}

if(!file_exists($base_customer_sql)){
	exit("Cannot open base_customer.sql file");
}

//First connect to authserver and fetch data for shard X
$auth_con = mysql_connect($authserverhost, $authserveruser, $authserverpass, true);
mysql_select_db($authserverdb, $auth_con);

$res = mysql_query("select dbhost, dbusername, dbpassword from shard where id = " . $shardid);
$row = mysql_fetch_row($res);

$shardhost = $row[0];
$sharduser = $row[1];
$shardpass = $row[2];

//second connect to the shard and create a customer just like new customer
$shard_con = mysql_connect($shardhost, $sharduser, $shardpass, true);
for($i=0; $i < $count; $i++){
	$dbpassword = genpassword();

	mysql_query("insert into customer (shardid, dbpassword,enabled) values ('$shardid', '$dbpassword', '1')", $auth_con )
			or die("failed to insert customer into auth server");
	$customerid = mysql_insert_id($auth_con);
	echo "Inserted customer id: " . $customerid . "\n";

	$newdbname = "c_$customerid";
	mysql_query("update customer set dbusername = '" . $newdbname . "', urlcomponent = 'customer" . $customerid . "' where id = '" . $customerid . "'", $auth_con);

	mysql_query("create database $newdbname",$shard_con)
		or die ("Failed to create new DB $newdbname : " . mysql_error($shard_con));
	mysql_select_db($newdbname, $shard_con)
		or die("Error selecting new database:" . mysql_error($shard_con));

	mysql_query("drop user '$newdbname'", $shard_con); //ensure mysql credentials match our records, which it won't if create user fails because the user already exists
	mysql_query("create user '$newdbname' identified by '$dbpassword'", $shard_con);
	mysql_query("grant select, insert, update, delete, create temporary tables, execute on $newdbname . * to '$newdbname'", $shard_con);



	//third, create tables using base schema
	$tablequeries = explode("$$$",file_get_contents($customer_schema));
	foreach ($tablequeries as $tablequery) {
		if (trim($tablequery)) {
			$tablequery = str_replace('_$CUSTOMERID_', $customerid, $tablequery);
			mysql_query($tablequery,$shard_con)
				or die ("Failed to execute statement \n$tablequery\n\nfor $newdbname : " . mysql_error($shard_con));
		}
	}
	echo "Generated customer: " . $newdbname . ", on Shard: " . $shardid . "\n";



	//fourth instead of inserting defaults, insert base customer sql
	$base_customer_sql_contents = explode("$$$",file_get_contents($base_customer_sql));
	foreach ($base_customer_sql_contents as $query) {
		if (trim($query)) {
			$tablequery = str_replace('_$CUSTOMERID_', $customerid, $query);
			mysql_query($query,$shard_con)
				or die ("Failed to execute statement \n$query\n\nfor $newdbname : " . mysql_error($shard_con));
		}
	}
	mysql_query("update setting set value='customer" . $customerid . "' where name = 'displayname'",$shard_con);
	echo "Finished inserting base sql for Customer " . $newdbname . "\n";
}
//last, profit



?>