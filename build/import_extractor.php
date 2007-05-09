<?

if ($argc < 2)
	exit ("Please specify customerid");
	
$customerid = $argv[1];

$SETTINGS = parse_ini_file("../inc/settings.ini.php", true);

$authhost="localhost:3306";
$authuser="root";
$authpass="";
$authdb="authserver";

$auth = mysql_connect($authhost, $authuser, $authpass)
			or die("Could not connect to auth: " . mysql_error($authdb));
mysql_select_db($authdb, $auth);



function getImportFileURL ($customerid, $uploadpath, $destfilename = "data.csv") {
	global $SETTINGS;
	$url = "ftp://";
	$url .= $SETTINGS['import']['ftpuser'] . ":" . $SETTINGS['import']['ftppass'];
	$url .= "@" . $SETTINGS['import']['ftphost'] . ":" . $SETTINGS['import']['ftpport'];
	$url .= "/" . $customerid . "/" . $uploadpath . "/$destfilename";

	return $url;
}


$query = "select id, dbhost, dbusername, dbpassword, hostname from customer where id = '$customerid'";
$res = mysql_query($query, $auth);
$customer = mysql_fetch_row($res);


//fetch report data per customer
$custdb = mysql_connect($customer[1], $customer[2], $customer[3])
			or die("Could not connect to customer: " . mysql_error($custdb));
mysql_select_db("c_$customer[0]", $custdb)
			or die("Could not select customer db: " . mysql_error($custdb));

//find import file, pull data out, and populate data field in import

$query = "select id, path from import";
$result = mysql_query($query, $custdb);
$imports = array();
while($row = mysql_fetch_row($result)){
	$imports[] = $row;
}
foreach($imports as $import){
	if($SETTINGS['import']['type'] == 'file'){
		$file = $SETTINGS['import']['filedir'] . $import[1];
	} else if($SETTINGS['import']['type'] == 'ftp'){
		$file = getImportFileUrl($customer[0], $import[0]);
	}
	
	if (is_readable($file) && is_file($file)) {
		echo "Extracting: " . $file . "\n";
		$fp = fopen($file, "r");
		$stream = "";
		while($data = fread($fp, filesize($file))){
			$stream .= $data;
		}
		$query = "update import set data = '" . mysql_real_escape_string($stream,$custdb) . "' where id = '$import[0]'";
		mysql_query($query, $custdb)
			or die("Failed to insert data into import:" . mysql_error($custdb));
	} else {
		echo "File Not Found: " . $file . "\n";
	}
}
	



?>