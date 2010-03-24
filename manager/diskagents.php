<?
require_once("common.inc.php");
require_once("../inc/form.inc.php");
require_once("../inc/html.inc.php");
require_once("../inc/table.inc.php");
require_once("../inc/formatters.inc.php");
require_once("XML/RPC.php");
require_once("diskclient.inc.php");

if (!$MANAGERUSER->authorized("diskagent"))
	exit("Not Authorized");


// connect to DISK database
$diskdb = DBConnect($SETTINGS['diskdb']['host'], $SETTINGS['diskdb']['user'], $SETTINGS['diskdb']['pass'], $SETTINGS['diskdb']['db']);


$custtxt = ""; // search customer url
$newcusturl = ""; // setup new agent for customer url
$newagentname = ""; // setup new agent with this name

//$queryextra = "";

// clear the customerid
if (isset($_GET['clear'])){
	unset($_SESSION['customerid']);
	redirect();
}

/*
// find for customerid
if (isset($_GET['cid'])) {
	if ($_GET['cid']) {
		$queryextra = " AND dm.customerid in (";
		foreach (explode(",", $_GET['cid']) as $cid)
			$queryextra .= "'". DBSafe($cid) . "',";
			
		$queryextra = substr($queryextra, 0, -1) . ") ";
	}
}

// show agents matching customer url search
if (isset($_POST['showmatch'])) {
	if (isset($_POST['custtxt']) && trim($_POST['custtxt'])) {
		$custtxt = escapehtml(trim($_POST['custtxt']));
		$queryextra = " and c.urlcomponent like '%" . DBSafe(trim($_POST['custtxt'])) . "%'";
	}
}
*/

