<?
include_once("common.inc.php");
include_once("../inc/form.inc.php");
include_once("../inc/table.inc.php");
include_once("../inc/html.inc.php");

// is admin user authorized to manage disk agents
if (!$MANAGERUSER->authorized("diskagent"))
	exit("Not Authorized");

// connect to DISK database
$diskdb = DBConnect($SETTINGS['diskdb']['host'], $SETTINGS['diskdb']['user'], $SETTINGS['diskdb']['pass'], $SETTINGS['diskdb']['db']);

// delete action, remove association between customer and agent
if (isset($_GET['delete']) && isset($_GET['agentid']) && isset($_GET['cid'])) {
	$query = "delete from customeragent where customerid=? and agentid=?";
	QuickUpdate($query, $diskdb, array($_GET['cid'], $_GET['agentid']));
}

// edit this agent
if (isset($_GET['agentid'])) {
	$agentid = $_GET['agentid']+0;
	if (!QuickQuery("select 1 from agent where id = ? limit 1", $diskdb, array($agentid))) {
		echo "Invalid SwiftSync ID.";
		exit();
	}
	$_SESSION['agentid'] = $agentid;
	redirect();
} else {
	$agentid = $_SESSION['agentid'];
}

// get the agent
$agent = QuickQueryRow("select uuid, name, numpollthread from agent where id=?", true, $diskdb, array($agentid));

// find all agent customerids to lookup customerurl in authserver db
$customerids = QuickQueryList("select distinct customerid from customeragent where agentid=?", false, $diskdb, array($agentid));
if (count($customerids))
	$customerlookup = QuickQueryList("select id, urlcomponent from customer where id in (".repeatWithSeparator("?",",",count($customerids)).")", true, false, $customerids);
else
	$customerlookup = array();

$query = "select customerid, 'UNKNOWN', options from customeragent where agentid=?";
$result = Query($query, $diskdb, array($agentid));
$data = array();
while ($row = DBGetRow($result)) {
	if (isset($row[0]) && isset($customerlookup[$row[0]])) {
		$row[1] = $customerlookup[$row[0]];
	}
	$data[] = $row;
}


// Main Form
$f = "editdiskagent";
$s = "main";
$reloadform = 0;

if (CheckFormSubmit($f,$s) || CheckFormSubmit($f, "addcust")) {
	//check to see if formdata is valid
	if (CheckFormInvalid($f)) {
		error('Form was edited in another window, reloading data');
		$reloadform = 1;
	} else {
		MergeSectionFormData($f, $s);

		TrimFormData($f, $s, "addcustomerurl");
		TrimFormData($f, $s, "agent_name");
		TrimFormData($f, $s, "agent_numpollthread");

		$agentname = GetFormData($f, $s, "agent_name");
		$agentnamelength = strlen($agentname);
		$numpollthread = GetFormData($f, $s, "agent_numpollthread") + 0;

		//do check
		if ( CheckFormSection($f, $s) ) {
			error('There was a problem trying to save your changes', 'Please verify that all required field information has been entered properly');
		} else {
			// add a new customer association to this agent
			if (CheckFormSubmit($f, "addcust")) {
				$customerurl = GetFormData($f, $s, "addcustomerurl");
				$cid = QuickQuery("select id from customer where urlcomponent = ?", false, array($customerurl));
				if (!$cid) {
					error('Invalid Customer URL');
				} else if (QuickQuery("select 1 from customeragent where customerid=? and agentid=? limit 1", $diskdb, array($cid, $agentid))) {
					error('Customer already associated with this Agent');
				} else {
					QuickUpdate("insert into customeragent (customerid, agentid) values (?, ?)", $diskdb, array($cid, $agentid));
					redirect();
				}

			} else { // submit, agent settings
				if ($agentnamelength < 5 || $agentnamelength > 50) {
					error('Agent name must be between 5 and 50 characters');
				} else if ($numpollthread < 2 || $numpollthread > 10) {
					error('Number of Polling Threads must be between 2 and 10');
				} else {
					// update agent properties
					QuickUpdate("update agent set name=?, numpollthread=? where id=?", $diskdb, array($agentname, $numpollthread, $agentid));

					// find each auth checkbox value
					foreach ($data as $row) {
						// TODO for 7.5 the only option is 'auth' but later need to add/remove options in a set
						$ischecked = GetFormData($f, $s, "auth_".$row[0]);
						if (QuickQuery("select 1 from customeragent where customerid=? and agentid=? and options like '%auth%'", $diskdb, array($row[0], $agentid))) {
							if (!$ischecked)
								QuickUpdate("update customeragent set options='' where customerid=? and agentid=?", $diskdb, array($row[0], $agentid));
						} else {
							if ($ischecked)
								QuickUpdate("update customeragent set options='auth' where customerid=? and agentid=?", $diskdb, array($row[0], $agentid));
						}
					}

					// back to main page
					redirect("diskagents.php");
				}	
			}
		}
	}
} else {
	$reloadform = 1;
}

