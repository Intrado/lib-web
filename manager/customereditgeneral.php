<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("common.inc.php");
require_once("../inc/table.inc.php");
require_once("../inc/html.inc.php");
require_once("../inc/utils.inc.php");
require_once("../obj/Phone.obj.php");
require_once("../obj/Validator.obj.php");
require_once("../obj/Form.obj.php");
require_once("../obj/FormItem.obj.php");
require_once("../obj/MessageGroup.obj.php");
require_once("../obj/Message.obj.php");
require_once("../obj/MessagePart.obj.php");
require_once("loadtemplatedata.php");
require_once("createtemplates.php");
require_once("../inc/themes.inc.php");
require_once("../obj/FormBrandTheme.obj.php");
require_once("../obj/ValSmsText.val.php");
require_once("XML/RPC.php");
require_once("authclient.inc.php");
////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////

if (isset($_GET['id'])) {
	if ($_GET['id'] === "new") {
		$_SESSION['customerid'] = null;
	} else {
		$_SESSION['customerid']= $_GET['id']+0;	
	}
	redirect();	
}


if (!isset($_SESSION['customerid']) && !$MANAGERUSER->authorized("newcustomer"))
	exit("Not Authorized");

if (!$MANAGERUSER->authorized("editcustomer")) {
	unset($_SESSION['customerid']);
	exit("Not Authorized");
}


////////////////////////////////////////////////////////////////////////////////
// Action/Request Processing
////////////////////////////////////////////////////////////////////////////////



////////////////////////////////////////////////////////////////////////////////
// Helper Functions
////////////////////////////////////////////////////////////////////////////////

class ValUrlComponent extends Validator {
	var $onlyserverside = true;
	function validate ($value, $args) {
		// Check alphanumeric
		if(!preg_match("/^[a-zA-Z0-9]*$/", $value)) {
			return 'Can only use letters and numbers';
		}
		
		// Allow legacy urlcomponents to be sorter than 5 characters but all new ones should be 5 or more
		if (($args["urlcomponent"] && strlen($args["urlcomponent"]) >= 5 && strlen($value) < 5) ||
			(!$args["urlcomponent"] && strlen($value) < 5)) {
			return 'URL path must be 5 or more characters';
		}		
		
		$query = "select count(*) from customer where urlcomponent=?";
		if (($args["customerid"] && QuickQuery($query . " and id!=?",false,array($value,$args["customerid"]))) ||
		(!$args["customerid"] && QuickQuery($query,false,array($value)))) {
			return 'URL path is already in use';
		}
		return true;
	}
}

class ValUrl extends Validator {
	var $urlregexp = "(http|https)\://[a-zA-Z0-9\-]+\.[a-zA-Z]{2,3}(:[a-zA-Z0-9]*)?/?([a-zA-Z0-9\-\._\'/\\\+&amp;%\$#\=~])*";

	function validate ($value, $args) {
		if (!preg_match("!^{$this->urlregexp}$!", $value))
		return "$this->label is not a valid url format";

		return true;
	}

	function getJSValidator () {
		return
		'function (name, label, value, args) {
			var urlregexp = "^' . addslashes($this->urlregexp) . '$";
			var reg = new RegExp(urlregexp);
			if (!reg.test(value))
				return label + " is not a valid url format";
			return true;
		}';
	}
}
	
////////////////////////////////////////////////////////////////////////////////
// Form Data
////////////////////////////////////////////////////////////////////////////////

// default settings
$settings = array(
	'timezone' => '',
	'displayname' => '',
	'urlcomponent' => ''
);

// $products = array(
// 	'cs' => 0,
// 	'tai' => 0,
// );
$products = array();

