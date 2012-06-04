<?
require_once("common.inc.php");
require_once("../inc/table.inc.php");

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

	$urlget = "taicustomers.php" . (isset($urlget) ? "$urlget&" : "?");

	if ($MANAGERUSER->preference("favcustomers") && in_array($row[0],$MANAGERUSER->preference("favcustomers")))
		return "<a title='Remove Favorite' href='$urlget" . "removefavorites={$row[0]}'><img style='margin-right: 4px;' src='mimg/removefav.png' border=0/></a>" . $row[0];
	else
		return "<a title='Add Favorite' href='$urlget" . "addfavorites={$row[0]}'><img style='margin-right: 4px;' src='mimg/addfav.png' border=0/></a>" . $row[0];
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
		return escapehtml($row[2]) . " (<a href='customerlink.php?id=" . $row[0] ."' target=\"_blank\">" . escapehtml($row[1]) . "</a>)";
	else
		return escapehtml($row[2] . " (" . $row[1] . ")");
}

function fmt_actions($row, $index) {
	$dmmethod = "system";
	if ($row[11] != 'asp')
		$dmmethod = "customer";
		
	global $MANAGERUSER;
	$actions = '<div class="actionlinks">';
	if ($MANAGERUSER->authorized("users"))
		$actions .= '<a href="userlist.php?customer=' . $row[0] . '" title="Users"><img src="mimg/s-users.png" border=0></a>';
	if ($MANAGERUSER->authorized("imports"))
		$actions .= '<a href="customerimports.php?customer=' . $row[0] . '" title="Imports"><img src="mimg/s-imports.png" border=0></a>';
	if ($MANAGERUSER->authorizedAny(array("ffield2gfield","billablecalls","edittemplate","runqueries")))
		$actions .= '<a href="advancedcustomeractions.php?cid=' . $row[0] . '" title="Advanced Actions"><img src="mimg/s-config.png" border=0></a>';

	$actions .= '</div>';
	return $actions;
}

////////////////////////////////////////////////////////////////////////////////
// request handling
////////////////////////////////////////////////////////////////////////////////

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

// SHOW DISABLED
if (isset($_GET["showdisabled"]))
	$sqltoggledisabled = "and not c.enabled";
else
	$sqltoggledisabled = "and c.enabled";

$favidsql = "";
if (!isset($_GET["search"]) && !isset($_GET["showall"]) && !isset($_GET["showdisabled"])) {
	//Favorite customers
	if ($MANAGERUSER->preference("favcustomers")) {
		$favidsql = "and c.id in (" . implode(",",$MANAGERUSER->preference("favcustomers")) . ")";
		$sqltoggledisabled = ""; //dont filter out disabled favorites
	}
}

$shownone = true;
if (isset($_GET["search"]) || isset($_GET["showall"]) || isset($_GET["showdisabled"]) || $MANAGERUSER->preference("favcustomers"))
	$shownone = false;

// SEARCH
$sqlsearch = "1"; // default to everything
if (isset($_GET["search"])) {
	$safesearch =  DBSafe(trim($_GET["search"]));
	if ($safesearch == "") {
		$sqlsearch = "0"; // Expect no customers.
		$shownone = true;
	} else
		$sqlsearch = "(c.id='$safesearch' or c.urlcomponent like '%$safesearch%' or c.inboundnumber='$safesearch')";
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
$query = "select c.id, c.shardid, c.urlcomponent, c.oem, c.oemid, c.nsid, c.notes, c.enabled, c.inboundnumber from customer c inner join customerproduct p on (p.customerid = c.id and p.product = 'tai' and p.enabled) where $sqlsearch $sqltoggledisabled $favidsql order by id";
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
		$row[4] = getCustomerSystemSetting('timezone', false, true, $custdb);
		$row[5] = $cust[6];
		$row[8] = QuickQuery("SELECT COUNT(*) FROM user where enabled = '1' and login != 'schoolmessenger'", $custdb);
		$row[12] = $cust[3];
		$row[13] = $cust[4];
		$row[14] = $cust[5];
		$row[15] = $cust[8];
		$row[21] = $shardinfo[$cust[1]][3];
		$row[22] = $cust[7]; //enabled
		$data[] = $row;
	}
}

