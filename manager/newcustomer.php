<?
include_once("common.inc.php");
include_once("../inc/form.inc.php");
include_once("../inc/html.inc.php");
include_once("../inc/utils.inc.php");
include_once("AspAdminUser.obj.php");

$timezones = array(	"US/Alaska",
					"US/Aleutian",
					"US/Arizona",
					"US/Central",
					"US/East-Indiana",
					"US/Eastern",
					"US/Hawaii",
					"US/Indiana-Starke",
					"US/Michigan",
					"US/Mountain",
					"US/Pacific",
					"US/Samoa"	);



////////////////////////////////////////////////////////////////////////////////
// Functions
////////////////////////////////////////////////////////////////////////////////

function genpassword() {
	$digits = 15;
	$passwd = "";
	$chars = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz";
	while ($digits--) {
		$passwd .= $chars[mt_rand(0,strlen($chars)-1)];
	}
	return $passwd;
}

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

$defaultbrands = array(
					"AutoMessenger" =>
										array("filelocation" => "img/auto_messenger.jpg",
										"filetype" => "image/jpg"),
					"SchoolMessenger" =>
										array("filelocation" => "img/logo_small.gif",
										"filetype" => "image/gif"),
					"SkyAlert" =>
										array("filelocation" => "img/sky_alert.jpg",
										"filetype" => "image/jpg")
					);

$f = "customer";
$s = "main";

$reloadform = 0;
$accountcreator = new AspAdminUser($_SESSION['aspadminuserid']);

