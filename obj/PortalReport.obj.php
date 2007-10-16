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
		if(isset($this->params['pkey'])){
			$pkeysql = " and p.pkey = '" . DBSafe($this->params['pkey']) . "' ";
		}
		if(isset($this->params['hideactivetokens']) && $this->params['hideactivetokens']){
			$hideactivetokens = " and (ppt.token is null or ppt.expirationdate < now()) ";
		}
		if(isset($this->params['hideassociated']) && $this->params['hideassociated']){
			$hideassociated = " and not exists (select count(*) from portalperson pp where pp.personid = p.id group by pp.personid) ";
		}
		$this->query = "select SQL_CALC_FOUND_ROWS
					p.pkey as pkey, 
					p.id as pid,
					p." . FieldMap::GetFirstNameField() . " as firstname, 
					p." . FieldMap::GetLastNameField() . " as lastname, 
					ppt.token,
					ppt.expirationdate "
					. generateFields("p")
					. " from person p 
					left join portalpersontoken ppt on (ppt.personid = p.id)
					where not p.deleted
					and p.type='system' "
					. $pkeysql
					. $rulesql
					. $hideactivetokens
					. $hideassociated
					. $usersql
					. " order by p.id";
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

		while($row = DBGetRow($result)){
			//$phones = QuickQueryList("select phone from phone where personid = '$row[1]'");
			//$emails = QuickQueryList("select phone from phone where personid = '$row[1]' and phone like '9993%'");
			//$row = array_insert($row, array($phones, $emails), 3);
			$associateids = QuickQueryList("select portaluserid from portalperson where personid = '$row[1]'");
			$associates = getPortalUsers($associateids);
			if($associates){
				$associatenames = array();
				foreach($associates as $associate){
					$associatenames[] = $associate["portaluser.firstname"] . " " . $associate["portaluser.lastname"] . " (" . $associate["portaluser.username"] . ")";
				}
			} else
				$associatenames = array("");
			$row = array_insert($row, array($associatenames), 5);

			$data[] = $row;
		}
		$data = flattenData($data);

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
			$titles[$end] = $field;
			if(!in_array($index, $activefields)){
				$hiddenColumns[] = $end;
			}
			$end++;
		}

		$formatters = array(0 => "fmt_idmagnify",
							5 => "fmt_date");
		
		startWindow("Search Results");
		showPageMenu($total,$pagestart,$max);
?>
			<table width="100%" cellpadding="3" cellspacing="1" class="list" id="portalresults">
<?
				showTableWithHidden($data, $titles, $formatters, $hiddenColumns, 0);
?>
			</table>
			<script langauge="javascript">
				var portalresultstable = new getObj("portalresults").obj;
			</script>
<?
		showPageMenu($total,$pagestart,$max);
		endWindow();
	}
	
	function runCSV(){
	
		
	}

}

?>