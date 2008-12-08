<?
require_once("../inc/db.inc.php");

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
				if (strpos($field,"@") === 0){
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

?>