<?

//establish a DB connection to the dmapi db
//remember we can't mix DB functions like DBSafe,Query, etc and need to specify the connection handler
//is there an alternate DB to use for the dmapi?
if (isset($SETTINGS['dmapidb']['host'])) {
	$db = $SETTINGS['dmapidb'];

	if ($db['persistent'])
		$dmapidb = mysql_pconnect($db['host'], $db['user'], $db['pass']);
	else
		$dmapidb = mysql_connect($db['host'], $db['user'], $db['pass']);
	if (!$dmapidb) {
		error_log("Problem connecting to MySQL server at " . $db['host'] . " error:" . mysql_error());
?>
		<error>Something happened connecting to the DB</error>
<?
		return;
	}

	if (!mysql_select_db($db['db'])) {
		error_log("Problem selecting databse for " . $db['host'] . " error:" . mysql_error());
?>
		<error>Something happened connecting to the DB</error>
<?
		return;
	}
} else {
	global $_dbcon;
	$dmapidb = $_dbcon; //just set it to the same as the default DB connection
}
?>