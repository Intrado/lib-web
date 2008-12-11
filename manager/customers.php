<?
include_once("common.inc.php");
include_once("../obj/Customer.obj.php");
include_once("../inc/table.inc.php");

////////////////////////////////////////////////////////////////////////////////
// formatters
////////////////////////////////////////////////////////////////////////////////

function fmt_custid($row, $index){
	global $favcustomers;

	if (isset($_GET["search"]))
		$urlget = "?search=" . escapehtml($_GET["search"]);
	else if (isset($_GET["showall"]) || empty($favcustomers))
		$urlget = "?showall";

	if (isset($urlget) && isset($_GET["showdisabled"]))
		$urlget .= "&showdisabled";
	else if (isset($_GET["showdisabled"]))
		$urlget = "?showdisabled";

	$urlget = "customers.php" . (isset($urlget) ? "$urlget&" : "?");

	if (!isset($favcustomers[$row[0]]))
		return "<a title='Add Favorite' href='$urlget" . "addfavorites={$row[0]}'><img style='margin-right: 4px;' src='img/addfav.png' border=0/></a>" . $row[0];
	else
		return "<a title='Remove Favorite' href='$urlget" . "removefavorites={$row[0]}'><img style='margin-right: 4px;' src='img/removefav.png' border=0/></a>" . $row[0];
}

