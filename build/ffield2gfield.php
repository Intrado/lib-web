<?
// Move customer Ffield mapping to a Gfield

$ffield = "f05";
$gfield = "g01";
$gfieldnum = 1;

$customerdbname = "c_9999";
$dbhost = "localhost";
$dbuser = "root";
$dbpass = "";


// connect
echo "Connecting to mysql customer database $customerdbname \n";
$custdb = mysql_connect($dbhost, $dbuser, $dbpass)
	or die("FAILURE: unable connect to database\n");

mysql_select_db($customerdbname);
echo "connection ok\n";

// verify data
echo "Verifying existing data is ok to move\n";

$query = "select count(*) from messagepart where fieldnum='$ffield'";
$res = mysql_query($query)
	or die("Failed to execute: $query ".mysql_error());
$count = mysql_fetch_row($res);
if ($count[0] > 0) die ("FAILURE: messagepart exists with this ffield");

// verify ffield exists and is multisearch
$query = "select count(*) from fieldmap where fieldnum='$ffield' and options like '%multisearch%' and options like '%searchable%' and options not like '%grade%'";
$res = mysql_query($query)
	or die("Failed to execute: $query ".mysql_error());
$count = mysql_fetch_row($res);
if ($count[0] != 1) die ("FAILURE: missing searchable multiselect field $ffield");

// verify gfield does not exist
$query = "select count(*) from fieldmap where fieldnum='$gfield'";
$res = mysql_query($query)
	or die("Failed to execute: $query ".mysql_error());
$count = mysql_fetch_row($res);
if ($count[0] > 0) die ("FAILURE: $gfield already exists");

// verify no groupdata for gfield
$query = "select count(*) from groupdata where fieldnum=$gfieldnum";
$res = mysql_query($query)
	or die("Failed to execute: $query ".mysql_error());
$count = mysql_fetch_row($res);
if ($count[0] > 0) die ("FAILURE: groupdata exists for $gfield");

echo "Moving Ffield to Gfield\n";


$query = "insert into reportgroupdata (fieldnum, jobid, personid, value) select $gfieldnum, jobid, personid, $ffield from reportperson where $ffield!=''";
$res = mysql_query($query)
	or die("Failed to execute: $query ".mysql_error());

$query = "update reportperson set $ffield=''";
$res = mysql_query($query)
	or die("Failed to execute: $query ".mysql_error());

$query = "insert into groupdata (fieldnum, personid, value, importid) select $gfieldnum, id, $ffield, importid from person where importid is not null and $ffield!=''";
$res = mysql_query($query)
	or die("Failed to execute: $query ".mysql_error());

$query = "update person set $ffield=''";
$res = mysql_query($query)
	or die("Failed to execute: $query ".mysql_error());

$query = "update persondatavalues set fieldnum='$gfield' where fieldnum='$ffield'";
$res = mysql_query($query)
	or die("Failed to execute: $query ".mysql_error());

$query = "update importfield set mapto='$gfield' where mapto='$ffield'";
$res = mysql_query($query)
	or die("Failed to execute: $query ".mysql_error());

$query = "update rule set fieldnum='$gfield' where fieldnum='$ffield'";
$res = mysql_query($query)
	or die("Failed to execute: $query ".mysql_error());

$query = "update fieldmap set fieldnum='$gfield' where fieldnum='$ffield'";
$res = mysql_query($query)
	or die("Failed to execute: $query ".mysql_error());



echo "SUCCESS\n";

?>