$customerid = null;
if (isset($_SESSION['customerid'])) {
	$customerid = $_SESSION['customerid'];
	$query = "select s.dbhost, c.dbusername, c.dbpassword, c.urlcomponent, c.enabled, c.oem, c.oemid, c.nsid, c.notes from customer c inner join shard s on (c.shardid = s.id) where c.id = '$customerid'";
	$custinfo = QuickQueryRow($query,true);
	$custdb = DBConnect($custinfo["dbhost"], $custinfo["dbusername"], $custinfo["dbpassword"], "c_$customerid");
	if (!$custdb) {
		exit("Connection failed for customer: {$custinfo["dbhost"]}, db: c_$customerid");
	}

	$query = "select name,value from setting";
	$settings = array_merge($settings, QuickQueryList($query,true,$custdb));
	
	$query = "select product, enabled from customerproduct where customerid=?";
	$products = QuickQueryList($query,true,false,array($_SESSION['customerid']));
	//$products = array_merge($products, QuickQueryList($query,true,false,array($_SESSION['customerid'])));
}


$timezones = array(
	"US/Alaska",
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
	"US/Samoa"
);


$shards = QuickQueryList("select id, name from shard where not isfull order by id",true);

$helpstepnum = 1;
$formdata = array(_L('Basics'));

$formdata["enabled"] = array(
	"label" => _L('Enabled'),
	"fieldhelp" => "Unchecking this box will disable this customer!  All repeating jobs will be stopped.  All scheduled jobs must be canceled manually.",
	"value" => isset($custinfo)?$custinfo["enabled"]:"",
	"validators" => array(),
	"control" => array("CheckBox"),
	"helpstep" => $helpstepnum
);

//Unable to change shard on this form
if (!$customerid) {
	$formdata["shard"] = array(
		"label" => _L('Shard'),
		"value" => "",
		"validators" => array(
			array("ValRequired"),
			array("ValInArray", "values" => array_keys($shards))
			),
		"control" => array("SelectMenu", "values" => array("" =>_L("-- Select a Shard --")) + $shards),
		"helpstep" => $helpstepnum
	);
}

$formdata["displayname"] = array(
	"label" => _L('Display Name'),
	"value" => $settings['displayname'],
	"validators" => array(
		array("ValRequired"),
		array("ValLength","min" => 3,"max" => 50)
	),
	"control" => array("TextField","size" => 30, "maxlength" => 51),
	"helpstep" => $helpstepnum
);

$formdata["timezone"] = array(
	"label" => _L('Time zone'),
	"value" => $settings['timezone'],
	"validators" => array(
		array("ValRequired"),
		array("ValInArray", "values" => $timezones)
	),
	"control" => array("SelectMenu", "values" => array_merge(array("" =>_L("-- Select a Timezone --")),array_combine($timezones,$timezones))),
	"helpstep" => $helpstepnum
);

$formdata["nsid"] = array(
	"label" => _L('NetSuite ID'),
	"value" => isset($custinfo)?$custinfo["nsid"]:"",
	"validators" => array(
		array("ValLength","max" => 50)
	),
	"control" => array("TextField","maxlength"=>50,"size"=>4),
	"helpstep" => $helpstepnum
);

$formdata["notes"] = array(
	"label" => _L('Notes'),
	"value" => isset($custinfo)?$custinfo["notes"]:"",
	"validators" => array(),
	"control" => array("TextArea", "rows" => 3, "cols" => 100),
	"helpstep" => $helpstepnum
);

$formdata[] = _L('Products');
$formdata["commsuite"] = array(
	"label" => _L('Commsuite'),
	"value" => isset($products['cs'])?$products['cs']:0,
	"validators" => array(),
	"control" => array("CheckBox"),
	"helpstep" => $helpstepnum
);

$formdata["tai"] = array(
	"label" => _L('Talk About It'),
	"value" => isset($products['tai'])?$products['tai']:0,
	"validators" => array(),
	"control" => array("CheckBox"),
	"helpstep" => $helpstepnum
);

$buttons = array(submit_button(_L("Save"),"save","tick"),submit_button(_L("Save and Return"),"done","tick"),
				icon_button(_L('Cancel'),"cross",null,"allcustomers.php"));
$form = new Form("newcustomer",$formdata,null,$buttons);

////////////////////////////////////////////////////////////////////////////////
// Form Data Handling
////////////////////////////////////////////////////////////////////////////////

//check and handle an ajax request (will exit early)
//or merge in related post data
$form->handleRequest();

