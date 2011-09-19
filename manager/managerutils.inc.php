<?

function genpassword($digits = 15) {
	$passwd = "";
	$chars = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz";
	while ($digits--) {
		$passwd .= $chars[mt_rand(0,strlen($chars)-1)];
	}
	return $passwd;
}

/*
 * create SchooMessenger user and profile
 * (used by both manager newcustomer and the commsuite migration scripts)
 */
function createSMUserProfile($newdb, $newdbname = "") {
	try {
		$query = "insert into access (name) values ('SchoolMessenger Admin')";
		QuickUpdate($query, $newdb);
		$accessid = $newdb->lastInsertId();
	} catch (PDOException $e) {
		die("ERROR: ".$e->getMessage());
	}

	$tablequeries = explode("$$$",file_get_contents("../db/update_SMAdmin_access.sql"));
	foreach ($tablequeries as $tablequery) {
		if (trim($tablequery)) {
			Query($tablequery,$newdb)
				or dieWithError("Failed to execute statement \n$tablequery\n\nfor $newdbname", $newdb);
		}
	}

	$query = "INSERT INTO `user` (`accessid`, `login`,
				`firstname`, `lastname`, `enabled`, `deleted`) VALUES
				( '$accessid' , 'schoolmessenger',
				'School', 'Messenger', 1 ,0)";
	QuickUpdate($query, $newdb);
}

// create the subscriber application database user
function createLimitedUser($limitedusername, $limitedpassword, $custdbname, $sharddb, $grantedhost = '%') {
	QuickUpdate("drop user '$limitedusername'@'$grantedhost'", $sharddb);
	QuickUpdate("create user '$limitedusername'@'$grantedhost' identified by '$limitedpassword'", $sharddb);

	$tables = array();
	$tables['audiofile'] 	= "select";
	$tables['content'] 		= "select";
	$tables['contactpref'] 	= "select, insert, update, delete";
	$tables['email'] 		= "select, update";
	$tables['fieldmap'] 	= "select";
	$tables['groupdata'] 	= "select, insert, update, delete";
	$tables['job'] 			= "select";
	$tables['jobsetting'] 	= "select";
	$tables['jobtype'] 		= "select";
	$tables['language']		= "select";
	$tables['message'] 		= "select";
	$tables['messageattachment'] = "select";
	$tables['messagegroup'] = "select";
	$tables['messagepart'] 	= "select";
	$tables['organization'] = "select";
	$tables['persondatavalues'] = "select";
	$tables['person'] 		= "select, update";
	$tables['personassociation'] = "select, insert, update, delete";
	$tables['phone'] 		= "select, update";
	$tables['reportperson'] = "select";
	$tables['setting'] 		= "select";
	$tables['sms'] 			= "select, update";
	$tables['subscriber'] 	= "select, update";
	$tables['subscriberpending'] = "select, delete";
	$tables['ttsvoice'] 	= "select";
	$tables['user'] 		= "select";
			
	foreach ($tables as $tablename => $privs) {
		if (QuickUpdate("grant ".$privs." on $custdbname . ".$tablename." to '$limitedusername'@'$grantedhost'", $sharddb) === false)
			dieWithError("Failed to grant ".$tablename." on ".$custdbname, $sharddb);
		
	}
}