function fmt_custurl($row, $index){
//index 1 is url
//index 2 is display name
	global $disabledcustomers;

	if (isset($disabledcustomers[$row[0]]))
		return '<span style="color: gray;">' . escapehtml($row[1]) . '</span>';

	if (isset($_GET["ajax"]))
		return escapehtml($row[1]);

	return escapehtml($row[2]) . " (<a href='customerlink.php?id=" . $row[0] ."' >" . escapehtml($row[1]) . "</a>)";
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

//row 11 is dm method
function fmt_actions($row, $index){
	$actions = '<a href="customeredit.php?id=' . $row[0] .'" title="Edit"><img src="img/s-edit.png" border=0></a>&nbsp;';
	$actions .= '<a href="userlist.php?customer=' . $row[0] . '" title="Users"><img src="img/s-users.png" border=0></a>&nbsp;';
	$actions .= '<a href="customerimports.php?customer=' . $row[0] . '" title="Imports"><img src="img/s-imports.png" border=0></a>&nbsp;';
	$actions .= '<a href="customeractivejobs.php?customer=' . $row[0] . '" title="Jobs"><img src="img/s-jobs.png" border=0></a>&nbsp;';
	$actions .= '<a href="customerpriorities.php?id=' . $row[0] . '" title="Priorities"><img src="img/s-priorities.png" border=0></a>&nbsp;';
	if($row[11] != "asp")
		$actions .= '<a href="customerdms.php?cid=' . $row[0] . '" title="DMs"><img src="img/s-rdms.png" border=0></a>';

	return $actions;
}

function fmt_jobcount($row, $index){
	if($row[$index] > 0){
		return "<div style='background-color: #ccffcc;'>" . $row[$index] . "<div>";
	} else {
		return $row[$index];
	}
}

function fmt_hasportal($row, $index){
	if($row[$index])
		return "Yes";
	else
		return "No";
}

function fmt_dmmethod($row, $index){
	if ($row[$index] === "asp") {
		return "CommSuite";
	}
	if ($row[$index] === "hybrid") {
		return "CSFlexEmerg";
	}
	if ($row[$index] === "cs") {
		return "CSFlex";
	}
	return "";
}


////////////////////////////////////////////////////////////////////////////////
// request/cookie handling
////////////////////////////////////////////////////////////////////////////////

// FAVORITES
// Favorite customers are indexed by customer ID.
if (isset($_COOKIE["favcustomers"])) {
	$favcustomers = array_flip(explode(",", $_COOKIE["favcustomers"]));
}
if (isset($_GET["addfavorites"])) {
	if (!isset($favcustomers))
		$favcustomers = array();
	$favcustomers = $favcustomers + array_flip(explode(",", preg_replace("/[ \t]+/", "", $_GET["addfavorites"])));
}
if (isset($_GET["removefavorites"]) && isset($favcustomers)) {
	$remove = explode(",", preg_replace("/[ \t]+/", "", $_GET["removefavorites"]));
	foreach ($remove as $id)
		unset($favcustomers[$id]);
}
if (isset($_GET["clearfavorites"])) {
	setcookie("favcustomers", "", strtotime("+1 week"));
	$_COOKIE['favcustomers'] = "";
	redirect("customers.php");
}
// Update/create cookie.
if (isset($favcustomers)) {
	unset($favcustomers[""]); // in case there's an empty input value
	$cids = implode(",", array_keys($favcustomers));
	setcookie("favcustomers", $cids, strtotime("+1 week"));
	$_COOKIE['favcustomers'] = $cids;
}

// SEARCH
$sqlsearch = "1=1"; // Expect all customers.
if (isset($_GET["search"]))
	$_GET["search"] = trim($_GET["search"]);
if (isset($_GET["search"]) && $_GET["search"] !== "") {
	$safesearch = DBSafe(trim($_GET["search"]));
	if ($safesearch === "")
		$sqlsearch = "1=0"; // Expect no customers.
	else
		$sqlsearch = "id='$safesearch' or urlcomponent like '%$safesearch%' or inboundnumber='$safesearch'";
}

// SHOW DISABLED
if (isset($_GET["showdisabled"]))
	$sqltoggledisabled = "not enabled and";
else
	$sqltoggledisabled = "enabled and";
if (!isset($_GET["search"]) && !isset($_GET["showall"]) && isset($favcustomers)) { // When viewing favorites, don't hide disabled customers.
	$sqltoggledisabled = "";
}


////////////////////////////////////////////////////////////////////////////////
// data handling
////////////////////////////////////////////////////////////////////////////////

// Keep an array of disabled customers, indexed by customer ID.
$disabledcustomers = array();

// First, get a list of every shard, $shardinfo[], indexed by ID, storing dbhost, dbusername, and dbpassword.
$res = Query("select id, dbhost, dbusername, dbpassword from shard order by id");
$shardinfo = array();
while($row = DBGetRow($res)){
	$shardinfo[$row[0]] = array($row[1], $row[2], $row[3]);
}

// Secondly, get a list of customers.
$customerquery = Query("select id, shardid, urlcomponent, oem, oemid, nsid, notes, enabled, inboundnumber from customer where $sqltoggledisabled ($sqlsearch) order by id");
$customers = array();
while($row = DBGetRow($customerquery)){
	// If viewing only favorite customers, skip appropriately.
	if (isset($favcustomers) && !isset($_GET["showall"]) && !isset($_GET["search"]) && !isset($favcustomers[$row[0]]))
		continue;
	
	if($row[7] == 0)
		$disabledcustomers[$row[0]] = "disabled";

	$customers[] = $row;
}

// With the list of customers ready, connect to each customer's shard and retrieve a bunch of helpful information about the customer.
$currhost = "";
$custdb;
$data = array();
foreach($customers as $cust) {
	if($currhost != $cust[1]){
		$custdb = mysql_connect($shardinfo[$cust[1]][0],$shardinfo[$cust[1]][1], $shardinfo[$cust[1]][2])
			or die("Could not connect to customer database: " . mysql_error());
		$currhost = $cust[1];
	}
	mysql_select_db("c_" . $cust[0]);
	if($custdb){
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
		$row[9] = QuickQuery("SELECT COUNT(*) FROM job INNER JOIN user ON(job.userid = user.id)
								WHERE job.status = 'active'", $custdb);
		$customerfeatures = array();

		if(getCustomerSystemSetting('_hasportal', false, true, $custdb))
			$customerfeatures[] = "ContactMgr";
		if(getCustomerSystemSetting('_hassms', false, true, $custdb))
			$customerfeatures[] = "SMS";
		if(getCustomerSystemSetting('_hassurvey', true, true, $custdb))
			$customerfeatures[] = "Survey";
		if(getCustomerSystemSetting('_hascallback', false, true, $custdb))
			$customerfeatures[] = "Callback";

		$row[10] = implode(", ", $customerfeatures);
		$row[11] = getCustomerSystemSetting('_dmmethod', "", true, $custdb);
		$row[12] = $cust[3];
		$row[13] = $cust[4];
		$row[14] = $cust[5];
		$row[15] = $cust[8];
		$data[] = $row;
	}
}

if (isset($_GET["ajax"])) {
	$titles = array("0" => "#ID",
			"1" => "#URL",
			"15" => "#Inbound",
			"5" => "#NOTES:");

	$formatters = array("1" => "fmt_custurl");

	print "Search Quick Preview:";
	print "<table class='list sortable' id='customers_preview'>";
	showTable($data, $titles, $formatters);
	print "</table>";
	exit();
}

$titles = array("0" => "#ID",
		"url" => "#Name",
		"3" => "#Product Name",
		"4" => "#Timezone",
		"6" => "#Status",
		"11" => "#DM Method",
		"10" => "#Features",
		"7" => "#Max Users",
		"8" => "#Users",
		"9" => "#Jobs",
		"Actions" => "Actions",
		"5" => "#NOTES: ",
		"12" => "@#OEM",
		"13" => "#OEM ID",
		"14" => "#NetSuite");

$formatters = array("0" => "fmt_custid",
		"url" => "fmt_custurl",
		"6" => "fmt_status",
		"8" => "fmt_users",
		"9" => "fmt_jobcount",
		"Actions" => "fmt_actions",
		"11" => "fmt_dmmethod");

$lockedTitles = array(0, "status", "actions");

include_once("nav.inc.php");
?>

<script>
//check on timeout after keyup
//clear error flag
//on timeout 250

function setcontent (html, obj) {
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
	if (document.getElementById('searchvalue').value.replace(/^[ ]+/, '') == '') {
		document.getElementById('searchpreview').innerHTML = "";
		return;
	}
	ajax('customers.php?ajax=true&' + serialize(document.getElementById(name)),null,setcontent, document.getElementById('searchpreview'));
}
</script>

<form id="search" autocomplete="off" action="customers.php" method="get">
	<? if (isset($_GET["showdisabled"]))
		print "<input type='hidden' name='showdisabled' value='1'/>";
	?>
	<input id="searchvalue" name="search" type="text" onkeyup="keyuptimer(event, 300, true, submitform, 'search');" size="30" value="<?=isset($_GET["search"]) ? escapehtml($_GET["search"]) : ""?>"/><button type="submit">Search</button> Search ID, URL Path Name, or Inbound Number
	<div id="searchpreview">
	</div>
</form>

<input id="showdisabled" type="checkbox" onclick="window.location='customers.php?' + (this.checked ? 'showdisabled&' : '') <? if(isset($_GET["showall"])) print "+ 'showall'"; else if (isset($_GET["search"])) print "+ 'search=" . escapehtml($_GET['search']) . "'"; ?>;" <?= isset($_GET['showdisabled']) ? "checked" : ""?>>
<label for="showdisabled">Show Disabled</label>

<?
if ((!isset($_GET["showall"]) && !empty($favcustomers)) && !isset($_GET["search"]))
	print "<a href='customers.php?showall'>Show All Customers</a> <a style='margin-left: 4px' href='?clearfavorites'><i>Clear Favorites</i></a>";
else
	print "<a href='customers.php'> <img src='img/fav.png' border=0/>Show Favorites</a>";

show_column_selector('customers_table', $titles, $lockedTitles);
?>

<table class="list sortable" id="customers_table">
<?
showTable($data, $titles, $formatters);
?>
</table>

<!-- Legend -->
<div>Pink cells indicate that only the system user account has been created</div>
<div>Red cells indicate that the customer has more users than they should</div>
<div>Green cells indicate customers with active jobs</div>
<?
include_once("navbottom.inc.php");


?>


