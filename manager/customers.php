<?
require_once("common.inc.php");
require_once("../inc/table.inc.php");

if (isset($_GET["newnav"])) {
	if ($_GET["newnav"] == "true") {
		$_SESSION["newnav"] = true;
	} else {
		unset($_SESSION["newnav"]);
		redirect("customers.php");
	}
}

////////////////////////////////////////////////////////////////////////////////
// formatters
////////////////////////////////////////////////////////////////////////////////

function fmt_custid($row, $index){
	global $MANAGERUSER;

	if (isset($_GET["search"]))
		$urlget = "?search=" . urlencode($_GET["search"]);
	else if (isset($_GET["showall"]) || !$MANAGERUSER->preference("favcustomers"))
		$urlget = "?showall";

	if (isset($urlget) && isset($_GET["showdisabled"]))
		$urlget .= "&showdisabled";
	else if (isset($_GET["showdisabled"]))
		$urlget = "?showdisabled";

	$urlget = "customers.php" . (isset($urlget) ? "$urlget&" : "?");

	if ($MANAGERUSER->preference("favcustomers") && in_array($row[0],$MANAGERUSER->preference("favcustomers")))
		return "<a title='Remove Favorite' href='$urlget" . "removefavorites={$row[0]}'><img style='margin-right: 4px;' src='mimg/removefav.png' border=0/></a>" . $row[0];
	else
		return "<a title='Add Favorite' href='$urlget" . "addfavorites={$row[0]}'><img style='margin-right: 4px;' src='mimg/addfav.png' border=0/></a>" . $row[0];
}

function fmt_hasdm($row, $index) {
	if($row[$index])
		return "Has SmartCall";
	else
		return "&nbsp;";
}

function fmt_custurl($row, $index){
	global $MANAGERUSER;
//index 1 is url
//index 2 is display name
	if (!$row[22])
		return '<span style="color: gray;">' . escapehtml($row[2]) . ' (' . escapehtml($row[1])  .')</span>';
	if (isset($_GET["ajax"]))
		return escapehtml($row[1]);
	
	if ($MANAGERUSER->authorized("logincustomer"))
		return escapehtml($row[2]) . " (<a class='cust_link' href='customerlink.php?id=" . $row[0] ."' target=\"_blank\">" . escapehtml($row[1]) . "</a>)";
	else
		return escapehtml($row[2] . " (" . $row[1] . ")");
}

function fmt_status($row, $index){
	if($row[$index])
		return "Repeating Jobs Disabled";
	else
		return "&nbsp;";
}

//Row[7] is the max users value
function fmt_users($row, $index){
	if($row[7] != "unlimited" && $row[$index] > $row[7]){
		return "<div style='background-color: #ff0000;'>" . $row[$index] . "</div>";
	} else if($row[$index] == 0){
		return "<div style='background-color: #ffcccc;'>" . $row[$index] . "</div>";
	} else {
		return $row[$index];
	}
}

// row 11 is dm method
function fmt_actions($row, $index) {
	$dmmethod = "system";
	if ($row[11] != 'asp')
		$dmmethod = "customer";
		
	global $MANAGERUSER;
	$actions = '<div class="actionlinks">';

	// SMK disabled access to the newly forming customer info page 2013-04-11 until we have time to come back around to it
	//$actions .= '<a href="customerinfo.php?id=' . $row[0] . '" title="Info"><img src="img/icons/information.png" border="0"/></a>';

	if ($MANAGERUSER->authorized("editcustomer"))
		$actions .= '<a href="customeredit.php?id=' . $row[0] .'" title="Edit"><img src="mimg/s-edit.png" border=0></a>';
	$actions .= '<a href="customerimages.php?id=' . $row[0] .'" alt="images" title="images"><img src="img/icons/image_edit.png" border=0></a>';
	if ($MANAGERUSER->authorized("users"))
		$actions .= '<a href="userlist.php?customer=' . $row[0] . '" title="Users"><img src="mimg/s-users.png" border=0></a>';
	if ($MANAGERUSER->authorized("imports"))
		$actions .= '<a href="customerimports.php?customer=' . $row[0] . '" title="Imports"><img src="mimg/s-imports.png" border=0></a>';
	if ($MANAGERUSER->authorized("activejobs"))
		$actions .= '<a href="customeractivejobs.php?' . $dmmethod . '&cid=' . $row[0] . '" title="Jobs"><img src="mimg/s-jobs.png" border=0></a>';
	if ($MANAGERUSER->authorized("editpriorities"))
		$actions .= '<a href="customerpriorities.php?id=' . $row[0] . '" title="Priorities"><img src="mimg/s-priorities.png" border=0></a>';
	if($row[11] != "asp" && $MANAGERUSER->authorized("editdm"))
		$actions .= '<a href="customerdms.php?cid=' . $row[0] . '" title="DMs"><img src="mimg/s-rdms.png" border=0></a>';
	if ($MANAGERUSER->authorizedAny(array("ffield2gfield","billablecalls","edittemplate","runqueries")))
		$actions .= '<a href="advancedcustomeractions.php?cid=' . $row[0] . '" title="Advanced Actions"><img src="mimg/s-config.png" border=0></a>';
	if ($MANAGERUSER->authorized("aspreportgraphs"))
    	$actions .= '<a href="customerdetails.php?customerid='. $row[0] . '" title="Customer details"><img src="img/icons/chart_bar.png" border=0></a>';
	$actions .= '</div>';
	return $actions;
}

