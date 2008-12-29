<?
require_once("../inc/db.inc.php");

function genpassword($digits = 15) {
	$passwd = "";
	$chars = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz";
	while ($digits--) {
		$passwd .= $chars[mt_rand(0,strlen($chars)-1)];
	}
	return $passwd;
}


/*
 *
 */
function createNewCustomer($authdb = false, $shardid = 0, $hostname = '', $customerid = 0) {

 				//choose shard info based on selection
				$shardinfo = QuickQueryRow("select dbhost, dbusername, dbpassword from shard where id=$shardid", true, $authdb);
				$shardhost = $shardinfo['dbhost'];
				$sharduser = $shardinfo['dbusername'];
				$shardpass = $shardinfo['dbpassword'];

				$dbpassword = genpassword();
				if ($customerid == 0) {
					QuickUpdate("insert into customer (urlcomponent, shardid, dbpassword,enabled) values
												('" . DBSafe($hostname) . "','$shardid', '$dbpassword', '1')", $authdb )
						or die("failed to insert customer into auth server");
					$customerid = mysql_insert_id();
				} else {
					$query = "update authserver.customer set shardid=$shardid, dbpassword='$dbpassword' and enabled=1 where id=$customerid";
					QuickUpdate($query, $authdb)
						or die("failed to update customer record on auth server");
				}

				$newdbname = "c_$customerid";
				QuickUpdate("update customer set dbusername = '" . $newdbname . "' where id = '" . $customerid . "'", $authdb);

				$newdb = mysql_connect($shardhost, $sharduser, $shardpass)
					or die("Failed to connect to DBHost $shardhost : " . mysql_error($newdb));
				QuickUpdate("create database $newdbname DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci",$newdb)
					or die ("Failed to create new DB $newdbname : " . mysql_error($newdb));
				mysql_select_db($newdbname,$newdb)
					or die ("Failed to connect to DB $newdbname : " . mysql_error($newdb));

				QuickUpdate("drop user '$newdbname'", $newdb); //ensure mysql credentials match our records, which it won't if create user fails because the user already exists
				QuickUpdate("create user '$newdbname' identified by '$dbpassword'", $newdb);
				QuickUpdate("grant select, insert, update, delete, create temporary tables, execute on $newdbname . * to '$newdbname'", $newdb);

				$tablequeries = explode("$$$",file_get_contents("../db/customer.sql"));
				foreach ($tablequeries as $tablequery) {
					if (trim($tablequery)) {
						$tablequery = str_replace('_$CUSTOMERID_', $customerid, $tablequery);
						Query($tablequery,$newdb)
							or die ("Failed to execute statement \n$tablequery\n\nfor $newdbname : " . mysql_error($newdb));
					}
				}
				return $newdb;
 }


/*
 * create SchooMessenger user and profile
 * (used by both manager newcustomer and the commsuite migration scripts)
 */
function createSMUserProfile($newdb) {

				$query = "insert into access (name) values ('SchoolMessenger Admin')";
				QuickUpdate($query, $newdb) or die( "ERROR: " . mysql_error());
				$accessid = mysql_insert_id();

				$query = "INSERT INTO `permission` (accessid,name,value) VALUES "
						. "($accessid, 'loginweb', '1'),"
						. "($accessid, 'manageprofile', '1'),"
						. "($accessid, 'manageaccount', '1'),"
						. "($accessid, 'managesystem', '1'),"
						. "($accessid, 'loginphone', '1'),"
						. "($accessid, 'startstats', '1'),"
						. "($accessid, 'startshort', '1'),"
						. "($accessid, 'starteasy', '1'),"
						. "($accessid, 'sendprint', '0'),"
						. "($accessid, 'callmax', '10'),"
						. "($accessid, 'sendemail', '1'),"
						. "($accessid, 'sendphone', '1'),"
						. "($accessid, 'sendsms', '1'),"
						. "($accessid, 'sendmulti', '1'),"
						. "($accessid, 'leavemessage', '1'),"
						. "($accessid, 'survey', '1'),"
						. "($accessid, 'createlist', '1'),"
						. "($accessid, 'createrepeat', '1'),"
						. "($accessid, 'createreport', '1'),"
						. "($accessid, 'maxjobdays', '7'),"
						. "($accessid, 'viewsystemreports', '1'),"
						. "($accessid, 'viewusagestats', '1'),"
						. "($accessid, 'viewcalldistribution', '1'),"
						. "($accessid, 'managesystemjobs', '1'),"
						. "($accessid, 'managemyaccount', '1'),"
						. "($accessid, 'viewcontacts', '1'),"
						. "($accessid, 'viewsystemactive', '1'),"
						. "($accessid, 'viewsystemrepeating', '1'),"
						. "($accessid, 'viewsystemcompleted', '1'),"
						. "($accessid, 'listuploadids', '1'),"
						. "($accessid, 'listuploadcontacts', '1'),"
						. "($accessid, 'setcallerid', '1'),"
						. "($accessid, 'blocknumbers', '1'),"
						. "($accessid, 'callblockingperms', 'editall'),"
						. "($accessid, 'metadata', '1'),"
						. "($accessid, 'portalaccess', '1'),"
						. "($accessid, 'generatebulktokens', '1'),"
						. "($accessid, 'managetasks', '1'), "
						. "($accessid, 'managecontactdetailsettings', '1'),"
						. "($accessid, 'messageconfirmation', '1')";
				QuickUpdate($query, $newdb) or die( "ERROR: " . mysql_error() . " SQL:" . $query);

				$query = "INSERT INTO `user` (`accessid`, `login`,
							`firstname`, `lastname`, `enabled`, `deleted`) VALUES
							( '$accessid' , 'schoolmessenger',
							'School', 'Messenger', 1 ,0)";
				QuickUpdate($query, $newdb) or die( "ERROR: " . mysql_error() . " SQL:" . $query);
}

