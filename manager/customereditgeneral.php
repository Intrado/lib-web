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
require_once("inc/customersetup.inc.php");
require_once("../inc/themes.inc.php");
require_once("../obj/FormBrandTheme.obj.php");
require_once("../obj/ValSmsText.val.php");
require_once("XML/RPC.php");
require_once("authclient.inc.php");
require_once("../obj/Person.obj.php");
require_once("../obj/User.obj.php");
require_once("../obj/Organization.obj.php");
require_once("obj/ValUrlComponent.val.php");


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

$formdata["urlcomponent"] =	array(
	"label" => _L('URL Path'),
	"value" => $settings['urlcomponent'],
	"validators" => array(
		array("ValRequired"),
		array("ValLength","max" => 30),
		array("ValUrlComponent", "customerid" => $customerid, "urlcomponent" => $settings['urlcomponent'])
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
			$custdb = createnewcustomer($postdata["shard"]);
			$customerid = $_SESSION['customerid'];
		}
		
		$query = "update customer set
								urlcomponent = ?,
								enabled=?,
								nsid=?,
								notes=?
								where id = ?";
				
		QuickUpdate($query,false,array(
			$postdata["urlcomponent"],
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
		setCustomerSystemSetting('urlcomponent', $postdata["urlcomponent"], $custdb);
		
		if (!isset($products["cs"])) {
			if ($postdata["commsuite"]) {
				// Add Commsuite to customer product from all new customers
				$query = "INSERT INTO `customerproduct` (`customerid`,`product`,`createdtimestamp`,`modifiedtimestamp`,`enabled`) VALUES (?,'cs',?,?,1)";
				QuickUpdate($query, false,array($customerid,time(),time()));
 			}
		} else {
			QuickUpdate("update customerproduct set enabled=?, modifiedtimestamp=? where customerid=? and product='cs'",false,array($postdata["commsuite"]?'1':'0',time(),$customerid));
		}
		
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

<? Validator::load_validators(array("ValUrlComponent"));?>
</script>
<?

startWindow($customerid?_L('Edit Customer'):_L('New Customer'));
echo $form->render();
endWindow();
include_once("navbottom.inc.php");
?>