// If user submitted the form
if (CheckFormSubmit($f,$s)){
	if(CheckFormInvalid($f)) {
		error('Form was edited in another window, reloading data');
		$reloadform = 1;
	} else {
		MergeSectionFormData($f,$s);

		// Checks to see if user left out any of the required fields
		if( CheckFormSection($f, $s) ) {
			error('There was a problem trying to save your changes', 'Please verify that all required field information has been entered properly');
		}else{

			$displayname = GetFormData($f,$s,"name");
			$timezone = GetFormData($f, $s, "timezone");
			$hostname = GetFormData($f, $s, "hostname");
			$managerpassword = GetFormData($f, $s, "managerpassword");
			$shard = GetFormData($f,$s,'shard')+0;
			$defaultproductname = GetformData($f, $s, "productname");
			$defaultbrand = GetFormData($f, $s, "logo");
			if($defaultbrand != "Other"){
				$logofile = @file_get_contents($defaultbrands[$defaultbrand]['filelocation']);
			} else {
				$logofile = true;
			}

			if (QuickQuery("SELECT COUNT(*) FROM customer WHERE urlcomponent='" . DBSafe($hostname) ."'")) {
				error('URL Path Already exists', 'Please Enter Another');
			} else if(!$accountcreator->runCheck($managerpassword)) {
				error('Bad Manager Password');
			} else if (!$shard){
				error('A shard needs to be chosen');
			} else if(!$logofile){
				error('Logo file read error occured, make sure you selected a logo');
			} else {

				//choose shard info based on selection
				$shardinfo = QuickQueryRow("select id, dbhost, dbusername, dbpassword from shard where id = '$shard'", true);
				$shardid = $shardinfo['id'];
				$shardhost = $shardinfo['dbhost'];
				$sharduser = $shardinfo['dbusername'];
				$shardpass = $shardinfo['dbpassword'];

				$dbpassword = genpassword();
				QuickUpdate("insert into customer (urlcomponent, shardid, dbpassword,enabled) values
												('" . DBSafe($hostname) . "','$shardid', '$dbpassword', '1')" )
						or die("failed to insert customer into auth server");
				$customerid = mysql_insert_id();

				$newdbname = "c_$customerid";
				QuickUpdate("update customer set dbusername = '" . $newdbname . "' where id = '" . $customerid . "'");

				$newdb = mysql_connect($shardhost, $sharduser, $shardpass)
					or die("Failed to connect to DBHost $shardhost : " . mysql_error($newdb));
				QuickUpdate("create database $newdbname",$newdb)
					or die ("Failed to create new DB $newdbname : " . mysql_error($newdb));
				mysql_select_db($newdbname,$newdb)
					or die ("Failed to connect to DB $newdbname : " . mysql_error($newdb));

				QuickUpdate("drop user '$newdbname'", $newdb); //ensure mysql credentials match our records, which it won't if create user fails because the user already exists
				QuickUpdate("create user '$newdbname' identified by '$dbpassword'", $newdb);
				QuickUpdate("grant select, insert, update, delete, create temporary tables, execute on $newdbname . * to '$newdbname'", $newdb);

				$tablequeries = explode("$$$",file_get_contents("../db/customer.sql"));
				foreach ($tablequeries as $tablequery) {
					if (trim($tablequery)) {
						$tablequery = str_replace('_$CUSTOMERID_', $customerid, $tablequery);
						Query($tablequery,$newdb)
							or die ("Failed to execute statement \n$tablequery\n\nfor $newdbname : " . mysql_error($newdb));
					}
				}

				$query = "insert into access (name) values ('SchoolMessenger Admin')";
				QuickUpdate($query, $newdb) or die( "ERROR: " . mysql_error());
				$accessid = mysql_insert_id();

				$query = "INSERT INTO `permission` (accessid,name,value) VALUES "
						. "($accessid, 'loginweb', '1'),"
						. "($accessid, 'manageprofile', '1'),"
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
						. "($accessid, 'sendsms', '1'),"
						. "($accessid, 'sendmulti', '1'),"
						. "($accessid, 'leavemessage', '1'),"
						. "($accessid, 'survey', '1'),"
						. "($accessid, 'createlist', '1'),"
						. "($accessid, 'createrepeat', '1'),"
						. "($accessid, 'createreport', '1'),"
						. "($accessid, 'maxjobdays', '7'),"
						. "($accessid, 'viewsystemreports', '1'),"
						. "($accessid, 'viewusagestats', '1'),"
						. "($accessid, 'viewcalldistribution', '1'),"
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
						. "($accessid, 'portalaccess', '1'),"
						. "($accessid, 'generatebulktokens', '1'),"
						. "($accessid, 'managetasks', '1');"
						;
				QuickUpdate($query, $newdb) or die( "ERROR: " . mysql_error() . " SQL:" . $query);

				$query = "INSERT INTO `user` (`accessid`, `login`,
							`firstname`, `lastname`, `enabled`, `deleted`) VALUES
							( '$accessid' , 'schoolmessenger',
							'School', 'Messenger', 1 ,0)";
				QuickUpdate($query, $newdb) or die( "ERROR: " . mysql_error() . " SQL:" . $query);

				$query = "INSERT INTO `fieldmap` (`fieldnum`, `name`, `options`) VALUES
							('f01', 'First Name', 'searchable,text,firstname'),
							('f02', 'Last Name', 'searchable,text,lastname'),
							('f03', 'Language', 'searchable,multisearch,language')";
				QuickUpdate($query, $newdb) or die( "ERROR:" . mysql_error() . " SQL:" . $query);

				$query = "INSERT INTO `language` (`name`) VALUES
							('English'),
							('Spanish')";
				QuickUpdate($query, $newdb) or die( "ERROR: " . mysql_error() . " SQL:" . $query);

				$query = "INSERT INTO `jobtype` (`name`, `systempriority`, `info`, `issurvey`, `deleted`) VALUES
							('Emergency', 1, 'General Announcements', 0, 0),
							('Attendance', 2, 'Time Critical Announcements', 0, 0),
							('General', 3, 'Emergencies Only', 0, 0),
							('Survey', 3, 'Surveys', 1, 0)";

				QuickUpdate($query, $newdb) or die( "ERROR: " . mysql_error() . " SQL:" . $query);

				$query = "INSERT INTO `jobtypepref` (`jobtypeid`,`type`,`sequence`,`enabled`) VALUES
							(1,'phone',0,1),
							(1,'phone',1,1),
							(1,'phone',2,1),
							(1,'email',0,1),
							(1,'email',1,1),
							(1,'sms',0,1),
							(1,'sms',1,1),
							(2,'phone',0,1),
							(2,'phone',1,0),
							(2,'phone',2,0),
							(2,'email',0,1),
							(2,'email',1,0),
							(2,'sms',0,1),
							(2,'sms',1,0),
							(3,'phone',0,1),
							(3,'phone',1,0),
							(3,'phone',2,0),
							(3,'email',0,1),
							(3,'email',1,0),
							(3,'sms',0,1),
							(3,'sms',1,0),
							(4,'phone',0,1),
							(4,'phone',1,0),
							(4,'phone',2,0),
							(4,'email',0,1),
							(4,'email',1,0),
							(4,'sms',0,0),
							(4,'sms',1,0)";

				QuickUpdate($query, $newdb) or die( "ERROR: " . mysql_error() . " SQL:" . $query);

				$surveyurl = $SETTINGS['feature']['customer_url_prefix'] . "/" . $hostname . "/survey/";
				$query = "INSERT INTO `setting` (`name`, `value`) VALUES
							('maxphones', '1'),
							('maxemails', '1'),
							('maxsms', '1'),
							('retry', '15'),
							('disablerepeat', '0'),
							('surveyurl', '" . DBSafe($surveyurl) . "'),
							('displayname', '" . DBSafe($displayname) . "'),
							('timezone', '" . DBSafe($timezone) . "')";

				QuickUpdate($query, $newdb) or die( "ERROR: " . mysql_error() . " SQL:" . $query);

				$query = "INSERT INTO `ttsvoice` (`language`, `gender`) VALUES
							('english', 'male'),
							('english', 'female'),
							('spanish', 'male'),
							('spanish', 'female')";

				QuickUpdate($query, $newdb) or die( "ERROR: " . mysql_error() . " SQL: " . $query);

				// Brand/LOGO Info

				if($logofile && $defaultbrand != "Other"){
					$query = "INSERT INTO `content` (`contenttype`, `data`) VALUES
								('" . $defaultbrands[$defaultbrand]["filetype"] . "', '" . base64_encode($logofile) . "');";
					QuickUpdate($query, $newdb) or die( "ERROR: " . mysql_error() . " SQL: " . $query);
					$logoid = mysql_insert_id();

					$query = "INSERT INTO `setting` (`name`, `value`) VALUES
								('_logocontentid', '" . $logoid . "')";
					QuickUpdate($query, $newdb) or die( "ERROR: " . mysql_error() . " SQL: " . $query);
				}

				QuickUpdate("INSERT INTO content (contenttype, data) values
							('image/gif', '" . base64_encode(file_get_contents("img/classroom_girl.jpg")) . "')",$newdb);
				$loginpicturecontentid = mysql_insert_id($newdb);

				$query = "INSERT INTO `setting` (`name`, `value`) VALUES
							('_loginpicturecontentid', '" . $loginpicturecontentid . "')";
				QuickUpdate($query, $newdb) or die( "ERROR: " . mysql_error() . " SQL: " . $query);


				$query = "INSERT INTO `setting` (`name`, `value`) VALUES
								('_productname', '" . DBSafe($defaultproductname) . "')";
					QuickUpdate($query, $newdb) or die( "ERROR: " . mysql_error() . " SQL: " . $query);


				redirect("customeredit.php?id=" . $customerid);

			}
		}
	}
} else {
	$reloadform = 1;
}

if( $reloadform ){

	ClearFormData($f);

	PutFormData($f,$s,'name',"","text",1,50, true);
	PutFormData($f,$s,'hostname',"","text",5,255, true);
	PutFormData($f,$s,'managerpassword',"", "text");
	PutFormData($f,$s,'timezone', "");
	PutFormData($f,$s,'shard', "", "number", "nomin", "nomax", true);
	PutFormData($f,$s,'logo', null, null, null, null);
	PutFormData($f,$s,'productname', "", "text", null, 255, true);
}

include_once("nav.inc.php");

NewForm($f);

?><br><?

NewFormItem($f, $s,"", 'submit');

?>

<table>
<tr><td>Customer display name: </td><td> <? NewFormItem($f, $s, 'name', 'text', 25, 50); ?></td></tr>
<tr><td>URL path name: </td><td><? NewFormItem($f, $s, 'hostname', 'text', 25, 255); ?> (Must be 5 or more characters)</td></tr>

<tr><td>Timezone: </td><td>
<?
	NewFormItem($f, $s, 'timezone', "selectstart");
	foreach($timezones as $timezone) {
	   NewFormItem($f, $s, 'timezone', "selectoption", $timezone, $timezone);
	}
	NewFormItem($f, $s, 'timezone', "selectend");
?>
</td></tr>

<tr>
	<td>Logo:</td>
	<td>
		<table>
		<?
			foreach($defaultbrands as $brand => $logoinfo){
				?>
				<tr>
					<td>
						<?
						NewFormItem($f, $s, "logo", "radio", null, $brand, "id='$brand' onclick='new getObj(\"productname\").obj.value=\"$brand\"'");
						?>
					</td>
					<td><img src="<?=$logoinfo['filelocation']?>" onclick="new getObj('<?=$brand?>').obj.checked=true; new getObj('productname').obj.value='<?=$brand?>'" /></td>
				</tr>
				<?
			}
		?>
			<tr>
				<td>
					<?
						NewFormItem($f, $s, "logo", "radio", null, "Other", "id='other' onclick='new getObj(\"productname\").obj.value=\"\"'");
					?>
				</td>
				<td>
					<div onclick="new getObj('other').obj.checked=true; new getObj('productname').obj.value=''">Other</div>
				</td>
			</tr>
		</table>
	</td>
</tr>

<tr>
	<td>Brand:</td>
	<td><? NewFormItem($f, $s, "productname", "text", 30, 255, "id='productname'"); ?></td>
</tr>

<tr><td>Shard: </td><td>
<?
	$shardquery = Query("select id, name from shard order by id");
	$shards = array();
	while($row = DBGetRow($shardquery, true)){
		$shards[] = $row;
	}
	NewFormItem($f, $s, 'shard', "selectstart");
	foreach($shards as $shard) {
		NewFormItem($f, $s, 'shard', "selectoption", $shard['name'], $shard['id'] );
	}
	NewFormItem($f, $s, 'shard', "selectend");
?>
</td></tr>
</table>
<br>
<div style="color:green" >
	Please remember to double check the customer settings on the following edit page.
</div>
<br>
<?

NewFormItem($f, $s,"", 'submit');
?><p>Manager Password: </td><td><? NewFormItem($f, $s, 'managerpassword', 'password', 25); ?><p><?
EndForm();

include_once("navbottom.inc.php");
?>

