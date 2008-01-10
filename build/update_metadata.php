<?
$cshost="localhost";
$csuser="_dbuser_";
$cspass="_dbpass_";
$csdb="commsuite";

$db_con = mysql_connect($cshost, $csuser, $cspass)
			or die("Could not connect to db: " . mysql_error($db_con));
mysql_select_db($csdb, $db_con);
				
$query = "update fieldmap fm set fm.options=concat(fm.options, ',firstname') where fm.name = 'First Name'";
mysql_query($query, $db_con);
$query = "update fieldmap fm set fm.options=concat(fm.options, ',lastname') where fm.name = 'Last Name'";
mysql_query($query, $db_con);
$query = "update fieldmap fm set fm.options=concat(fm.options, ',grade') where fm.name = 'Grade' OR fm.name='Grade Level'";
mysql_query($query, $db_con);
$query = "update fieldmap fm set fm.options=concat(fm.options, ',school') where fm.name = 'School' or fm.name='school'";
mysql_query($query, $db_con);
$query = "update fieldmap fm set fm.options=concat(fm.options, ',language') where fm.name = 'Language'";
mysql_query($query, $db_con);



?>