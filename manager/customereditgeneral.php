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
require_once("../obj/ValSmsText.val.php");
require_once("XML/RPC.php");
require_once("authclient.inc.php");
require_once("../obj/Person.obj.php");
require_once("../obj/User.obj.php");
require_once("../obj/Organization.obj.php");
require_once("obj/ValUrlComponent.val.php");
require_once("obj/ValUrl.val.php");
require_once("obj/LogoRadioButton.fi.php");
require_once("obj/LanguagesItem.fi.php");
require_once("obj/ValInboundNumber.val.php");
require_once("inc/customersetup.inc.php");
require_once("loadtaitemplatedata.php");

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

class CustomerProduct {
	var $product;
	var $createdtimestamp;
	var $modifiedtimestamp;
	var $enabled;
}


////////////////////////////////////////////////////////////////////////////////
// Form Data
////////////////////////////////////////////////////////////////////////////////

// default settings
$settings = array(
	'_dmmethod' => '',
	'timezone' => '',
	'displayname' => '',
	'organizationfieldname' => 'School',
	'urlcomponent' => '',
	'_logocontentid' => '',
	'_logoclickurl' => '',
	'_productname' => '',
	'_supportemail' => '',
	'_supportphone' => '',
	'callerid' => '',
	'defaultareacode' => '',
	'inboundnumber' => '',
	'maxguardians' => '0',
	'maxphones' => '1',
	'maxemails' => '1',
	'emaildomain' => '',
	'tinydomain' => '',
	'softdeletemonths' => '6',
	'harddeletemonths' => '24',
	'disablerepeat' => '0',
	'_hassurvey' => '0',
	'surveyurl' => '',
	'_hassms' => '0',
	'maxsms' => '1',
	'smscustomername' => '',
	'enablesmsoptin' => '0',
	'_hassmapi' => '0',
	'_hascallback' => '0',
	'callbackdefault' => 'inboundnumber',
	'_hasldap' => '0',
	'_hasenrollment' => '0',
	'_hastargetedmessage' => '0',
	'_hasselfsignup' => '',
	'_hasportal' => '',
	'_hasfacebook' => '0',
	'_hastwitter' => '0',
	'_hasfeed' => '0',
	'autoreport_replyname' => 'SchoolMessenger',
	'autoreport_replyemail' => 'autoreport@system.schoolmessenger.com',
	'_renewaldate' => '',
	'_callspurchased' => '',
	'_maxusers' => '',
	'_timeslice' => '450',
	'loginlockoutattempts' => '5',
	'logindisableattempts' => '0',
	'loginlockouttime' => '5',
	'_amdtype' => "ivr"
);

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

$customerid = null;
if (isset($_SESSION['customerid'])) {
	$customerid = $_SESSION['customerid'];
	$query = "select s.dbhost, c.urlcomponent, c.enabled, c.oem, c.oemid, c.nsid, c.notes, s.dbusername as shardusername, s.dbpassword as shardpassword from customer c inner join shard s on (c.shardid = s.id) where c.id = '$customerid'";
	$custinfo = QuickQueryRow($query,true);
	// connect to customer database as the shard user (needed to create tables for new products)
	$custdb = DBConnect($custinfo["dbhost"], $custinfo["shardusername"], $custinfo["shardpassword"], "c_$customerid");
	if (!$custdb) {
		exit("Connection failed for customer: {$custinfo["dbhost"]}, db: c_$customerid, as shard user");
	}

	$query = "select name,value from setting";
	$settings = array_merge($settings, QuickQueryList($query,true,$custdb));
	
	$query = "select product, enabled from customerproduct where customerid=?";
	$products = QuickQueryList($query,true,false,array($_SESSION['customerid']));
}


$logos = array(); 
if ($customerid && $settings['_logocontentid'] != '') {
	$logos = array( "Saved" => 'No change - Preview: <div style="display:inline;"><img src="customerlogo.img.php?id=' . $customerid .'" width="70px" alt="" /></div>');
}
// Content for logo selector
$logos += array( 	"AutoMessenger" => '<img src="mimg/auto_messenger.jpg" alt="AutoMessenger" title="AutoMessenger" />',
					"SchoolMessenger" => '<img src="mimg/logo_small.gif" alt="SchoolMessenger" title="SchoolMessenger" />',
					"Skylert" => '<img src="mimg/sky_alert.jpg" alt="Skylert" title="Skylert" />');

// Locations and mimetype for default logos
$defaultlogos = array(
					"AutoMessenger" => array("filelocation" => "mimg/auto_messenger.jpg",
											"filetype" => "image/jpeg"),
					"SchoolMessenger" => array("filelocation" => "mimg/logo_small.gif",
											"filetype" => "image/gif"),
					"Skylert" => array("filelocation" => "mimg/sky_alert.jpg",
										"filetype" => "image/jpeg")
);


$shards = QuickQueryList("select id, name from shard where not isfull order by id",true);

$dmmethod = array('' => '--Choose a Method--', 'asp' => 'CommSuite (fully hosted)','hybrid' => 'CS + SmartCall + Emergency','cs' => 'CS + SmartCall (data only)');

if ($customerid)
	$shortcodegroupname = QuickQuery("select description from shortcodegroup where id = (select shortcodegroupid from customer where id = ?)", null, array($customerid));
else
	$shortcodegroupname = QuickQuery("select description from shortcodegroup where id = 1"); // hardcoded id=1 is the default group for new customers


$helpstepnum = 1;

$formdata = array(_L('Products'));
$formdata["commsuite"] = array(
	"label" => _L('CommSuite'),
	"value" => isset($products['cs'])?$products['cs']:0,
	"validators" => array(),
	"control" => array("CheckBox"),
	"helpstep" => $helpstepnum
);

