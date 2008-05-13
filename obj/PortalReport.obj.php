<?

class PortalReport extends ReportGenerator{

	var $reporttotal = 0;

	function generateQuery(){
		global $USER;
		$this->params = $this->reportinstance->getParameters();
		$rulesql = getRuleSql($this->params, "p");
		$usersql = $USER->userSQL("p");
		$pkeysql = "";
		$hideactivecodes = "";
		$hideassociated = "";
		$hideassociatedtable = "";
		$showall = false;
		if(isset($this->params['showall']))
			$showall = true;
		if(isset($this->params['pkey'])){
			$pkeysql = " and p.pkey = '" . DBSafe($this->params['pkey']) . "' ";
		}
		if(isset($this->params['hideactivecodes']) && $this->params['hideactivecodes']){
			$hideactivecodes = " and (ppt.token is null or ppt.expirationdate < curdate()) ";
		}
		if(isset($this->params['hideassociated']) && $this->params['hideassociated']){
			$hideassociated = " and not exists(select count(*) from portalperson pp2 where pp2.personid = p.id group by pp2.personid) ";
		}
		if($rulesql || $pkeysql || $showall){
			$this->query = "select SQL_CALC_FOUND_ROWS
						p.pkey as pkey,
						p.id as pid,
						p." . FieldMap::GetFirstNameField() . " as firstname,
						p." . FieldMap::GetLastNameField() . " as lastname,
						ppt.token,
						ppt.expirationdate"
						. generateFields("p")
						. "	from person p
						left join portalpersontoken ppt on (ppt.personid = p.id)
						where not p.deleted
						and p.type='system' "
						. $pkeysql
						. $rulesql
						. $hideactivecodes
						. $hideassociated
						. $usersql;
			//test query used to confirm no active codes are in the list
			$this->testquery = "select count(ppt.token)
						from person p
						left join portalpersontoken ppt on (ppt.personid = p.id)
						where not p.deleted
						and p.type='system'
						and ppt.expirationdate > curdate() "
						. $pkeysql
						. $rulesql
						. $hideactivecodes
						. $hideassociated
						. $usersql;
		} else {
			$this->query = "";
			$this->testquery = "";
		}
	}


	function runHtml(){

		$pagestart = $this->params['pagestart'];
		$max = 100;
		$fields = FieldMap::getOptionalAuthorizedFieldMaps();
		$fieldlist = array();
		foreach($fields as $field){
			$fieldlist[$field->fieldnum] = $field->name;
		}
		$activefields = explode(",", isset($this->params['activefields']) ? $this->params['activefields'] : "");
		$query = $this->query . " order by p.id" . " limit $pagestart, $max";
		$result = Query($query);
		$this->reporttotal = QuickQuery("select found_rows()");
		$data = array();
		$personids = array();
		$count = 0;
		$curr = null;
		while($row = DBGetRow($result)){
			$personids[] = $row[1];
			$data[] = $row;
		}
		$result = Query("select personid, portaluserid from portalperson where personid in ('" . implode("','",$personids) . "') order by personid, portaluserid");
		$portaluserids = array();
		$personportalusers = array();
		while($row = DBGetRow($result)){
			$portaluserids[] = $row[1];
			if(!isset($personportalusers[$row[0]]))
				$personportalusers[$row[0]] = array();
			$personportalusers[$row[0]][] = $row[1];
		}
		$portalusers = getPortalUsers($portaluserids);
		$newdata = array();
		foreach($data as $index => $row){
			if(!isset($personportalusers[$row[1]])){
				array_splice($row, 6, 0, array(""));
				$newdata[] = $row;
			} else {
				foreach($personportalusers[$row[1]] as $portaluserid){
					if(isset($portalusers[$portaluserid])){
						$portaluser = $portalusers[$portaluserid];
						$portaluserinfo = $portaluser['portaluser.firstname'] . " " . $portaluser['portaluser.lastname'] . " (" . $portaluser['portaluser.username'] . ")";
						array_splice($row, 6, 0, array($portaluserinfo));
						$newdata[] = $row;
					}
				}
			}
		}
		$data = $newdata;
		$titles = array(0 => "ID#",
						2 => "First Name",
						3 => "Last Name",
						4 => "Activation Code",
						5 => "Expiration Date",
						6 => "Contact Manager Account(s)");

		$titles = appendFieldTitles($titles, 6, $fieldlist, $activefields);

		$repeatedColumns = array(6);

		$formatters = array(0 => "fmt_idmagnify",
							5 => "fmt_date",
							4 => "fmt_activation_code");

		startWindow("Search Results");
		showPageMenu($this->reporttotal,$pagestart,$max);
?>
			<table width="100%" cellpadding="3" cellspacing="1" class="list" id="portalresults">
<?
				showTable($data, $titles, $formatters, $repeatedColumns, 0);
?>
			</table>
<?
		showPageMenu($this->reporttotal,$pagestart,$max);
		endWindow();
?>
		<script langauge="javascript">
			var portalresultstable = new getObj("portalresults").obj;
		</script>
<?
	}

	function runCSV(){

		$fields = FieldMap::getOptionalAuthorizedFieldMaps();
		$fieldlist = array();
		foreach($fields as $field){
			$fieldlist[$field->fieldnum] = $field->name;
		}
		$activefields = explode(",", isset($this->params['activefields']) ? $this->params['activefields'] : "");
		$titles = array(0 => "ID#",
						2 => "First Name",
						3 => "Last Name",
						4 => "Token",
						5 => "Expiration Date");

		// find the f-fields the same way as the query did
		// strip off the f, use the field number as the index and
		// it's position as the offset
		$fieldindex = explode(",",generateFields("p"));
		foreach($fieldindex as $index => $fieldnumber){
			$aliaspos = strpos($fieldnumber, ".");
			if($aliaspos !== false){
				$fieldindex[$index] = substr($fieldnumber, $aliaspos+1);
			}
		}
		$fieldindex = array_flip($fieldindex);
		$activefields = array_flip($activefields);
		foreach($fieldlist as $fieldnum => $fieldname){
			if(isset($activefields[$fieldnum])){
				$titles[] = $fieldname;
			}
		}

		header("Pragma: private");
		header("Cache-Control: private");
		header("Content-disposition: attachment; filename=report.csv");
		header("Content-type: application/vnd.ms-excel");

		session_write_close();//WARNING: we don't keep a lock on the session file, any changes to session data are ignored past this point

		echo '"' . implode('","', $titles) . '"';
		echo "\r\n";

		$data = array();
		$query = $this->query . " order by p.id";
		$result = Query($query);
		$count = 0;
		while($row = DBGetRow($result)){
			if($row[4]){
				if(strtotime($row[5]) < strtotime("now")){
					$row[4] = "Expired";
				}
			}
			if($row[5]){
				$row[5] = date("m/d/Y", strtotime($row[5]));
			}
			$array = array($row[0], $row[2], $row[3], $row[4],$row[5]);

			//index 13 is the last position of a non-ffield
			foreach($fieldlist as $fieldnum => $fieldname){
				if(isset($activefields[$fieldnum])){
					$num = $fieldindex[$fieldnum];
					$array[] = $row[5+$num];
				}
			}

			echo '"' . implode('","', $array) . '"';
			echo "\r\n";
		}

	}

}

?>