if ( $reloadform ) {
	ClearFormData($f);
	PutFormData($f, $s, "Submit", "");
	PutFormData($f, "addcust", "Add Customer URL", "");

	PutFormData($f, $s, "addcustomerurl", "", "text", "nomin", "nomax", false);
	PutFormData($f, $s, "agent_name", $agent['name'], "text", "nomin", "nomax", true);
	PutFormData($f, $s, "agent_numpollthread", $agent['numpollthread'], "number", "2", "10", true);
	
	// find each auth checkbox value
	foreach ($data as $row) {
		if (strpos($row[2], "auth") === false) {
			$ischecked = false;
		} else {
			$ischecked = true;
		}
		PutFormData($f, $s, "auth_".$row[0], $ischecked, "bool");
	}
}

//index 0 is customer id
//index 1 is customer url
function fmt_customerUrl($row, $index) {
	return "<a href=\"customerlink.php?id=" . $row[0] ."\" target=\"_blank\">" . $row[1] . "</a>";
}

// index 2 is options
function fmt_authentication($row, $index) {
	global $f, $s;
	return NewFormItem($f, $s, 'auth_'.$row[0], 'checkbox');
}

// index 0 is agentid
function fmt_editdiskagent_actions($row, $index) {
	global $agentid;
	$url =  '<a href="editdiskagent.php?delete&agentid=' . $agentid . '&cid=' . $row[0]. '" title="Delete"><img src="mimg/cross.png" border=0></a>&nbsp;' ;
	return $url;
}

$titles = array(
	0 => "#Cust ID", 
	1 => "#Customer URL", 
	2 => "Authentication", 
	"actions" => "Actions"
);
$formatters = array(
	1 => "fmt_customerUrl",
	2 => "fmt_authentication",
	"actions" => "fmt_editdiskagent_actions"
	);

include_once("nav.inc.php");

//custom newform declaration to catch if manager password is submitted
NewForm($f);
?>
<div>Settings for SwiftSync: <?=$agent['name']?></div>
<table>
<?

?>
	<tr>
		<td>SwiftSync Name: </td>
		<td><? NewFormItem($f, $s, "agent_name", "text", "20");?></td>
	</tr>
	<tr>
		<td>Number of Polling Threads: </td>
		<td><? NewFormItem($f, $s, "agent_numpollthread", "text", "5");?></td>
	</tr>
	<tr>
		<td colspan="2">
Customers this SwiftSync is associated with:<BR>
<table class="list sortable" id="customer_agent_table">
<?
	showTable($data, $titles, $formatters);
?>
</table>
		</td>
	</tr>
	<tr>
		<td>
		<? NewFormItem($f, $s, "addcustomerurl", "text", "20");?>
		</td>
		<td>
		<? NewFormItem($f, "addcust", "Add Customer URL", "submit"); ?>
		</td>
	</tr>
	<tr>
		<td>
<?
			NewFormItem($f, $s, "Submit", "submit");
?>
		</td>
	</tr>
</table>
<?
EndForm();
include_once("navbottom.inc.php");
?>
