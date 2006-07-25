<?
include_once("common.inc.php");




if ($_POST['submit']) {
	if ($_POST['code'] != "joplin555") exit("bad code");
	if ($_POST['password'] != $_POST['password']) exit("passwords do not match");
	if (QuickQuery("select count(*) from user where login='" . DBSafe($_POST['user']) . "' and deleted=0") > 0)
		exit("username exists");

	$query = "insert into customer (name,enabled,timezone,hostname) values ('$_POST[name]',1,'$_POST[timezone]','$_POST[hostname]')";
	QuickUpdate($query) or die( "ERROR:" . mysql_error() . " SQL:" . $query);
	$custid = mysql_insert_id();

	$query = "insert into access (name,customerid) values ('System Administrators',$custid)";
	QuickUpdate($query) or die( "ERROR:" . mysql_error());
	$accessid = mysql_insert_id();

	$query = "INSERT INTO `permission` (accessid,name,value) VALUES "
			. "($accessid, 'loginweb', '1'),"
			. "($accessid, 'manageprofile', '1'),"
			. "($accessid, 'manageaccount', '1'),"
			. "($accessid, 'manageaccount', '1'),"
			. "($accessid, 'managesystem', '1'),"
			. "($accessid, 'loginphone', '1'),"
			. "($accessid, 'startstats', '1'),"
			. "($accessid, 'startshort', '1'),"
			. "($accessid, 'starteasy', '1'),"
			. "($accessid, 'sendprint', '0'),"
			. "($accessid, 'callmax', '10'),"
			. "($accessid, 'sendemail', '1'),"
			. "($accessid, 'sendphone', '1'),"
			. "($accessid, 'sendmulti', '1'),"
			. "($accessid, 'createlist', '1'),"
			. "($accessid, 'createrepeat', '1'),"
			. "($accessid, 'createreport', '1'),"
			. "($accessid, 'datafields', 'f01|f02|f03'),"
			. "($accessid, 'maxjobdays', '7'),"
			. "($accessid, 'viewsystemreports', '1'),"
			. "($accessid, 'managesystemjobs', '1'),"
			. "($accessid, 'managemyaccount', '1'),"
			. "($accessid, 'viewcontacts', '1'),"
			. "($accessid, 'viewsystemactive', '1'),"
			. "($accessid, 'viewsystemrepeating', '1'),"
			. "($accessid, 'viewsystemcompleted', '1'),"
			. "($accessid, 'listuploadids', '1'),"
			. "($accessid, 'listuploadcontacts', '1'),"
			. "($accessid, 'setcallerid', '1'),"
			. "($accessid, 'blocknumbers', '1'),"
			. "($accessid, 'callblockingperms', 'editall'),"
			. "($accessid, 'metadata', '1'),"
			. "($accessid, 'managetasks', '1');"
		;
	QuickUpdate($query) or die( "ERROR:" . mysql_error() . " SQL:" . $query);

	$query = "INSERT INTO `user` (`accessid`, `login`, `password`, `customerid`, `firstname`, `lastname`, `enabled`, `deleted`) VALUES
		($accessid, '$_POST[user]', password('$_POST[password]'), $custid, 'System', 'Administrator', 1 ,0)";
	QuickUpdate($query) or die( "ERROR:" . mysql_error() . " SQL:" . $query);

	$query = "INSERT INTO `fieldmap` (`customerid`, `fieldnum`, `name`, `options`) VALUES
		($custid, 'f01', 'First Name', 'searchable,text'),
		($custid, 'f02', 'Last Name', 'searchable,text'),
		($custid, 'f03', 'Language', 'searchable,multisearch');
		";
	QuickUpdate($query) or die( "ERROR:" . mysql_error() . " SQL:" . $query);

	$query = "
		INSERT INTO `language` (`customerid`, `name`, `code`) VALUES
		(123, 'English', ''),(123, 'Spanish', '');
		";
	QuickUpdate($query) or die( "ERROR:" . mysql_error() . " SQL:" . $query);


	$query = "INSERT INTO `jobtype` (`customerid`, `name`, `priority`, `systempriority`, `deleted`) VALUES
			($custid, 'Emergency', 10000, 1, 0),
			($custid, 'Attendance', 20000, 2, 0),
			($custid, 'General', 30000, 3, 0)
		";
	QuickUpdate($query) or die( "ERROR:" . mysql_error() . " SQL:" . $query);


	exit("All done!");

} else {
?>


<html>
<body>
<form method="POST">

Customer name: <input name="name" type="text"><br>
URL name: <input name="hostname" type="text"><br>
Admin username: <input name="user" type="text"><br>
Admin password: <input name="password" type="password"><br>
Password verify:<input name="password2" type="password"><br>

Reliance secret code:<input name="code" type="password"><br>
<select name="timezone">
<option value="US/Alaska">US/Alaska</option>
<option value="US/Aleutian">US/Aleutian</option>
<option value="US/Arizona">US/Arizona</option>
<option value="US/Central">US/Central</option>
<option value="US/East-Indiana">US/East-Indiana</option>
<option value="US/Eastern">US/Eastern</option>
<option value="US/Hawaii">US/Hawaii</option>
<option value="US/Indiana-Starke">US/Indiana-Starke</option>
<option value="US/Michigan">US/Michigan</option>
<option value="US/Mountain">US/Mountain</option>
<option value="US/Pacific">US/Pacific</option>
<!-- <option value="US/Pacific-New">US/Pacific-New</option> -->
<option value="US/Samoa">US/Samoa</option>
<option value="America/Adak">America/Adak</option>
<option value="America/Anchorage">America/Anchorage</option>
<option value="America/Anguilla">America/Anguilla</option>
<option value="America/Antigua">America/Antigua</option>
<option value="America/Araguaina">America/Araguaina</option>
<option value="America/Argentina/Buenos_Aires">America/Argentina/Buenos_Aires</option>
<option value="America/Argentina/Catamarca">America/Argentina/Catamarca</option>
<option value="America/Argentina/ComodRivadavia">America/Argentina/ComodRivadavia</option>
<option value="America/Argentina/Cordoba">America/Argentina/Cordoba</option>
<option value="America/Argentina/Jujuy">America/Argentina/Jujuy</option>
<option value="America/Argentina/La_Rioja">America/Argentina/La_Rioja</option>
<option value="America/Argentina/Mendoza">America/Argentina/Mendoza</option>
<option value="America/Argentina/Rio_Gallegos">America/Argentina/Rio_Gallegos</option>
<option value="America/Argentina/San_Juan">America/Argentina/San_Juan</option>
<option value="America/Argentina/Tucuman">America/Argentina/Tucuman</option>
<option value="America/Argentina/Ushuaia">America/Argentina/Ushuaia</option>
<option value="America/Aruba">America/Aruba</option>
<option value="America/Asuncion">America/Asuncion</option>
<option value="America/Atka">America/Atka</option>
<option value="America/Bahia">America/Bahia</option>
<option value="America/Barbados">America/Barbados</option>
<option value="America/Belem">America/Belem</option>
<option value="America/Belize">America/Belize</option>
<option value="America/Boa_Vista">America/Boa_Vista</option>
<option value="America/Bogota">America/Bogota</option>
<option value="America/Boise">America/Boise</option>
<option value="America/Buenos_Aires">America/Buenos_Aires</option>
<option value="America/Cambridge_Bay">America/Cambridge_Bay</option>
<option value="America/Campo_Grande">America/Campo_Grande</option>
<option value="America/Cancun">America/Cancun</option>
<option value="America/Caracas">America/Caracas</option>
<option value="America/Catamarca">America/Catamarca</option>
<option value="America/Cayenne">America/Cayenne</option>
<option value="America/Cayman">America/Cayman</option>
<option value="America/Chicago">America/Chicago</option>
<option value="America/Chihuahua">America/Chihuahua</option>
<option value="America/Coral_Harbour">America/Coral_Harbour</option>
<option value="America/Cordoba">America/Cordoba</option>
<option value="America/Costa_Rica">America/Costa_Rica</option>
<option value="America/Cuiaba">America/Cuiaba</option>
<option value="America/Curacao">America/Curacao</option>
<option value="America/Danmarkshavn">America/Danmarkshavn</option>
<option value="America/Dawson">America/Dawson</option>
<option value="America/Dawson_Creek">America/Dawson_Creek</option>
<option value="America/Denver">America/Denver</option>
<option value="America/Detroit">America/Detroit</option>
<option value="America/Dominica">America/Dominica</option>
<option value="America/Edmonton">America/Edmonton</option>
<option value="America/Eirunepe">America/Eirunepe</option>
<option value="America/El_Salvador">America/El_Salvador</option>
<option value="America/Ensenada">America/Ensenada</option>
<option value="America/Fort_Wayne">America/Fort_Wayne</option>
<option value="America/Fortaleza">America/Fortaleza</option>
<option value="America/Glace_Bay">America/Glace_Bay</option>
<option value="America/Godthab">America/Godthab</option>
<option value="America/Goose_Bay">America/Goose_Bay</option>
<option value="America/Grand_Turk">America/Grand_Turk</option>
<option value="America/Grenada">America/Grenada</option>
<option value="America/Guadeloupe">America/Guadeloupe</option>
<option value="America/Guatemala">America/Guatemala</option>
<option value="America/Guayaquil">America/Guayaquil</option>
<option value="America/Guyana">America/Guyana</option>
<option value="America/Halifax">America/Halifax</option>
<option value="America/Havana">America/Havana</option>
<option value="America/Hermosillo">America/Hermosillo</option>
<option value="America/Indiana/Indianapolis">America/Indiana/Indianapolis</option>
<option value="America/Indiana/Knox">America/Indiana/Knox</option>
<option value="America/Indiana/Marengo">America/Indiana/Marengo</option>
<option value="America/Indiana/Vevay">America/Indiana/Vevay</option>
<option value="America/Indianapolis">America/Indianapolis</option>
<option value="America/Inuvik">America/Inuvik</option>
<option value="America/Iqaluit">America/Iqaluit</option>
<option value="America/Jamaica">America/Jamaica</option>
<option value="America/Jujuy">America/Jujuy</option>
<option value="America/Juneau">America/Juneau</option>
<option value="America/Kentucky/Louisville">America/Kentucky/Louisville</option>
<option value="America/Kentucky/Monticello">America/Kentucky/Monticello</option>
<option value="America/Knox_IN">America/Knox_IN</option>
<option value="America/La_Paz">America/La_Paz</option>
<option value="America/Lima">America/Lima</option>
<option value="America/Los_Angeles">America/Los_Angeles</option>
<option value="America/Louisville">America/Louisville</option>
<option value="America/Maceio">America/Maceio</option>
<option value="America/Managua">America/Managua</option>
<option value="America/Manaus">America/Manaus</option>
<option value="America/Martinique">America/Martinique</option>
<option value="America/Mazatlan">America/Mazatlan</option>
<option value="America/Mendoza">America/Mendoza</option>
<option value="America/Menominee">America/Menominee</option>
<option value="America/Merida">America/Merida</option>
<option value="America/Mexico_City">America/Mexico_City</option>
<option value="America/Miquelon">America/Miquelon</option>
<option value="America/Monterrey">America/Monterrey</option>
<option value="America/Montevideo">America/Montevideo</option>
<option value="America/Montreal">America/Montreal</option>
<option value="America/Montserrat">America/Montserrat</option>
<option value="America/Nassau">America/Nassau</option>
<option value="America/New_York">America/New_York</option>
<option value="America/Nipigon">America/Nipigon</option>
<option value="America/Nome">America/Nome</option>
<option value="America/Noronha">America/Noronha</option>
<option value="America/North_Dakota/Center">America/North_Dakota/Center</option>
<option value="America/Panama">America/Panama</option>
<option value="America/Pangnirtung">America/Pangnirtung</option>
<option value="America/Paramaribo">America/Paramaribo</option>
<option value="America/Phoenix">America/Phoenix</option>
<option value="America/Port-au-Prince">America/Port-au-Prince</option>
<option value="America/Port_of_Spain">America/Port_of_Spain</option>
<option value="America/Porto_Acre">America/Porto_Acre</option>
<option value="America/Porto_Velho">America/Porto_Velho</option>
<option value="America/Puerto_Rico">America/Puerto_Rico</option>
<option value="America/Rainy_River">America/Rainy_River</option>
<option value="America/Rankin_Inlet">America/Rankin_Inlet</option>
<option value="America/Recife">America/Recife</option>
<option value="America/Regina">America/Regina</option>
<option value="America/Rio_Branco">America/Rio_Branco</option>
<option value="America/Rosario">America/Rosario</option>
<option value="America/Santiago">America/Santiago</option>
<option value="America/Santo_Domingo">America/Santo_Domingo</option>
<option value="America/Sao_Paulo">America/Sao_Paulo</option>
<option value="America/Scoresbysund">America/Scoresbysund</option>
<option value="America/Shiprock">America/Shiprock</option>
<option value="America/St_Johns">America/St_Johns</option>
<option value="America/St_Kitts">America/St_Kitts</option>
<option value="America/St_Lucia">America/St_Lucia</option>
<option value="America/St_Thomas">America/St_Thomas</option>
<option value="America/St_Vincent">America/St_Vincent</option>
<option value="America/Swift_Current">America/Swift_Current</option>
<option value="America/Tegucigalpa">America/Tegucigalpa</option>
<option value="America/Thule">America/Thule</option>
<option value="America/Thunder_Bay">America/Thunder_Bay</option>
<option value="America/Tijuana">America/Tijuana</option>
<option value="America/Toronto">America/Toronto</option>
<option value="America/Tortola">America/Tortola</option>
<option value="America/Vancouver">America/Vancouver</option>
<option value="America/Virgin">America/Virgin</option>
<option value="America/Whitehorse">America/Whitehorse</option>
<option value="America/Winnipeg">America/Winnipeg</option>
<option value="America/Yakutat">America/Yakutat</option>
<option value="America/Yellowknife">America/Yellowknife</option>

<!-- <option value="Brazil/Acre">Brazil/Acre</option>
<option value="Brazil/DeNoronha">Brazil/DeNoronha</option>
<option value="Brazil/East">Brazil/East</option>
<option value="Brazil/West">Brazil/West</option>
<option value="Canada/Atlantic">Canada/Atlantic</option>
<option value="Canada/Central">Canada/Central</option>
<option value="Canada/East-Saskatchewan">Canada/East-Saskatchewan</option>
<option value="Canada/Eastern">Canada/Eastern</option>
<option value="Canada/Mountain">Canada/Mountain</option>
<option value="Canada/Newfoundland">Canada/Newfoundland</option>
<option value="Canada/Pacific">Canada/Pacific</option>
<option value="Canada/Saskatchewan">Canada/Saskatchewan</option>
<option value="Canada/Yukon">Canada/Yukon</option>
<option value="Chile/Continental">Chile/Continental</option>
<option value="Chile/EasterIsland">Chile/EasterIsland</option>
<option value="Mexico/BajaNorte">Mexico/BajaNorte</option>
<option value="Mexico/BajaSur">Mexico/BajaSur</option>
<option value="Mexico/General">Mexico/General</option>
-->

</select><br>

<input name="submit" type="submit">

</form>
</body>
</html>
<?
}
?>

