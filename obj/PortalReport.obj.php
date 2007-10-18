<?

class PortalReport extends ReportGenerator{

	function generateQuery(){
		global $USER;
		$this->params = $this->reportinstance->getParameters();
		$rulesql = getRuleSql($this->params, "p");
		$usersql = $USER->userSQL("p");
		$pkeysql = "";
		$hideactivetokens = "";
		$hideassociated = "";
		$showall = false;
		
		if(isset($this->params['showall']))
			$showall = true;
		if(isset($this->params['pkey'])){
			$pkeysql = " and p.pkey = '" . DBSafe($this->params['pkey']) . "' ";
		}
		if(isset($this->params['hideactivetokens']) && $this->params['hideactivetokens']){
			$hideactivetokens = " and (ppt.token is null or ppt.expirationdate < now()) ";
		}
		if(isset($this->params['hideassociated']) && $this->params['hideassociated']){
			$hideassociated = " and not exists (select count(*) from portalperson pp where pp.personid = p.id group by pp.personid) ";
		}
		if($rulesql || $pkeysql || $showall){
			$this->query = "select SQL_CALC_FOUND_ROWS
						p.pkey as pkey, 
						p.id as pid,
						p." . FieldMap::GetFirstNameField() . " as firstname, 
						p." . FieldMap::GetLastNameField() . " as lastname, 
						ppt.token,
						ppt.expirationdate,
						pp.portaluserid "
						. generateFields("p")
						. "	from person p 
						left join portalpersontoken ppt on (ppt.personid = p.id)
						left join portalperson pp on (pp.personid = p.id)
						where not p.deleted
						and p.type='system' "
						. $pkeysql
						. $rulesql
						. $hideactivetokens
						. $hideassociated
						. $usersql
						. " order by p.id";
		} else {
			$this->query = "";
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
		$query = $this->query . " limit $pagestart, $max";
		$result = Query($query);
		$total = QuickQuery("select found_rows()");
		$data = array();
		$portaluserids = array();
		$count = 0;
		$curr = null;
		while($row = DBGetRow($result)){
			if($row[6])
				$portaluserids[] = $row[6];
			$data[] = $row;
			if($curr != $row[1]){
				$count++;
				$curr = $row[1];
			}
		}
		$portalusers = getPortalUsers($portaluserids);
		foreach($data as $index => $row){
			if(isset($portalusers[$row[6]])){
				$portaluser = $portalusers[$row[6]];
				$row[6] = $portaluser['portaluser.firstname'] . " " . $portaluser['portaluser.lastname'];
				$data[$index] = $row;
			}
		}
		
		$titles = array(0 => "ID#", 
						2 => "First Name", 
						3 => "Last Name",
						4 => "Activation Code",
						5 => "Expiration Date",
						6 => "Associated Portal Users");
						
		//set the initial column index for fields
		//by end of loop, end should be the last column index for a field
		$start = 7;
		$end = $start;
		$hiddenColumns = array();

		foreach($fieldlist as $index => $field){
			if(!in_array($index, $activefields)){
				$titles[$end] = "@" .$field;
			} else {
				$titles[$end] = $field;
			}
			$end++;
		}

		$repeatedColumns = array(6);
		
		$formatters = array(0 => "fmt_idmagnify",
							5 => "fmt_date");
		
		startWindow("Search Results");
		showPageMenu($total,$pagestart,$count);
?>
			<table width="100%" cellpadding="3" cellspacing="1" class="list" id="portalresults">
<?
				showTable($data, $titles, $formatters, $repeatedColumns, 0);
?>
			</table>
			<script langauge="javascript">
				var portalresultstable = new getObj("portalresults").obj;
			</script>
<?
		showPageMenu($total,$pagestart,$count);
		endWindow();
	}
	
	function runCSV(){
		
		
		$titles = array("ID#", "First Name", "Last Name", "Token", "Expiration Date");
		$titles = '"' . implode('","',$titles) . '"';
		
		header("Pragma: private");
		header("Cache-Control: private");
		header("Content-disposition: attachment; filename=report.csv");
		header("Content-type: application/vnd.ms-excel");
		
		session_write_close();//WARNING: we don't keep a lock on the session file, any changes to session data are ignored past this point
		
		echo $titles;
		echo "\r\n";
		$curr = null;
		if($this->query){
			$result = Query($this->query);
			while($row = DBGetRow($result)){
				if($curr == $row[0])
					continue;
				$curr = $row[0];
			
				$date = "";
				if($row[5])
					$date = date("M d Y", strtotime($row[5]));
		
				$data = array($row[0], $row[2], $row[3], $row[4], $date);
				$output = '"' . implode('","', $data) . '"';
				echo $output;
				echo "\r\n";
			}
		}
	}

}

?>