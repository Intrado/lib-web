<?
require_once("common.inc.php");
require_once("../inc/table.inc.php");
require_once("../inc/html.inc.php");

if (isset($_GET["newnav"])) {
	if ($_GET["newnav"] == "true") {
		$_SESSION["newnav"] = true;
	} else {
		unset($_SESSION["newnav"]);
		redirect("index.php");
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

	$urlget = "allcustomers.php" . (isset($urlget) ? "$urlget&" : "?");

	if ($MANAGERUSER->preference("favcustomers") && in_array($row["id"],$MANAGERUSER->preference("favcustomers")))
		return "<a title='Remove Favorite' href='$urlget" . "removefavorites={$row["id"]}'><img style='margin-right: 4px;' src='mimg/removefav.png' border=0/></a>" . $row["id"];
	else
		return "<a title='Add Favorite' href='$urlget" . "addfavorites={$row["id"]}'><img style='margin-right: 4px;' src='mimg/addfav.png' border=0/></a>" . $row["id"];
}

function fmt_timezone($row, $index){
	// 14400 seconds = 4 hours
	return gen2cache(14400,null,null,'customersetting',$row["id"],'timezone',"");
}
function customersetting($customerid,$setting,$defaultvalue){
	$query = "select c.id, s.dbhost, c.dbusername, c.dbpassword, c.urlcomponent from customer c inner join shard s on (c.shardid = s.id) where c.id=?";
	$custinfo = QuickQueryRow($query,true,false,array($customerid));
	$custdb = DBConnect($custinfo["dbhost"], $custinfo["dbusername"], $custinfo["dbpassword"], "c_{$custinfo["id"]}");
	if (!$custdb) {
		exit("Connection failed for customer: {$custinfo["dbhost"]}, db: c_{$custinfo["id"]}");
	}
	
	error_log("getting $setting $defaultvalue from customer $customerid");
	return getCustomerSystemSetting($setting,$defaultvalue,true,$custdb);
}


// row 11 is dm method
function fmt_actions($row, $index) {
	global $MANAGERUSER;
	$actions = '<div class="actionlinks">';
	if ($MANAGERUSER->authorized("editcustomer"))
		$actions .= '<a href="customereditgeneral.php?id=' . $row["id"] .'" title="Edit"><img src="mimg/s-edit.png" border=0></a>';
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


//SHOW DISABLED
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
		$sqlsearch = "(c.id='$safesearch' or c.urlcomponent like '%$safesearch%')";
}
////////////////////////////////////////////////////////////////////////////////
// data handling
////////////////////////////////////////////////////////////////////////////////


global $_dbcon;

// First, get a list of every shard, $shardinfo[], indexed by ID, storing dbhost, dbusername, and dbpassword.
$res = Query("select id, dbhost, dbusername, dbpassword, name from shard order by id");
$shardinfo = array();
while($row = DBGetRow($res,true)){
	$shardinfo[$row["id"]] = $row;
}

// Secondly, get a list of customers.
$query = "select c.id,c.shardid,c.urlcomponent,group_concat(p.product) as products,c.nsid,c.notes from customer c " .
 "left join customerproduct p on (c.id = p.customerid and p.enabled) " .
 " where $sqlsearch $sqltoggledisabled $favidsql group by id order by id";
error_log($query);
$customerquery = Query($query);
$customers = array();
if (!$shownone) {
	while ($row = DBGetRow($customerquery,true)) {
		$customers[] = $row;
	}
}
// With the list of customers ready, connect to each customer's shard and retrieve a bunch of helpful information about the customer.
$currhost = "";
$custdb; // customer database, using shard connection
$data = array();
foreach ($customers as $cust) {
	if ($currhost != $cust["shardid"]) {
		$dsn = 'mysql:dbname=c_'.$cust["id"].';host='.$shardinfo[$cust["shardid"]]["dbhost"];
		$custdb = new PDO($dsn, $shardinfo[$cust["shardid"]]["dbusername"], $shardinfo[$cust["shardid"]]["dbpassword"]);
		$custdb->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
		$currhost = $cust["shardid"];
	}
	
	if ($custdb) {
		$custdb->query("use c_".$cust["id"]);
		
		$row = $cust;
		$row["displayname"] = getCustomerSystemSetting('displayname', false, true, $custdb);
		$data[] = $row;
	}
}

if (isset($_GET["ajax"])) {
	$titles = array(
		"id" => "#ID",
		"urlcomponent" => "urlcomponent",
		"displayname" => "Name");

	$formatters = array();

	print "Search Quick Preview:";
	print "<table class='list sortable' id='customers_preview'>";
	showTable($data, $titles, $formatters);
	print "</table>";
	exit();
}

$titles = array(
	"id" => "#ID",
	"urlcomponent" => "Url",
	"displayname" => "Name",
	"products" => "Products",
	"timezone" => "timezone",
	"nsid" => "NSID",
	"notes" => "Notes",
	"actions" => "Actions");

$formatters = array(
	"id" => "fmt_custid",
	"timezone" => "fmt_timezone",
	"actions" => "fmt_actions",
);

$lockedTitles = array("actions");

$TITLE = "Overview";
$PAGE = "overview:all";

include_once("nav.inc.php");

?>
<div class="csec secbutton">
	<?= icon_button("Add Customer", "add",null,"customereditgeneral.php?id=new") ?>
</div><!-- .csec .secbutton -->
<div class="csec secwindow"><!-- contains recent activity -->

<?
startWindow(_L('Customers'));
include_once("inc/searchbar.inc.php");

?>

<input id="showdisabled" type="checkbox" onclick="window.location='allcustomers.php?' + (this.checked ? 'showdisabled&' : '') <? if(isset($_GET["showall"])) print "+ 'showall'"; else if (isset($_GET["search"])) print "+ 'search=" . escapehtml($_GET['search']) . "'"; ?>;" <?= isset($_GET['showdisabled']) ? "checked" : ""?>>
<label for="showdisabled">Show Disabled</label>

<?
if (!isset($_GET["showall"]))
	echo "&nbsp;|&nbsp;<a href='allcustomers.php?showall'>Show All Customers</a> ";
else if ($MANAGERUSER->preference("favcustomers")) {
	echo "&nbsp;|&nbsp;<a href='allcustomers.php'> <img src='mimg/fav.png' border=0/>Show Favorites</a>";
	echo "&nbsp;|&nbsp;<a style='margin-left: 4px' href='?clearfavorites'><i>Clear Favorites</i></a>";
}

//show_column_selector('customers_table', $titles, $lockedTitles);
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
<?
endWindow();
?>
</div><!-- .csec .secwindow -->
<?
include_once("navbottom.inc.php");
?>


