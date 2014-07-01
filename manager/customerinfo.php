<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("common.inc.php");
require_once("../inc/table.inc.php");
require_once("../inc/html.inc.php");
require_once("../inc/utils.inc.php");
require_once("../obj/Validator.obj.php");
require_once("../obj/Form.obj.php");
require_once("../obj/FormItem.obj.php");

////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////

if (! isset($_GET['customerid']) && !$MANAGERUSER->authorized("newcustomer"))
	exit("Not Authorized");

if (!$MANAGERUSER->authorized("editcustomer")) {
	unset($_SESSION['customerid']);
	exit("Not Authorized");
}

////////////////////////////////////////////////////////////////////////////////
// Action/Request Processing
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
	'_hasphonetargetedmessage' => '0',
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

if (isset($_GET['id'])) {
	$customerid = DBSafe($_GET['id']);
	$query = "
select
	s.dbhost,
	c.urlcomponent,
	c.enabled,
	c.oem,
	c.oemid,
	c.nsid,
	c.notes,
	s.dbusername as shardusername,
	s.dbpassword as shardpassword,
	group_concat(p.product) as products
from
	customer c
	inner join shard s on (c.shardid = s.id)
	left join customerproduct p on (c.id = p.customerid and p.enabled)
where
	c.id = '$customerid'
	";
	$custinfo = QuickQueryRow($query,true);

	// connect to customer database as the shard user (needed to create tables for new products)
	$custdb = DBConnect($custinfo["dbhost"], $custinfo["shardusername"], $custinfo["shardpassword"], "c_$customerid");
	if (!$custdb) {
		exit("Connection failed for customer: {$custinfo["dbhost"]}, db: c_$customerid, as shard user");
	}

	$query = "select name,value from setting";
	$settings = array_merge($settings, QuickQueryList($query,true,$custdb));
}


////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$PAGE = "overview";
$TITLE = _L('Customer Information');

include_once("nav.inc.php");

startWindow(_L('Customer Information'));
?>

<h2>General</h2>
<hr size="1"/>

<?

print "dbhost=[{$custinfo['dbhost']}]";
print_r($custinfo);
print_r($settings);

$products = explode(',', $custinfo['products']);
?>
<br/><br/>

<?
if (in_array('cs', $products)) {
?>
<h2>CommSuite</h2>
<hr size="1"/>
CommSuite Specific stuffs<br/>
<br/><br/>
<?
}

if (in_array('tai', $products)) {
?>
<h2>TalkAboutIt</h2>
<hr size="1"/>
TalkAboutIt Specific stuffs<br/>
<br/><br/>
<?
}
endWindow();
include_once("navbottom.inc.php");
?>
