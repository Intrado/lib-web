<?

$SETTINGS = parse_ini_file("../inc/settings.ini.php", true);

$dbhost="localhost:3306";
$dbuser="root";
$dbpass="";
$db="dialerasp";

$custdb = mysql_connect($dbhost, $dbuser, $dbpass)
			or die("Could not connect to db: " . mysql_error($authdb));
mysql_select_db($db, $custdb);



function getImportFileURL ($customerid, $uploadpath, $destfilename = "data.csv") {
	global $SETTINGS;
	$url = "ftp://";
	$url .= $SETTINGS['import']['ftpuser'] . ":" . $SETTINGS['import']['ftppass'];
	$url .= "@" . $SETTINGS['import']['ftphost'] . ":" . $SETTINGS['import']['ftpport'];
	$url .= "/" . $customerid . "/" . $uploadpath . "/$destfilename";

	return $url;
}

//find import file, pull data out, and populate data field in import

$query = "select id, path, customerid from import";
$result = mysql_query($query, $custdb);
$imports = array();
while($row = mysql_fetch_row($result)){
	$imports[] = $row;
}
foreach($imports as $import){
	if($SETTINGS['import']['type'] == 'file'){
		$file = $SETTINGS['import']['filedir'] . $import[1];
	} else if($SETTINGS['import']['type'] == 'ftp'){
		$file = getImportFileUrl($import[2], $import[0]);
	}
	
	if (is_readable($file) && is_file($file)) {
		echo "Extracting: " . $file . "\n";
		$stream = file_get_contents($file);
		$query = "update import set data = '" . mysql_real_escape_string($stream,$custdb) . "' where id = '$import[0]'";
		mysql_query($query, $custdb)
			or die("Failed to insert data into import:" . mysql_error($custdb));
	} else {
		echo "File Not Found: " . $file . "\n";
	}
}
	



?>