// generate new agent uuid and add to database, display in agent table
$genstatus = false;
if (isset($_POST['genuuid'])) {
	if (isset($_POST['newcusturl'])  && trim($_POST['newcusturl']) &&
		isset($_POST['newagentname']) && trim($_POST['newagentname'])) {
		$newcusturl = escapehtml(trim($_POST['newcusturl']));
		$newagentname = escapehtml(trim($_POST['newagentname']));
		$agentnamelength = strlen($newagentname);
		if ($agentnamelength < 5 || $agentnamelength > 50) {
			$genstatus = "Error, agent name must be between 5 and 50 characters";
		} else {
			// verify customerurl exists
			$cid = QuickQuery("select id from customer where urlcomponent=?", false, array(trim($_POST['newcusturl'])));
			if ($cid) {
				$custinfo = QuickQueryRow("select s.dbhost, s.dbusername, s.dbpassword from shard s 
						inner join customer c on (c.shardid = s.id)
						where c.id = ?", false, false, array($cid));
				$custdb = DBConnect($custinfo[0], $custinfo[1], $custinfo[2], "c_" . $cid);
				if (!$custdb) {
					$genstatus = "Error, failure connecting to customer database";
				} else {
					// TODO future release to support mulitple agents per customer
					if (QuickQuery("select 1 from setting where name='_authdiskuuid' limit 1", $custdb)) {
						$genstatus = "Error, customer already has agent associated";
					} else {
						// generate new UUID
						$uuid = md5($newcusturl . microtime());
						// insert into disk database
						QuickUpdate("insert into agent (uuid, name, numpollthread) values (?, ?, 2)", $diskdb, array($uuid, $newagentname));
						QuickUpdate("insert into customeragent (customerid, agentid) values (?, (select id from agent where uuid=?))", $diskdb, array($cid, $uuid));
						// insert into customer database
						QuickUpdate("insert into setting (name, value) values ('_authdiskuuid', ?)", $custdb, array($uuid));
						$genstatus = "Success";
					}
				}
			} else {
				$genstatus = "Error, customer not found";
			}
		}
	} else {
		$genstatus = "Error, missing field";
	}
}

/*
if(isset($_SESSION['customerid'])){
	$queryextra = " and dm.customerid = " . $_SESSION['customerid'] . " ";
}

if(isset($_GET['showdisabled'])) {
	$showingDisabledDMs = true;
	$queryextra .= " and s_dm_enabled.value = '0' ";
} else {
	$showingDisabledDMs = false;
	$queryextra .= " and (s_dm_enabled.value = '1' or s_dm_enabled.value is null) ";
}

if(isset($_GET['showall'])) {
	$showingDisabledDMs = false;
	$queryextra = "";
}
*/


//index 1 is customer id
//index 2 is customer url
function fmt_customerUrl($row, $index){
	$url = "";
	if($row[2] == "UNKNOWN")
		$url = $row[2];
	else {
		$url = "<a href=\"customerlink.php?id=" . $row[1] ."\" target=\"_blank\">" . $row[2] . "</a>";
	}
	return $url;
}

// index 0 is agentid
function fmt_DMActions($row, $index){
	$url =  '<a href="editdiskagent.php?agentid=' . $row[0] . '" title="Edit"><img src="img/s-edit.png" border=0></a>&nbsp;' ;
	return $url;
}

// index 1 is customerid
// index 5 is last seen
function fmt_dmstatus($row,$index) {
	$problems = array();

	if ($row[5]/1000 < time() - 30)
		$problems[] = "Agent Lost Connection";

	if ($row[1] == null || $row[1] <= 0)
		$problems[] = "Invalid Customer ID";

	if (count($problems))
		return "<div style=\"background-color:red\">" . implode(", ", $problems) . "</div>";
	else
		return "OK";
}

function fmt_lastseen($row, $index){
	$output = date("Y-m-d G:i:s", $row[$index]/1000);
	if($row[$index]/1000 > strtotime("now") - (1800) && $row[$index]/1000 < strtotime("now")-600){
		$output = "<div style=\"background-color:yellow\">" . $output . "</div>";
	} else if($row[$index]/1000 < strtotime("now") - (1800)){
		$output = "<div style=\"background-color:red\">" . $output . "</div>";
	}
	return $output;
}

// find all agent customerids to lookup customerurl in authserver db
$customerids = QuickQueryList("select distinct customerid from customeragent", false, $diskdb);
if (count($customerids))
	$customerlookup = QuickQueryList("select id, urlcomponent from customer where id in (".repeatWithSeparator("?",",",count($customerids)).")", true, false, $customerids);
else
	$customerlookup = array();

// query diskserver for online agent status
$diskserverresults = getAgentList();

$agents = array();
// TODO how to concat() customerids and urls when one agent associated with many customers?
$query = "select a.id, ca.customerid, 'UNKNOWN', a.name, 'UNKNOWN', 'UNKNOWN', 'UNKNOWN', a.uuid, a.numpollthread from agent a left join customeragent ca on (ca.agentid = a.id) group by a.id";
$result = Query($query, $diskdb);
$data = array();
while ($row = DBGetRow($result)) {
	if (isset($row[7]) && isset($diskserverresults[$row[7]])) {
		$agentprops = $diskserverresults[$row[7]];
		$row[4] = $agentprops['ip'];
		$row[5] = $agentprops['lastseen'];
		$row[6] = $agentprops['version'];
	}
	if (isset($row[1]) && isset($customerlookup[$row[1]])) {
		$row[2] = $customerlookup[$row[1]];
	}
	$data[] = $row;
}


/*
if ($data) {
	// First, get a list of every shard, $shardinfo[], indexed by ID, storing dbhost, dbusername, and dbpassword.
	$result = Query("select id, dbhost, dbusername, dbpassword, name from shard order by id");
	$shardinfo = array();
	while($row = DBGetRow($result)){
		$shardinfo[$row[0]] = array($row[1], $row[2], $row[3], $row[4]);
	}
	
	// Connect to each customer's shard and retrieve dmmethod
	$custdb;
	foreach($data as $dataPos => $cust) {
		if ($cust[1] + 0 > 0) {
			try {
				$dsn = 'mysql:dbname=c_'.$cust[1].';host='.$shardinfo[$cust[15]][0];
				$custdb = new PDO($dsn, $shardinfo[$cust[15]][1], $shardinfo[$cust[15]][2]);
				$custdb->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
			} catch (PDOException $e) {
				die("Could not connect to customer database: ".$e->getMessage());
			}
			Query("use c_" . $cust[1], $custdb);
			$query = "select value from setting where name = '_dmmethod' limit 1";
			if ($custdb)
				$data[$dataPos]['dmmethod'] = QuickQuery($query, $custdb);
		} else {
			$data[$dataPos]['dmmethod'] = '';
		}
		
	}
}
*/

// Add field titles, leading # means it is sortable leading @ means it is hidden by default
$titles = array(0 => "#Agent ID");
$titles[1] = "#Cust ID";
$titles[2] = "#Customer URL";
$titles[3] = "#Name";
$titles[4] = "#Last IP";
$titles[5] = "#Last Seen";
$titles["status"] = "#Status";
$titles[6] = "#Version";
$titles[7] = "@#Agent UUID";
$titles[8] = "#Polling Threads";
$titles["actions"] = "Actions";

// Do not provide a checkbox to hide these columns.
$lockedTitles = array(0, "status", "actions", 2, 3);

$formatters = array(2 => "fmt_customerUrl",
					"actions" => "fmt_DMActions",
					"status" => "fmt_dmstatus",
					5 => "fmt_lastseen");

/////////////////////////////
// Display
/////////////////////////////

include_once("nav.inc.php");

?>

<form method="POST" action="diskagents.php">
<table>
	<tr>
<?/*	
		<td valign="top">
			<table border="0" cellpadding="2" cellspacing="1" class="list">
				<tr class="listHeader" align="left" valign="bottom">
					<td>
						Search (can match partial urls)
					</td>
				</tr>
				<tr>
					<td valign="top">
						<table>
							<tr>
								<td valign="top" align="left">
									Cust URL:
								</td>
								<td>
									<input type="text" name="custtxt" id="custtxt" value="<?=$custtxt?>" size="20" maxlength="50" />
								</td>
							</tr>
							<tr>
								<td colspan="2">
									<input type="submit" name="showmatch" id="showmatch" value="Search" />   
								</td>
							</tr>
						</table>
					</td>
					
				</tr>
			</table>
		</td>
*/ ?>
		<td valign="top">
			<table border="0" cellpadding="2" cellspacing="1" class="list">
				<tr class="listHeader" align="left" valign="bottom">
					<td>
						Setup new Agent
					</td>
				</tr>
				<tr>
					<td valign="top">
						<table>
							<tr>
								<td valign="top" align="left">
									Cust URL:
								</td>
								<td>
									<input type="text" name="newcusturl" id="newcusturl" value="<?=$newcusturl?>" size="20" maxlength="50" />
								</td>
							</tr>
							<tr>
								<td valign="top" align="left">
									Agent Name:
								</td>
								<td>
									<input type="text" name="newagentname" id="newagentname" value="<?=$newagentname?>" size="20" maxlength="50" />
								</td>
							</tr>
							<tr>
								<td colspan="2">
									<input type="submit" name="genuuid" id="genuuid" value="Generate Agent UUID" />   
								</td>
							</tr>
<?						if ($genstatus) { ?>
							<tr>
								<td colspan="2">
									<?=$genstatus?>
								</td>
							</tr>
<?						} ?>
						</table>
					</td>
					
				</tr>
			</table>
		</td>
	</tr>
</table>
<? 
/*
<a href='diskagents.php?showall=1'>Show All Agents</a> 

if($showingDisabledDMs) {
	?><a href='diskagents.php'>Show Enabled Agents</a><? 
} else {
	?><a href='diskagents.php?showdisabled=1'>Show Disabled Agents</a><?
}
* */
?>
</form>
<?

// Show the column data hide/select check boxes.
show_column_selector('customer_dm_table', $titles, $lockedTitles);

?>
<table class="list sortable" id="customer_dm_table">
<?
	showTable($data, $titles, $formatters);
?>
</table>
<script language="javascript">
	var table = new getObj('customer_dm_table').obj;
	var trows = table.rows;
	for (var i = 0, length = trows.length; i < length; i++) {
		trows[i].id = 'row'+i;
	}
</script>
<?
if (file_exists("diskagentbuild.txt")) {
?>
	<div>Latest Version: <?=file_get_contents("diskagentbuild.txt");?></div>
<?
}
include_once("navbottom.inc.php");
?>