/*
$formdata["contactmanager"] = array(
	"label" => _L('Contact Manager'),
	"value" => isset($products['cm'])?$products['cm']:0,
	"validators" => array(),
	"control" => array("CheckBox"),
	"helpstep" => $helpstepnum
);
*/
$formdata["tai"] = array(
	"label" => _L('Talk About It'),
	"value" => isset($products['tai'])?$products['tai']:0,
	"validators" => array(),
	"control" => array("CheckBox"),
	"helpstep" => $helpstepnum
);


$formdata[] = _L('Basics');

include("inc/customerRequiredFormItems.inc.php");

$thispage = "customereditgeneral.php";
$returntopage = "allcustomers.php";

$buttons = array(submit_button(_L("Save"),"save","tick"),submit_button(_L("Save and Return"),"done","tick"),
				icon_button(_L('Cancel'),"cross",null,$returntopage));
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
			$custdb = createnewcustomer($postdata["shard"]);
			$customerid = $_SESSION['customerid'];
		}
		
		saveRequiredFields($custdb,$customerid,$postdata);
		
		
		$query = "select product, enabled from customerproduct where customerid=?";
		$products = QuickQueryList($query,true,false,array($customerid));
		
		// CommSuite
		if (!isset($products["cs"])) {
			if ($postdata["commsuite"]) {
				// Add Commsuite to customer product from all new customers
				$query = "INSERT INTO `customerproduct` (`customerid`,`product`,`createdtimestamp`,`modifiedtimestamp`,`enabled`) VALUES (?,'cs',?,?,1)";
				QuickUpdate($query, false,array($customerid,time(),time()));
 			}
		} else {
			QuickUpdate("update customerproduct set enabled=?, modifiedtimestamp=? where customerid=? and product='cs'",false,array($postdata["commsuite"]?'1':'0',time(),$customerid));
		}
		
		// Contact Manger
		if (!isset($products["cm"])) {
			if ($postdata["contactmanager"]) {
				// Add Commsuite to customer product from all new customers
				$query = "INSERT INTO `customerproduct` (`customerid`,`product`,`createdtimestamp`,`modifiedtimestamp`,`enabled`) VALUES (?,'cm',?,?,1)";
				QuickUpdate($query, false,array($customerid,time(),time()));
 			}
		} else {
			QuickUpdate("update customerproduct set enabled=?, modifiedtimestamp=? where customerid=? and product='cm'",false,array($postdata["contactmanager"]?'1':'0',time(),$customerid));
		}
		
		// Talk About It
		if (!isset($products["tai"])) {
			if ($postdata["tai"]) {
				
				// initial customer setup for tai product enablement
				$savedbcon = $_dbcon;
				$_dbcon = $custdb;
				tai_setup($customerid);
				$_dbcon = $savedbcon;
				
				$query = "INSERT INTO `customerproduct` (`customerid`,`product`,`createdtimestamp`,`modifiedtimestamp`,`enabled`) VALUES
																	(?,'tai',?,?,1)";
				QuickUpdate($query, false,array($customerid,time(),time()));
			}
		} else {
			QuickUpdate("update customerproduct set enabled=?, modifiedtimestamp=? where customerid=? and product='tai'",false,array($postdata["tai"]?'1':'0',time(),$customerid));
		}
		
		// products saved in customer setting, as well as authserver.customerproduct table
		$query = "select product, createdtimestamp, modifiedtimestamp, enabled from customerproduct where customerid = ?";
		$rows = QuickQueryMultiRow($query, false, null, array($customerid));
		$customerproducts = array();
		foreach ($rows as $row) {
			$cp = new CustomerProduct();
			$cp->product = $row[0];
			$cp->createdtimestamp = $row[1];
			$cp->modifiedtimestamp = $row[2];
			$cp->enabled = $row[3];
			$customerproducts[] = $cp;
		}
		setCustomerSystemSetting('_products', json_encode($customerproducts), $custdb);
				
		Query("COMMIT");
		if($button == "done") {
			if ($ajax)
				$form->sendTo($returntopage);
			else
				redirect($returntopage);
		} else {
			if ($ajax)
				$form->sendTo($thispage);
			else
				redirect($thispage);
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
	$('newcustomer_logo').observe("change", function (event) {
		var e = event.element();
		var savedname = '<?= $settings['_productname'] ?>';
		$('newcustomer_productname').value = (e.value && e.type == "radio" && e.value != "Other" && e.value != "Saved")?e.value:savedname;
	});
	
	$('newcustomer_displayname').observe("change", function (event) {
		var e = event.element();
		if ($('newcustomer_hassms').checked) {
			$('newcustomer_smscustomername').value = e.value;
		}
	});
	$('newcustomer_hassms').observe("change", function (event) {
		if ($('newcustomer_hassms').checked) {
			$('newcustomer_enablesmsoptin').checked = 1;
			$('newcustomer_smscustomername').value = $('newcustomer_displayname').value;
		} else {
			$('newcustomer_enablesmsoptin').checked = 0;
			$('newcustomer_smscustomername').value = '';
		}
	});
	$('newcustomer_enabled').observe("change", function (event) {
		//var checkbox = event.Element();
		var checkbox = $('newcustomer_enabled');
		if (checkbox.checked == 0) 
			checkbox.checked = !confirm("Are you sure you want to DISABLE this customer?");
	});
});
<? Validator::load_validators(array("ValInboundNumber","ValUrlComponent","ValSmsText","ValLanguages","ValUrl"));?>
</script>
<?

startWindow($TITLE);
echo $form->render();
endWindow();
include_once("navbottom.inc.php");
?>