function show_column_selector($tablename=null, $fields, $lockedFields=array(),$pagename = null){
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
				if($pagename && isset($_SESSION['fieldview']) &&
					 isset($_SESSION['fieldview']["$pagename:$field"])) {
					$showFields[$id] = array($fieldnum, $_SESSION['fieldview']["$pagename:$field"]);
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
							$result = "<img src=\"mimg/checkbox-rule.png\" " .
									"onclick=\"" .
									"if($('hiddenfield$fieldnum').checked){this.src='mimg/checkbox-clear.png'}else{this.src='mimg/checkbox-rule.png'};";
							$checked = "checked>";
						} else {
							$result = "<img src=\"mimg/checkbox-clear.png\" " .
									"onclick=\"" .
									"if($('hiddenfield$fieldnum').checked){this.src='mimg/checkbox-clear.png'}else{this.src='mimg/checkbox-rule.png'};";
							$checked = ">";
						}
						if($tablename == null){
							$result .= "\">";
						} else {
							$result .= "toggleHiddenField('$fieldnum');try { setColVisability($('$tablename'), $fieldnum, $('hiddenfield$fieldnum').checked,'" . ($pagename?$pagename:"") . "','" . $fields[$id] . "'); } catch (e) {alert('exce' + e)}; \">";
						}
						echo $result;
						echo "<input style='display: none;' type='checkbox' id='hiddenfield$fieldnum' " . $checked;
					?>
					</div></td>
					<?
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
			<td colspan="2">
			<?=icon_button("Apply Filters", "magnifier","displayRows($('$tablename'));")?>
			</td>

		</tr>
	</table>

	<script type="text/javascript">
	var optionToDataAssociation = <?=json_encode($filterVals)?>;
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


function dieWithError($error, $pdo = false) {
	$dberr;
	if ($pdo) {
		$e = $pdo->errorInfo();
		$dberr = $e[2];
	}
	die ($error . " : " . $dberr);
}


$SHARDINFO = false;
$CUSTOMERINFO = false;

/* 
 * Loads all shard and customer info, use when iterating over all/several customers.
 * Only loads enabled customers.
 */
function loadManagerConnectionData () {
	global $SHARDINFO, $CUSTOMERINFO, $_dbcon;
	$SHARDINFO = array();
	$CUSTOMERINFO = array();
	
	$res = Query("select id, dbhost, dbusername, dbpassword, name from shard order by id",$_dbcon);
	while($row = DBGetRow($res,true)){
		$SHARDINFO[$row['id']] = $row;
	}
	
	$query = "select id, oem, oemid, nsid, shardid, urlcomponent, inboundnumber, notes, enabled from customer where enabled order by id";
	$res = Query($query,$_dbcon);
	while ($row = DBGetRow($res,true)) {
		$CUSTOMERINFO[$row['id']] = $row;
	}
}

/* 
 * Connects to or uses an already esablished connection to the customer's shard, then switches to that DB. 
 * Only use if you need to iterate over all/several customers. 
 * Requires that loadManagerConnectionData() has already been called.
 */
function getPooledCustomerConnection ($cid,$readonly=false) {
	global $SHARDINFO, $CUSTOMERINFO, $_dbcon, $SETTINGS;
	$cid = 0 + $cid; //just in case
		
	if (!$SHARDINFO)
		loadManagerConnectionData();
	
	$sid = $CUSTOMERINFO[$cid]['shardid'];

	if ($readonly && !isset($SETTINGS["db"]["readonly"][$sid-1])) {
		error_log("WARNING: readonly connection requested, but not found in config");
		$readonly = false;
	}
	
	$dbtype = $readonly ? "readonly" : "dbcon";
	//see if we need to connect
	if (!isset($SHARDINFO[$sid][$dbtype])) {
		$host = $readonly ? $SETTINGS["db"]["readonly"][$sid-1] : $SHARDINFO[$sid]["dbhost"];
		$dsn = "mysql:dbname=c_$cid;host=$host";
		error_log("New PDO connection to $dsn");
		$SHARDINFO[$sid][$dbtype] = new PDO($dsn, $SHARDINFO[$sid]["dbusername"], $SHARDINFO[$sid]["dbpassword"]);
		$SHARDINFO[$sid][$dbtype]->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
	}
	//select this customer's db
	$SHARDINFO[$sid][$dbtype]->query("use c_$cid");
	
	return $SHARDINFO[$sid][$dbtype];
}

/*
 * Gets a connection to a single customer's DB. 
 * Use when you need to connect to a single customer (eg customer edit or a customer specific page)
 * DO NOT use when you need to iterate over several customers, use getPooledCustomerConnection() instead.
 */
function getSingleCustomerConnection ($cid,$readonly=false) {
	global $_dbcon, $SETTINGS;
	$cid = 0 + $cid; //just in case
	
	$connectinfo = QuickQueryRow("select s.id, s.dbhost, s.dbusername, s.dbpassword from customer c inner join shard s on (c.shardid = s.id) where c.id = ?",true,$_dbcon,array($cid));

	if ($readonly && !isset($SETTINGS["db"]["readonly"][$connectinfo["id"]-1])) {
		error_log("WARNING: readonly connection requested, but not found in config");
		$readonly = false;
	}

	$host = $readonly ? $SETTINGS["db"]["readonly"][$connectinfo["id"]-1] : $connectinfo["dbhost"];
	
	$dsn = "mysql:dbname=c_$cid;host=$host";
	$db = new PDO($dsn, $connectinfo["dbusername"], $connectinfo["dbpassword"]);
	$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
	return $db;
}

?>