function fmt_jobcount($row, $index){
	if($row[$index] > 0){
		return "<div style='background-color: #ccffcc;'>" . $row[$index] . "<div>";
	} else {
		return $row[$index];
	}
}

function fmt_dmmethod($row, $index){
	switch ($row[$index]) {
		case "asp": return "CommSuite";
		case "hybrid" : return "CSFlexEmerg";
		case "cs" : return "CSFlex";
	}
}

function fmt_smsoptin($row, $index){
	if ($row[$index])
		return "yes";
	return "";
}

////////////////////////////////////////////////////////////////////////////////
// request handling
////////////////////////////////////////////////////////////////////////////////

// SEARCH - SMK reworked 2013-03-28 so that if the search criteria was supplied,
// then deleted and press enter, the page displays as it does by default
// (showing favorites) instead of the less friendly "hiding search results"
// message

// FAVORITES
if (isset($_GET["addfavorites"])) {
	$MANAGERUSER->addFavCustomer($_GET["addfavorites"]);
}
if (isset($_GET["removefavorites"])) {
	$MANAGERUSER->delFavCustomer($_GET["removefavorites"]);
}
if (isset($_GET["clearfavorites"])) {
	$MANAGERUSER->setPreference("favcustomers",false);
	$MANAGERUSER->update();
}


// SHOW DISABLED - SMK reworked 2013-03-28 so that disabled customers may be
// added to the current search criteria, but do not become the exclusive search
// result - if this is not the desired behavior then the "Show" options really
// should be converted into radio-style toggles.
if (isset($_GET["showdisabled"])) {
	//$sqltoggledisabled = "and not enabled";
}
else {
	$sqltoggledisabled = "and enabled";
}

$shownone = true;
if (isset($_GET["search"]) || isset($_GET["showall"]) || isset($_GET["showdisabled"]) || $MANAGERUSER->preference("favcustomers"))
	$shownone = false;

$sqlsearch = "1"; // default to everything
if (isset($_GET["search"])) {
	$safesearch =  DBSafe(trim($_GET["search"]));
	if ($safesearch == "") {
		$sqlsearch = "1"; // Expect no customers.
	} else
		$sqlsearch = "(id='$safesearch' or urlcomponent like '%$safesearch%' or inboundnumber='$safesearch')";
}

$favidsql = "";
if (
	(! (isset($_GET["search"]) && strlen($_GET["search"]))) &&
	(! isset($_GET["showall"])) &&
	(! isset($_GET["showdisabled"])) &&
	($sqlsearch == '1')
) {
	//Favorite customers
	if ($MANAGERUSER->preference("favcustomers")) {
		$favidsql = "and id in (" . implode(",",$MANAGERUSER->preference("favcustomers")) . ")";
		$sqltoggledisabled = ""; //dont filter out disabled favorites
	}
}

////////////////////////////////////////////////////////////////////////////////
// data handling
////////////////////////////////////////////////////////////////////////////////


global $_dbcon;

// First, get a list of every shard, $shardinfo[], indexed by ID, storing dbhost, dbusername, and dbpassword.
$res = Query("select id, dbhost, dbusername, dbpassword, name from shard order by id");
$shardinfo = array();
while($row = DBGetRow($res)){
	$shardinfo[$row[0]] = array($row[1], $row[2], $row[3], $row[4]);
}