if (isset($_GET["ajax"])) {
	$titles = array("0" => "#ID",
			"1" => "#URL",
			"15" => "#Inbound",
			"5" => "#Notes");

	$formatters = array("1" => "fmt_custurl");

	print "Search Quick Preview:";
	print "<table class='list sortable' id='customers_preview'>";
	showTable($data, $titles, $formatters);
	print "</table>";
	exit();
}

$titles = array("0" => "#ID",
		"21" => "@#Shard",
		"url" => "#Name",
		"4" => "#Timezone",
		"8" => "#Users",
		"Actions" => "Actions",
		"5" => "#NOTES: ",
		"14" => "#NetSuite");

$formatters = array("0" => "fmt_custid",
		"url" => "fmt_custurl",
		"Actions" => "fmt_actions");

$lockedTitles = array(0, "status", "actions");

$TITLE = "Talk About It Customers";
$PAGE = "tai:customers";
include_once("nav.inc.php");
startWindow(_L('Talk About It Customers'));

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
	obj.innerHTML = html;
}

function submitform (name) {
	// if blank, don't submit.
	if ($('searchvalue').value.replace(/^[ ]+/, '') == '') {
		$('searchpreview').innerHTML = "";
		return;
	}
	var request = 'taicustomers.php?ajax=true&search=' + $('searchvalue').value;
	cachedAjaxGet(request,setcontent,$('searchpreview'));
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

<form id="search" autocomplete="off" action="taicustomers.php" method="get">
	<? if (isset($_GET["showdisabled"]))
		print "<input type='hidden' name='showdisabled' value='1'/>";
	?>
	<input id="searchvalue" name="search" type="text" onkeyup="keyuptimer(event, 300, true, submitform, 'searchvalue');" size="30" value="<?=isset($_GET["search"]) ? escapehtml($_GET["search"]) : ""?>"/><button type="submit">Search</button> Search ID, URL Path Name, or Inbound Number
	<div id="searchpreview">
	</div>
</form>

<input id="showdisabled" type="checkbox" onclick="window.location='taicustomers.php?' + (this.checked ? 'showdisabled&' : '') <? if(isset($_GET["showall"])) print "+ 'showall'"; else if (isset($_GET["search"])) print "+ 'search=" . escapehtml($_GET['search']) . "'"; ?>;" <?= isset($_GET['showdisabled']) ? "checked" : ""?>>
<label for="showdisabled">Show Disabled</label>

<?
if (!isset($_GET["showall"]))
	echo "&nbsp;|&nbsp;<a href='taicustomers.php?showall'>Show All TalkAboutIt Customers</a> ";
else if ($MANAGERUSER->preference("favcustomers")) {
	echo "&nbsp;|&nbsp;<a href='taicustomers.php'> <img src='mimg/fav.png' border=0/>Show Favorites</a>";
	echo "&nbsp;|&nbsp;<a style='margin-left: 4px' href='?clearfavorites'><i>Clear Favorites</i></a>";
}

show_column_selector('customers_table', $titles, $lockedTitles);
?>
<hr>
<table class="list sortable" id="customers_table">
<?
if ($shownone)
	echo "<h3>Hiding customer list by default, click show all to see everyone, or use the handy search feature</h3>";
else 
	showTable($data, $titles, $formatters);
?>
</table>

<!-- Legend -->
<div>Pink cells indicate that only the system user account has been created</div>
<div>Red cells indicate that the customer has more users than they should</div>
<div>Green cells indicate customers with active jobs</div>
<?
endWindow();

include_once("navbottom.inc.php");


?>


