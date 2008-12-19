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

			if (QuickQuery("SELECT COUNT(*) FROM customer WHERE urlcomponent='" . DBSafe($hostname) ."' and enabled=1")) {
				error('URL Path Already exists', 'Please Enter Another');
			} else if(!$accountcreator->runCheck($managerpassword)) {
				error('Bad Manager Password');
			} else if (!$shard){
				error('A shard needs to be chosen');
			} else if(!$logofile){
				error('Logo file read error occured, make sure you selected a logo');
			} else {

				createNewCustomer(false, $shard, $hostname);
				createSMUserProfile($newdb);

				$query = "INSERT INTO `fieldmap` (`fieldnum`, `name`, `options`) VALUES
							('f01', 'First Name', 'searchable,text,firstname'),
							('f02', 'Last Name', 'searchable,text,lastname'),
							('f03', 'Language', 'searchable,multisearch,language'),
							('c01', 'Staff ID', 'searchable,multisearch,staff')";
				QuickUpdate($query, $newdb) or die( "ERROR:" . mysql_error() . " SQL:" . $query);

				$query = "INSERT INTO `language` (`name`) VALUES
							('English'),
							('Spanish')";
				QuickUpdate($query, $newdb) or die( "ERROR: " . mysql_error() . " SQL:" . $query);

				$query = "INSERT INTO `jobtype` (`name`, `systempriority`, `info`, `issurvey`, `deleted`) VALUES
							('Emergency', 1, 'Emergencies Only', 0, 0),
							('Attendance', 2, 'Attendance', 0, 0),
							('General', 3, 'General Announcements', 0, 0),
							('Survey', 3, 'Surveys', 1, 0)";

				QuickUpdate($query, $newdb) or die( "ERROR: " . mysql_error() . " SQL:" . $query);

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
							('swedish', 'male'),
							('brazilian', 'female'),
							('brazilian', 'male')
							";

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
<div style="color:green" >
	Please remember to double check the customer settings on the following edit page.
</div>
<br>
<?

NewFormItem($f, $s,"", 'submit');
managerPassword($f, $s);
EndForm();

include_once("navbottom.inc.php");
?>