// Secondly, get a list of customers.
$query = "select id, shardid, urlcomponent, oem, oemid, nsid, notes, enabled, inboundnumber from customer where $sqlsearch $sqltoggledisabled $favidsql order by id";
$customerquery = Query($query);
$customers = array();
if (!$shownone) {
	while ($row = DBGetRow($customerquery)) {
		$customers[] = $row;
	}
}
// With the list of customers ready, connect to each customer's shard and retrieve a bunch of helpful information about the customer.
$currhost = "";
$custdb; // customer database, using shard connection
$data = array();
foreach ($customers as $cust) {
	if ($currhost != $cust[1]) {
		$dsn = 'mysql:dbname=c_'.$cust[0].';host='.$shardinfo[$cust[1]][0];
		$custdb = new PDO($dsn, $shardinfo[$cust[1]][1], $shardinfo[$cust[1]][2]);
		$custdb->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
		$currhost = $cust[1];
	}
	
	if ($custdb) {
		$custdb->query("use c_".$cust[0]);
		
		$row = array();
		$row[0] = $cust[0];
		$row[1] = $cust[2];
		$row[2] = getCustomerSystemSetting('displayname', false, true, $custdb);
		$row[3] = getCustomerSystemSetting('_productname', false, true, $custdb);
		$row[4] = getCustomerSystemSetting('timezone', false, true, $custdb);
		$row[5] = $cust[6];
		$row[6] = getCustomerSystemSetting('disablerepeat', false, true, $custdb);

		$row[7] = getCustomerSystemSetting('_maxusers', false, true, $custdb);

		$row[8] = QuickQuery("SELECT COUNT(*) FROM user where enabled = '1' and login != 'schoolmessenger'", $custdb);
		$row[9] = QuickQuery("SELECT COUNT(*) FROM job INNER JOIN user ON(job.userid = user.id)	WHERE job.status = 'active'", $custdb);
		
		$customerfeatures = array();

		if (getCustomerSystemSetting('_hasportal', false, true, $custdb))
			$customerfeatures[] = "ContactMgr";
		if (getCustomerSystemSetting('_hasldap', false, true, $custdb))
			$customerfeatures[] = "LDAP";
		if (getCustomerSystemSetting('_hassms', false, true, $custdb))
			$customerfeatures[] = "SMS";
		if (getCustomerSystemSetting('_hassmapi', false, true, $custdb))
			$customerfeatures[] = "SMAPI";
		if (getCustomerSystemSetting('_hassurvey', true, true, $custdb))
			$customerfeatures[] = "Survey";
		if (getCustomerSystemSetting('_hascallback', false, true, $custdb))
			$customerfeatures[] = "Callback";
		if (getCustomerSystemSetting('_hasselfsignup', false, true, $custdb))
			$customerfeatures[] = "Self-Signup";
		if (getCustomerSystemSetting('_hasenrollment', false, true, $custdb))
			$customerfeatures[] = "Enrollment";
		if (getCustomerSystemSetting('_hastargetedmessage', false, true, $custdb))
			$customerfeatures[] = "Classroom";
		if (getCustomerSystemSetting('_hasfacebook', false, true, $custdb))
			$customerfeatures[] = "Facebook";
		if (getCustomerSystemSetting('_hastwitter', false, true, $custdb))
			$customerfeatures[] = "Twitter";
		if (getCustomerSystemSetting('_hasfeed', false, true, $custdb))
			$customerfeatures[] = "Feed";

		$row[10] = implode(", ", $customerfeatures);
		$row[11] = getCustomerSystemSetting('_dmmethod', "", true, $custdb);
		$row[12] = $cust[3];
		$row[13] = $cust[4];
		$row[14] = $cust[5];
		$row[15] = $cust[8];
		$row[16] = QuickQuery("SELECT COUNT(*) FROM custdm", $custdb);
		$row[17] = getCustomerSystemSetting('_timeslice', false, true, $custdb);
		$row[18] = getCustomerSystemSetting('emaildomain', false, true, $custdb);
		$row[19] = getCustomerSystemSetting('autoreport_replyname', false, true, $custdb);
		$row[20] = getCustomerSystemSetting('autoreport_replyemail', false, true, $custdb);
		$row[21] = $shardinfo[$cust[1]][3];
		$row[22] = $cust[7]; //enabled
		$row[23] = getCustomerSystemSetting('enablesmsoptin', false, true, $custdb);
		$data[] = $row;
	}
}

if (isset($_GET["ajax"])) {
	$titles = array(
		"0" => "#ID",
		"21" => "#Shard",
		"1" => "#URL",
		"15" => "#Inbound",
		"5" => "#Notes"
	);

	$formatters = array("1" => "fmt_custurl");

	print "Search Quick Preview:";
	print "<table class='list sortable' id='customers_preview'>";
	showTable($data, $titles, $formatters);
	print "</table>";
	exit();
}

