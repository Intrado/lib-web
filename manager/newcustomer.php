<?
require_once("common.inc.php");
require_once("../inc/form.inc.php");
require_once("../inc/html.inc.php");
require_once("../inc/utils.inc.php");

if (!$MANAGERUSER->authorized("newcustomer"))
	exit("Not Authorized");

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

global $_dbcon;


////////////////////////////////////////////////////////////////////////////////
// Functions
////////////////////////////////////////////////////////////////////////////////


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
					"Skylert" =>
										array("filelocation" => "img/sky_alert.jpg",
										"filetype" => "image/jpg")
					);

$f = "customer";
$s = "main";

$reloadform = 0;

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
			$shard = GetFormData($f,$s,'shard')+0;
			$defaultproductname = GetformData($f, $s, "productname");
			$defaultbrand = GetFormData($f, $s, "logo");
			if($defaultbrand != "Other"){
				$logofile = @file_get_contents($defaultbrands[$defaultbrand]['filelocation']);
			} else {
				$logofile = true;
			}

			if (QuickQuery("SELECT COUNT(*) FROM customer WHERE urlcomponent=? and enabled=1", false, array($hostname))) {
				error('URL Path Already exists', 'Please Enter Another');
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
				$limitedpassword = genpassword();
				QuickUpdate("insert into customer (urlcomponent, shardid, dbpassword, limitedpassword, enabled) 
												values (?, ?, ?, ?, '1')", false, array($hostname, $shardid, $dbpassword, $limitedpassword) )
						or dieWithError("failed to insert customer into auth server", $_dbcon);
				$customerid = $_dbcon->lastInsertId();

				$newdbname = "c_$customerid";
				$limitedusername = "c_".$customerid."_limited";
				QuickUpdate("update customer set dbusername = '" . $newdbname . "', limitedusername = '" . $limitedusername . "' where id = '" . $customerid . "'");

				$newdb = DBConnect($shardhost, $sharduser, $shardpass, "aspshard");
				QuickUpdate("create database $newdbname DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci",$newdb)
					or dieWithError("Failed to create new DB ".$newdbname, $newdb);
				$newdb->query("use ".$newdbname)
					or dieWithError("Failed to connect to DB ".$newdbname, $newdb);

				// customer db user
				QuickUpdate("drop user '$newdbname'", $newdb); //ensure mysql credentials match our records, which it won't if create user fails because the user already exists
				QuickUpdate("create user '$newdbname' identified by '$dbpassword'", $newdb);
				QuickUpdate("grant select, insert, update, delete, create temporary tables, execute on $newdbname . * to '$newdbname'", $newdb);

				// create customer tables
				$tablequeries = explode("$$$",file_get_contents("../db/customer.sql"));
				$tablequeries = array_merge($tablequeries, explode("$$$",file_get_contents("../db/createtriggers.sql")));
				foreach ($tablequeries as $tablequery) {
					if (trim($tablequery)) {
						$tablequery = str_replace('_$CUSTOMERID_', $customerid, $tablequery);
						Query($tablequery,$newdb)
							or dieWithError("Failed to execute statement \n$tablequery\n\nfor $newdbname", $newdb);
					}
				}

				// subscriber db user
				createLimitedUser($limitedusername, $limitedpassword, $newdbname, $newdb);

				// 'schoolmessenger' user
				createSMUserProfile($newdb);

				$query = "INSERT INTO `fieldmap` (`fieldnum`, `name`, `options`) VALUES
							('f01', 'First Name', 'searchable,text,firstname,subscribe,dynamic'),
							('f02', 'Last Name', 'searchable,text,lastname,subscribe,dynamic'),
							('f03', 'Language', 'searchable,multisearch,language,subscribe,static'),
							('c01', 'Staff ID', 'searchable,multisearch,staff')";
				QuickUpdate($query, $newdb) or dieWithError("SQL:" . $query, $newdb);

				$query = "INSERT INTO `language` (`name`) VALUES
							('English'),
							('Spanish')";
				QuickUpdate($query, $newdb) or dieWithError("SQL:" . $query, $newdb);

				$query = "INSERT INTO `jobtype` (`name`, `systempriority`, `info`, `issurvey`, `deleted`) VALUES
							('Emergency', 1, 'Emergencies Only', 0, 0),
							('Attendance', 2, 'Attendance', 0, 0),
							('General', 3, 'General Announcements', 0, 0),
							('Survey', 3, 'Surveys', 1, 0)";

				QuickUpdate($query, $newdb) or dieWithError(" SQL:" . $query, $newdb);

				$query = "INSERT INTO `jobtypepref` (`jobtypeid`,`type`,`sequence`,`enabled`) VALUES
							(1,'phone',0,1),
							(1,'email',0,1),
							(1,'sms',0,1),
							(2,'phone',0,1),
							(2,'email',0,1),
							(2,'sms',0,1),
							(3,'phone',0,1),
							(3,'email',0,1),
							(3,'sms',0,1),
							(4,'phone',0,1),
							(4,'email',0,1),
							(4,'sms',0,0)";

				QuickUpdate($query, $newdb) or dieWithError(" SQL:" . $query, $newdb);

				$surveyurl = $SETTINGS['feature']['customer_url_prefix'] . "/" . $hostname . "/survey/";
				$query = "INSERT INTO `setting` (`name`, `value`) VALUES
							('customerurl', ?),
							('maxphones', '1'),
							('maxemails', '1'),
							('maxsms', '1'),
							('retry', '15'),
							('disablerepeat', '0'),
							('surveyurl', ?),
							('displayname', ?),
							('timezone', ?)";

				QuickUpdate($query, $newdb, array($hostname, $surveyurl, $displayname, $timezone)) or dieWithError(" SQL:" . $query, $newdb);

				$query = "INSERT INTO `ttsvoice` (`language`, `gender`) VALUES
							('english', 'male'),
							('english', 'female'),
							('spanish', 'male'),
							('spanish', 'female'),
							('catalan', 'female'),
							('catalan', 'male'),
							('chinese', 'female'),
							('dutch', 'female'),
							('dutch', 'male'),
							('finnish', 'female'),
							('french', 'female'),
							('french', 'male'),
							('german', 'female'),
							('german', 'male'),
							('greek', 'female'),
							('italian', 'female'),
							('italian', 'male'),
							('polish', 'female'),
							('polish', 'male'),
							('portuguese', 'female'),
							('portuguese', 'male'),
							('russian', 'female'),
							('swedish', 'female'),
							('swedish', 'male')
							";

				QuickUpdate($query, $newdb) or dieWithError(" SQL: " . $query, $newdb);

				// Brand/LOGO Info

				if($logofile && $defaultbrand != "Other"){
					$query = "INSERT INTO `content` (`contenttype`, `data`) VALUES
								('" . $defaultbrands[$defaultbrand]["filetype"] . "', '" . base64_encode($logofile) . "');";
					QuickUpdate($query, $newdb) or dieWithError(" SQL: " . $query, $newdb);
					$logoid = $newdb->lastInsertId();

					$query = "INSERT INTO `setting` (`name`, `value`) VALUES
								('_logocontentid', '" . $logoid . "')";
					QuickUpdate($query, $newdb) or dieWithError(" SQL: " . $query, $newdb);
				}

				// Login Picture
				QuickUpdate("INSERT INTO content (contenttype, data) values
							('image/gif', '" . base64_encode(file_get_contents("img/classroom_girl.jpg")) . "')",$newdb);
				$loginpicturecontentid = $newdb->lastInsertId();

				$query = "INSERT INTO `setting` (`name`, `value`) VALUES
							('_loginpicturecontentid', '" . $loginpicturecontentid . "')";
				QuickUpdate($query, $newdb) or dieWithError(" SQL: " . $query, $newdb);

				// Subscriber Login Picture
				QuickUpdate("INSERT INTO content (contenttype, data) values
							('image/gif', '" . base64_encode(file_get_contents("img/header_highered3.gif")) . "')",$newdb);
				$subscriberloginpicturecontentid = $newdb->lastInsertId();

				$query = "INSERT INTO `setting` (`name`, `value`) VALUES
							('_subscriberloginpicturecontentid', '" . $subscriberloginpicturecontentid . "')";
				QuickUpdate($query, $newdb) or dieWithError(" SQL: " . $query, $newdb);

				// Product Name
				$query = "INSERT INTO `setting` (`name`, `value`) VALUES
							('_productname', ?)";
				QuickUpdate($query, $newdb, array($defaultproductname)) or dieWithError(" SQL: " . $query, $newdb);

				redirect("customeredit.php?id=" . $customerid);

			}
		}
	}
} else {
	$reloadform = 1;
}

if( $reloadform ){

	ClearFormData($f);
	PutFormData($f,$s,"Submit", "");
	PutFormData($f,$s,'name',"","text",1,50, true);
	PutFormData($f,$s,'hostname',"","text",5,255, true);
	PutFormData($f,$s,'timezone', "");
	PutFormData($f,$s,'shard', "", "number", "nomin", "nomax", true);
	PutFormData($f,$s,'logo', null, null, null, null);
	PutFormData($f,$s,'productname', "", "text", null, 255, true);
}

include_once("nav.inc.php");

NewForm($f);

?><br><?

NewFormItem($f, $s,"Submit", 'submit');

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
	$shardquery = Query("select id, name from shard where not isfull order by id");
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
<?

NewFormItem($f, $s,"Submit", 'submit');
EndForm();

include_once("navbottom.inc.php");
?>

