<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("common.inc.php");
require_once("XML/RPC.php");
require_once("authclient.inc.php");

////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////

if (!$MANAGERUSER->authorized("editcustomer"))
	exit("Not Authorized");

if(isset($_GET['searchtxt'])) {
	$langfilepath = '../inc/lsr-language-utf8.txt';

	$langfp = fopen($langfilepath, 'r') or die("Can't open input file \"$langfilepath\"\n");
	$count = 0;
	$result = array();
	
	while (($data = fgetcsv($langfp,null,'	')) !== FALSE) {
		if(!isset($data[0]))
			exit("Unable to read the first column on line " . ($count + 1) ."\n");
		if(!isset($data[1]))
			exit("Unable to read the second column on line " . ($count + 1) ."\n");
		if(!isset($data[2]))
			exit("Unable to read the third column on line " . ($count + 1) ."\n");

		
		if(strtolower(trim($_GET['searchtxt'])) )
		if(stripos($data[2],strtolower(trim($_GET['searchtxt']))) !== false) {
			$result[$data[0]] = $data[2];
			$count++;
		}
	}

	header('Content-Type: application/json');
	echo json_encode($count?$result:false);
	exit(0);
}