$datachange = false;
$errors = false;
//check for form submission
if ($button = $form->getSubmit()) { //checks for submit and merges in post data
	$ajax = $form->isAjaxSubmit(); //whether or not this requires an ajax response	
	
	if ($form->checkForDataChange()) {
		$datachange = true;
		} else if (($errors = $form->validate()) === false) { //checks all of the items in this form
		$postdata = $form->getData(); //gets assoc array of all values {name:value,...}
		Query("BEGIN");
		// Craete new customer if It does not exist 
		if (!$customerid) {			
			//choose shard info based on selection
			$shardinfo = QuickQueryRow("select id, dbhost, dbusername, dbpassword from shard where id = ?", true,false,array($postdata["shard"]));
			$shardid = $shardinfo['id'];
			$shardhost = $shardinfo['dbhost'];
			$sharduser = $shardinfo['dbusername'];
			$shardpass = $shardinfo['dbpassword'];
			
			$dbpassword = genpassword();
			$limitedpassword = genpassword();
			QuickUpdate("insert into customer (urlcomponent, shardid, dbpassword, limitedpassword)
															values (?, ?, ?, ?)", false, array('', $shardid, $dbpassword, $limitedpassword) )
			or dieWithError("failed to insert customer into auth server", $_dbcon);
			
			$customerid = $_dbcon->lastInsertId();
			$custdbname = "c_$customerid";
			$limitedusername = "c_".$customerid."_limited";
			QuickUpdate("update customer set dbusername = '" . $custdbname . "', limitedusername = '" . $limitedusername . "' where id = '" . $customerid . "'");
			
			$custdb = DBConnect($shardhost, $sharduser, $shardpass, "aspshard");
			QuickUpdate("create database $custdbname DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci",$custdb)
				or dieWithError("Failed to create new DB ".$custdbname, $custdb);
			$custdb->query("use ".$custdbname)
				or dieWithError("Failed to connect to DB ".$custdbname, $custdb);
			
			// customer db user
			$grantedhost = '%';
			if (isset($SETTINGS['feature']['should_grant_local']) && $SETTINGS['feature']['should_grant_local']) {
				$grantedhost = 'localhost';
			}
			QuickUpdate("drop user '$custdbname'@'$grantedhost'", $custdb); //ensure mysql credentials match our records, which it won't if create user fails because the user already exists
			QuickUpdate("create user '$custdbname'@'$grantedhost' identified by '$dbpassword'", $custdb);
			QuickUpdate("grant select, insert, update, delete, create temporary tables, execute on $custdbname . * to '$custdbname'@'$grantedhost'", $custdb);
			
			// create customer tables
			$tablequeries = explode("$$$",file_get_contents("../db/customer.sql"));
			$tablequeries = array_merge($tablequeries, explode("$$$",file_get_contents("../db/createtriggers.sql")));
			foreach ($tablequeries as $tablequery) {
				if (trim($tablequery)) {
					$tablequery = str_replace('_$CUSTOMERID_', $customerid, $tablequery);
					Query($tablequery,$custdb)
					or dieWithError("Failed to execute statement \n$tablequery\n\nfor $custdbname", $custdb);
				}
			}
			
			// subscriber db user
			createLimitedUser($limitedusername, $limitedpassword, $custdbname, $custdb, $grantedhost);
			
			// 'schoolmessenger' user
			createSMUserProfile($custdb, $custdbname);
			
			$query = "INSERT INTO `fieldmap` (`fieldnum`, `name`, `options`) VALUES
										('f01', 'First Name', 'searchable,text,firstname,subscribe,dynamic'),
										('f02', 'Last Name', 'searchable,text,lastname,subscribe,dynamic'),
										('f03', 'Language', 'searchable,multisearch,language,subscribe,static')";
			QuickUpdate($query, $custdb) or dieWithError("SQL:" . $query, $custdb);

			$query = "INSERT INTO `language` (`name`,`code`) VALUES
													('English','en'),
													('Spanish','es')";
			QuickUpdate($query, $custdb) or dieWithError("SQL:" . $query, $custdb);
			
			QuickUpdate($query, $custdb) or dieWithError(" SQL:" . $query, $custdb);
			
			// Set Session to make the save button stay on the page 
			$_SESSION['customerid']= $customerid;
		}
		
		$query = "update customer set
								enabled=?,
								nsid=?,
								notes=?
								where id = ?";
				
		QuickUpdate($query,false,array(
			$postdata["enabled"]?'1':'0',
			$postdata["nsid"],
			$postdata["notes"],
			$customerid
		));
		
		// notify authserver to refresh the customer cache
		refreshCustomer($customerid);
		
		$shardinfo = QuickQueryRow("select s.dbhost, s.dbusername, s.dbpassword from shard s inner join customer c on (c.shardid = s.id) where c.id = ?", true,false,array($customerid));
		$sharddb = DBConnect($shardinfo["dbhost"], $shardinfo["dbusername"], $shardinfo["dbpassword"], "aspshard");
		if(!$sharddb) {
			exit("Connection failed for customer: $customerid, shardhost: {$shardinfo["dbhost"]}");
		}
		
		// if timezone changed (rare occurance, but we must update scheduled jobs and report records on the shard database)
		if ($postdata["timezone"] != getCustomerSystemSetting('timezone', false, true, $custdb)) {
			QuickUpdate("update qjob set timezone=? where customerid=?", $sharddb, array($postdata["timezone"],$customerid));
			QuickUpdate("update qschedule set timezone=? where customerid=?", $sharddb,array($postdata["timezone"],$customerid));
			QuickUpdate("update qreportsubscription set timezone=? where customerid=?", $sharddb,array($postdata["timezone"],$customerid));
		}
		
		if (!$postdata["enabled"]) {
			setCustomerSystemSetting("disablerepeat", "1", $custdb);
			setCustomerSystemSetting("_customerenabled", "0", $custdb);
			// Remove active import alerts but leave the alert rules since they will not trigger for disabled customers
			QuickUpdate("delete from importalert where customerid=?", $sharddb, array($customerid));
		} else {
			setCustomerSystemSetting("_customerenabled", "1", $custdb);
		}
		
		setCustomerSystemSetting('timezone', $postdata["timezone"], $custdb);
		setCustomerSystemSetting('displayname', $postdata["displayname"], $custdb);
		
		
		if (!isset($products["cs"])) {
			if ($postdata["commsuite"]) {
				// Create all commsuite specific 
// 				$tablequeries = explode("$$$",file_get_contents("../db/targetedmessages.sql"));
// 				foreach ($tablequeries as $tablequery) {
// 					if (trim($tablequery)) {
// 						$tablequery = str_replace('_$CUSTOMERID_', $customerid, $tablequery);
// 						Query($tablequery,$custdb)
// 						or dieWithError("Failed to execute statement \n$tablequery\n\nfor $custdbname", $custdb);
// 					}
// 				}
				
// 				$query = "INSERT INTO `jobtype` (`name`, `systempriority`, `info`, `issurvey`, `deleted`) VALUES
// 														('Emergency', 1, 'Emergencies Only', 0, 0),
// 														('Attendance', 2, 'Attendance', 0, 0),
// 														('General', 3, 'General Announcements', 0, 0),
// 														('Survey', 3, 'Surveys', 1, 0)";
				
// 				QuickUpdate($query, $custdb) or dieWithError(" SQL:" . $query, $custdb);
				
// 				$query = "INSERT INTO `jobtypepref` (`jobtypeid`,`type`,`sequence`,`enabled`) VALUES
// 														(1,'phone',0,1),
// 														(1,'email',0,1),
// 														(1,'sms',0,1),
// 														(2,'phone',0,1),
// 														(2,'email',0,1),
// 														(2,'sms',0,1),
// 														(3,'phone',0,1),
// 														(3,'email',0,1),
// 														(3,'sms',0,1),
// 														(4,'phone',0,1),
// 														(4,'email',0,1),
// 														(4,'sms',0,0)";
				
// 				QuickUpdate($query, $custdb) or dieWithError(" SQL:" . $query, $custdb);
					
// 				// Login Picture
// 				QuickUpdate("INSERT INTO content (contenttype, data) values
// 														('image/gif', '" . base64_encode(file_get_contents("mimg/classroom_girl.jpg")) . "')",$custdb);
// 				$loginpicturecontentid = $custdb->lastInsertId();
					
// 				$query = "INSERT INTO `setting` (`name`, `value`) VALUES
// 														('_loginpicturecontentid', '" . $loginpicturecontentid . "')";
// 				QuickUpdate($query, $custdb) or dieWithError(" SQL: " . $query, $custdb);
					
// 							// Subscriber Login Picture
// 				QuickUpdate("INSERT INTO content (contenttype, data) values
// 														('image/gif', '" . base64_encode(file_get_contents("mimg/header_highered3.gif")) . "')",$custdb);
// 							$subscriberloginpicturecontentid = $custdb->lastInsertId();
					
// 				$query = "INSERT INTO `setting` (`name`, `value`) VALUES
// 														('_subscriberloginpicturecontentid', '" . $subscriberloginpicturecontentid . "')";
// 				QuickUpdate($query, $custdb) or dieWithError(" SQL: " . $query, $custdb);
					
// 				// Classroom Message Category
// 				$query = "INSERT INTO `targetedmessagecategory` (`id`, `name`, `deleted`, `image`) VALUES
// 														(1, 'Default', 0, 'blue dot')";
// 							QuickUpdate($query, $custdb) or dieWithError(" SQL: " . $query, $custdb);
				
// 				// set global to customer db, restore after this section
// 				global $_dbcon;
// 				$savedbcon = $_dbcon;
// 				$_dbcon = $custdb;
				
// 				// Default Email Templates
// 				if (!createDefaultTemplates())
// 					return false;
				
// 				// restore global db connection
// 				$_dbcon = $savedbcon;
				
				// Create commsuite stuff
				$query = "INSERT INTO `customerproduct` (`customerid`,`product`,`createdtimestamp`,`modifiedtimestamp`,`enabled`) VALUES
																	(?,'cs',?,?,1)";
				QuickUpdate($query, false,array($customerid,time(),time()));
			}
		} else {
			QuickUpdate("update customerproduct set enabled=?, modifiedtimestamp=? where customerid=? and product='cs'",false,array($postdata["commsuite"]?'1':'0',time(),$customerid));
		}
		
		if (!isset($products["tai"])) {
			if ($postdata["tai"]) {
				// Create commsuite stuff
				$query = "INSERT INTO `customerproduct` (`customerid`,`product`,`createdtimestamp`,`modifiedtimestamp`,`enabled`) VALUES
																	(?,'tai',?,?,1)";
				QuickUpdate($query, false,array($customerid,time(),time()));
			}
		} else {
			QuickUpdate("update customerproduct set enabled=?, modifiedtimestamp=? where customerid=? and product='tai'",false,array($postdata["tai"]?'1':'0',time(),$customerid));
		}
		
		
		Query("COMMIT");
		if($button == "done") {
			if ($ajax)
				$form->sendTo("allcustomers.php");
			else
				redirect("allcustomers.php");
		} else {
			if ($ajax)
				$form->sendTo("customereditgeneral.php");
			else
				redirect("customereditgeneral.php");
		}
	}
}

////////////////////////////////////////////////////////////////////////////////
// Display Functions
////////////////////////////////////////////////////////////////////////////////


////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$TITLE = $customerid?_L('Edit Customer'):_L('New Customer');
$PAGE = "overview:editcustomer";

include_once("nav.inc.php");

// Optional Load Custom Form Validators
?>

<script type="text/javascript">

document.observe('dom:loaded', function() {
	$('newcustomer_enabled').observe("change", function (event) {
		//var checkbox = event.Element();
		var checkbox = $('newcustomer_enabled');
		if (checkbox.checked == 0) 
			checkbox.checked = !confirm("Are you sure you want to DISABLE this customer?");
	});
});

<? Validator::load_validators(array());?>
</script>
<?

startWindow($customerid?_L('Edit Customer'):_L('New Customer'));
echo $form->render();
endWindow();
include_once("navbottom.inc.php");
?>