function show_column_selector($tablename=null, $fields, $lockedFields=array()){
?>
	<table border="0" cellpadding="2" cellspacing="1" class="list">
		<tr class="listHeader" align="left" valign="bottom">
<?
			$showFields = array();
			$fieldnum = 0;
			foreach($fields as $id => $field){
				if (strpos($field,"@#") === 0){
					$displaytitle = substr($field,2);
					$showFields[$id] = array($fieldnum, false);
				} else if (strpos($field,"@") === 0){
					$displaytitle = substr($field,1);
					$showFields[$id] = array($fieldnum, false);
				} else if (strpos($field,"#") === 0){
					$displaytitle = substr($field,1);
					$showFields[$id] = array($fieldnum, true);
				} else {
					$displaytitle = $field;
					$showFields[$id] = array($fieldnum, true);
				}
				if (!in_array($id, $lockedFields, true)) {
					?><td><?=$displaytitle;?></td><?
				}
				$fieldnum++;
			}
?>
		</tr>
		<tr>
<?
			foreach($showFields as $id => $details){
				$fieldnum = $details[0];
				$display = $details[1];
				if (!in_array($id, $lockedFields, true)) {
					?><td><div align="center">
					<?
						if ($display) {
							$result = "<img src=\"../img/checkbox-rule.png\" " .
									"onclick=\"var x = new getObj('hiddenfield$fieldnum'); " .
									"if(x.obj.checked){this.src='../img/checkbox-clear.png'}else{this.src='../img/checkbox-rule.png'};";
							$checked = "checked>";
						} else {
							$result = "<img src=\"../img/checkbox-clear.png\" " .
									"onclick=\"var x = new getObj('hiddenfield$fieldnum'); " .
									"if(x.obj.checked){this.src='../img/checkbox-clear.png'}else{this.src='../img/checkbox-rule.png'};";
							$checked = ">";
						}

						if($tablename == null){
							$result .= "\">";
						} else {
							$result .= "toggleHiddenField('$fieldnum');" .
									" try { setColVisability(new getObj('$tablename').obj, $fieldnum, new getObj('hiddenfield$fieldnum').obj.checked); } catch (e) {}; \">";
						}
						echo $result;
						echo "<input style='display: none;' type='checkbox' id='hiddenfield$fieldnum' " . $checked;
					?>
					</div></td><?
				}
			}
?>
		</tr>
	</table>
<?
}

function show_row_filter($tablename, $data, $fields, $filterFields, $formatters) {
?>
	<table border="0" cellpadding="2" cellspacing="1" class="list">
		<tr class="listHeader" align="left" valign="bottom">
<?
			foreach($fields as $id => $field){
				if (strpos($field,"@#") === 0){
					$displaytitle = substr($field,2);
				} else if (strpos($field,"@") === 0){
					$displaytitle = substr($field,1);
				} else if (strpos($field,"#") === 0){
					$displaytitle = substr($field,1);
				} else {
					$displaytitle = $field;
				}
				if (in_array($id, $filterFields, true)) {
					?><td><?=$displaytitle;?></td><?
				}
			}

?>
		</tr>
<?
		$filterVals = array();
		foreach ($filterFields as $id) {
			$rownum = 1;
			foreach ($data as $row) {
				if (isset($formatters[$id])) {
					$fn = $formatters[$id];
					$cel = $fn($row,$id);
				} else {
					$cel = $row[$id];
				}
				if (!isset($filterVals[$id][$cel])) {
					$filterVals[$id][$cel] = array($rownum);
				} else {
					$filterVals[$id][$cel][] = $rownum;
				}
				$rownum++;
			}
		}
?>
		<script language="javascript">
			var optionToDataAssociation = <?=json_encode($filterVals)?>;
		</script>

		<tr>
<?

// fill out multi-select boxes with field data values and put them here.

			foreach ($filterVals as $id => $dataVals) {
				?><td valign="top"><SELECT MULTIPLE>	<?
				foreach ($dataVals as $data => $rows) {
					echo "<OPTION ID=\"option$id"."_"."$data\" VALUE=\"$data\" SELECTED> $data";
				}
				echo "</SELECT></td>";
			}

?>
		</tr>
		<tr>
			<td colspan="2"><input type="button" class="button" name="filterRows" value="Apply Filters" onclick="displayRows(new getObj('<?=$tablename?>').obj);" /></td>

		</tr>
	</table>

	<script language="javascript">

	function displayRows(table) {
		var filters = 0;
		var trows = table.rows;
		var showRows = new Array();
		for (var i = 1, length = trows.length; i < length + 1; i++) {
			showRows.push(0);
		}

		var fields;
		for ( a in optionToDataAssociation ) {
			filters++;
			for (b in optionToDataAssociation[a]) {
				opt = new getObj('option' + a + '_' + b);
				if (opt.obj.selected) {
					for ( c in optionToDataAssociation[a][b] ) {
						showRows[optionToDataAssociation[a][b][c]]++;
					}
				}
			}
		}

		for (var d = 1, length = trows.length; d < length; d++) {
			var rowId = 'row'+d;
			var modrow = new getObj(rowId).obj;
			if (showRows[d] >= filters) {
				modrow.style.display = '';
			} else {
				modrow.style.display = 'none';
			}
		}

		var color = 0;
		for (var d = 1, length = trows.length; d < length; d++) {
			if (trows[d].style.display != 'none') {
				if (color) {
					trows[d].className = 'listAlt';
				} else {
					trows[d].className = '';
				}
				color = !color;
			}
		}
	}

	</script>
<?
}

?>