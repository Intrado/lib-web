<?
include_once("common.inc.php");
include_once("../inc/form.inc.php");
include_once("../inc/table.inc.php");
include_once("../inc/html.inc.php");

if (!$MANAGERUSER->authorized("diskagent"))
	exit("Not Authorized");

// connect to DISK database
$diskdb = DBConnect($SETTINGS['diskdb']['host'], $SETTINGS['diskdb']['user'], $SETTINGS['diskdb']['pass'], $SETTINGS['diskdb']['db']);

if (isset($_GET['agentid'])) {
	$agentid = $_GET['agentid']+0;
	if (!QuickQuery("select 1 from agent where id = ? limit 1", $diskdb, array($agentid))) {
		echo "Invalid Agent ID.";
		exit();
	}
	$_SESSION['agentid'] = $agentid;
	redirect();
} else {
	$agentid = $_SESSION['agentid'];
}

// get the agent
$agent = QuickQueryRow("select uuid, name, numpollthreads from agent where id=?", true, $diskdb, array($agentid));
// NOTE support for 1-1 relationship, TODO many-many
$agent['customerid'] = QuickQuery("select customerid from customeragent where agentid=?", $diskdb, array($agentid));


$f = "editdiskagent";
$s = "main";
$reloadform = 0;

if (CheckFormSubmit($f,$s) || CheckFormSubmit($f, "authorize") || CheckFormSubmit($f, "unauthorize")) {
	//check to see if formdata is valid
	if (CheckFormInvalid($f)) {
		error('Form was edited in another window, reloading data');
		$reloadform = 1;
	} else {
		MergeSectionFormData($f, $s);

		TrimFormData($f, $s, "customerid");
		TrimFormData($f, $s, "agent_name");
		TrimFormData($f, $s, "agent_numpollthreads");

		$customerid = GetFormData($f, $s, "customerid") + 0;
		$agentname = GetFormData($f, $s, "agent_name");
		$agentnamelength = strlen($agentname);
		$numpollthreads = GetFormData($f, $s, "agent_numpollthreads") + 0;

		//do check
		if ( CheckFormSection($f, $s) ) {
			error('There was a problem trying to save your changes', 'Please verify that all required field information has been entered properly');
		} else {
			if ($customerid && !QuickQuery("select 1 from customer where id = ? limit 1", false, array($customerid))) {
				error('Invalid Customer ID');
			} else if ($agentnamelength < 5 || $agentnamelength > 50) {
				error('Agent name must be between 5 and 50 characters');
			} else if ($numpollthreads < 2 || $numpollthreads > 10) {
				error('Number of Polling Threads must be between 2 and 10');
			} else {
				// TODO transactions

				// cleanup and set customer's setting for '_authdiskuuid', also update disk.customeragent table
				
				// get existing customers for this agent
				$samecustid = false;
				$customerids = QuickQueryList("select customerid from customeragent where agentid=?", false, $diskdb, array($agentid));
				foreach ($customerids as $cid) {
					if ($cid == $customerid) {
						$samecustid = true;
						continue;
					} else {
						// find customer shard
						$custinfo = QuickQueryRow("select s.dbhost, s.dbusername, s.dbpassword from shard s 
								inner join customer c on (c.shardid = s.id)
								where c.id = ?", false, false, array($cid));
						$custdb = DBConnect($custinfo[0], $custinfo[1], $custinfo[2], "c_" . $cid);
						QuickUpdate("delete from setting where name='_authdiskuuid'", $custdb);
						
						// remove from disk.customeragent
						QuickUpdate("delete from customeragent where customerid=? and agentid=?", $diskdb, array($cid, $agentid));
					}
				}
				// find new customer (likely unchanged, but you never know?)
				$custinfo = QuickQueryRow("select s.dbhost, s.dbusername, s.dbpassword from shard s 
						inner join customer c on (c.shardid = s.id)
						where c.id = ?", false, false, array($customerid));
				$custdb = DBConnect($custinfo[0], $custinfo[1], $custinfo[2], "c_" . $customerid);
				if (QuickQuery("select 1 from setting where name='_authdiskuuid' limit 1", $custdb)) {
					QuickUpdate("update setting set value=? where name='_authdiskuuid'", $custdb, array($agent['uuid']));
				} else {
					QuickUpdate("insert into setting (name, value) values ('_authdiskuuid', ?)", $custdb, array($agent['uuid']));
				}
				
				// if not the same as before, add relationship
				if (!$samecustid) {
					QuickUpdate("insert into customeragent (customerid, agentid) values (?, ?)", $diskdb, array($customerid, $agentid));
				}

				// update agent properties
				QuickUpdate("update agent set name=?, numpollthreads=? where id=?", $diskdb, array($agentname, $numpollthreads, $agentid));

				// back to main page
				redirect("diskagents.php");
			}
		}
	}
} else {
	$reloadform = 1;
}

if ( $reloadform ) {
	ClearFormData($f);
	PutFormData($f, $s, "Submit", "");
	
	PutFormData($f, $s, "customerid", $agent['customerid'], "number", "1", "nomax", true);
	PutFormData($f, $s, "agent_name", $agent['name'], "text", "nomin", "nomax", true);
	PutFormData($f, $s, "agent_numpollthreads", $agent['numpollthreads'], "number", "2", "10", true);
}


include_once("nav.inc.php");

//custom newform declaration to catch if manager password is submitted
NewForm($f);
?>
<div>Settings for <?=$agent['name']?></div>
<table>
<?

?>
	<tr>
		<td>Customer ID: </td>
		<td><? NewFormItem($f, $s, "customerid", "text", "5"); ?></td>
	</tr>
	<tr>
		<td>Agent Name:</td>
		<td><? NewFormItem($f, $s, "agent_name", "text", "20");?></td>
	</tr>
	<tr>
		<td>Number of Polling Threads: </td>
		<td><? NewFormItem($f, $s, "agent_numpollthreads", "text", "5");?></td>
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
