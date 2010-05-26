<?
// Use this to extract data from an asp customer so that you have something to run the shard_migrate script with

//settings

//authserver db info
$authhost = "10.25.25.68";
$authuser = "root";
$authpass = "";

$customerid = 1;

//----------------------------------------------------------------------

$SETTINGS = parse_ini_file("../inc/settings.ini.php",true);

require_once("../inc/db.inc.php");
require_once("../inc/DBMappedObject.php");
require_once("../inc/DBRelationMap.php");

//----------------------------------------------------------------------

//connect to authserver db
echo "connecting to authserver db\n";
$authdb = DBConnect($authhost,$authuser,$authpass,"authserver");

echo "connecting to source shard\n";
$query = "select dbhost,dbusername,dbpassword from shard where id=(select shardid from customer where id=$customerid)";
list($srchost,$srcuser,$srcpass) = QuickQueryRow($query,false,$authdb) or die("Can't query shard info:" . errorinfo($authdb)); 
$srcsharddb = DBConnect($srchost,$srcuser,$srcpass,"aspshard") or die("Can't connect to shard:" . $srchost);

//sanity checks
echo "doing sanity checks\n";

//customer has active jobs
$query = "select count(*) from qjob where status in ('processing', 'procactive', 'active') and customerid=$customerid";
if (QuickQuery($query,$srcsharddb))
	die("There are active jobs!");

//max last login
if (QuickQuery("select max(lastlogin) > now() - interval 1 hour from c_$customerid.user where login != 'schoolmessenger'",$srcsharddb))
	die("A user has logged in less than an hour ago!");

//customer db exists on source
if (!QuickQuery("show databases like 'c_$customerid'",$srcsharddb))
	die("Customer databases doesn't exist on source shard");

//----------------------------------------------------------------------

//backup the existing customer db
$backupfilename = "c_$customerid.xfer.sql";
$cmd = "nice mysqldump -h $srchost -u $srcuser -p$srcpass --no-create-info --skip-triggers c_$customerid > $backupfilename";
echo "Backing up customer data\n$cmd\n";
$result = exec($cmd,$output,$retval);

if ($retval != 0)
	die("Problem backing up data for transfer\n" . implode("\n",$output));
	
	
?>