$titles = array(
	"0" => "#ID",
	"21" => "#Shard",
	"url" => "#Name",
	"3" => "@#Product Name",
	"4" => "@#Timezone",
	"6" => "@#Status",
	"15" => "@#Inbound",
	"16" => "@#Has SC DM",
	"11" => "@#DM Method",
	"23" => "@#SMS Opt-in",
	"10" => "#Features",
	"7" => "@#Max Users",
	"8" => "@#Users",
	"9" => "@#Jobs",
	"17" => "@#Timeslice",
	"18" => "@#Email Domain",
	"19" => "@#AutoReport Name",
	"20" => "@#AutoReport Addr",
	"Actions" => "Actions",
	"5" => "#NOTES: ",
	"12" => "@#OEM",
	"13" => "@#OEM ID",
	"14" => "#NetSuite"
);

$formatters = array(
	"0" => "fmt_custid",
	"url" => "fmt_custurl",
	"6" => "fmt_status",
	"8" => "fmt_users",
	"9" => "fmt_jobcount",
	"Actions" => "fmt_actions",
	"11" => "fmt_dmmethod",
	"23" => "fmt_smsoptin",
	"16" => "fmt_hasdm"
);

$lockedTitles = array(0, "status", "actions");

$TITLE = _L("Commsuite Customers");
$PAGE = "commsuite:customers";

include_once("nav.inc.php");

startWindow(_L('Customer Search'));

?>

<script>
//check on timeout after keyup
//clear error flag
//on timeout 250

function setcontent (response, obj) {
	var html = response.responseText;
	// no search results
	if (html == " ") { 
		obj.innerHTML = "";
		return;
	}

	show(obj.id);
	//obj.innerHTML = "<a href='javascript: hide(\"" + obj.id + "\"); undefined;'> Hide </a>";
	//obj.innerHTML += html;
	obj.innerHTML = html;
}

function submitform (name) {
	// if blank, don't submit.
	if ($('searchvalue').value.replace(/^[ ]+/, '') == '') {
		$('searchpreview').innerHTML = "";
		return;
	}
	var request = 'customers.php?ajax=true&search=' + $('searchvalue').value;
	cachedAjaxGet(request,setcontent,$('searchpreview'));
	
	//ajax('customers.php?ajax=true&' + serialize(document.getElementById(name)),null,setcontent, document.getElementById('searchpreview'));
}

function keyuptimer (e, t, ignoreenterkey, fn, args) {
	if (this.timeoutid)
		clearTimeout(this.timeoutid);
	var e=window.event || e;
	var keyunicode=e.charCode || e.keyCode;
	if (keyunicode != 13 || !ignoreenterkey)
		this.timeoutid = setTimeout(fn,t,args);
}
</script>

<form id="search" autocomplete="off" action="customers.php" method="get">
	<? if (isset($_GET["showdisabled"]))
		print '<input type="hidden" name="showdisabled" value="1"/>';
	?>
	<input id="searchvalue" name="search" type="text" onkeyup="keyuptimer(event, 300, true, submitform, 'searchvalue');" size="30" value="<?=isset($_GET["search"]) ? escapehtml($_GET["search"]) : ""?>"/><button type="submit">Search</button> Search ID, URL Path Name, or Inbound Number
	<div id="searchpreview">
	</div>
</form>

<?
endWindow();
startWindow(_L('Commsuite Customers'));
?>

Show: 

<?
if (!isset($_GET["showall"]))
	print '<a class="cust_link" href="customers.php?showall">All Customers</a> | ';
else if ($MANAGERUSER->preference("favcustomers")) {
	echo '<a href="customers.php"> <!--img src="mimg/fav.png" border=0/-->Favorites</a> | ';
	echo '<a style="margin-left: 4px" href="?clearfavorites"><i>Clear Favorites</i></a> | ';
}
?>

<label for="showdisabled">Include Disabled</label> <input id="showdisabled" type="checkbox" onclick="window.location='customers.php?' + (this.checked ? 'showdisabled&' : '') <? if(isset($_GET["showall"])) print "+ 'showall'"; else if (isset($_GET["search"])) print "+ 'search=" . escapehtml($_GET['search']) . "'"; ?>;" <?= isset($_GET['showdisabled']) ? "checked" : ""?>>

<?
//show_column_selector('customers_table', $titles, $lockedTitles);
?>
<hr />
<table class="list sortable" id="customers_table">
<?
if ($shownone)
	echo "<h3>Hiding customer list by default, click show all to see everyone, or use the handy search feature</h3>";
else 
	showTable($data, $titles, $formatters);
?>
</table>

<?
include_once("navbottom.inc.php");
?>


