<?

class PortalReport extends ReportGenerator{

	var $reporttotal = 0;

	function generateQuery(){
		global $USER;
		$this->params = $this->reportinstance->getParameters();
		$rulesql = getRuleSql($this->params, "p");
		$usersql = $USER->userSQL("p");
		$pkeysql = "";
		$hideactivetokens = "";
		$hideassociated = "";
		$hideassociatedtable = "";
		$showall = false;
		if(isset($this->params['showall']))
			$showall = true;
		if(isset($this->params['pkey'])){
			$pkeysql = " and p.pkey = '" . DBSafe($this->params['pkey']) . "' ";
		}
		if(isset($this->params['hideactivetokens']) && $this->params['hideactivetokens']){
			$hideactivetokens = " and (ppt.token is null or ppt.expirationdate < curdate()) ";
		}
		if(isset($this->params['hideassociated']) && $this->params['hideassociated']){
			$hideassociated = " and ppcount.count is null ";
			$hideassociatedtable = " left join 
									(select count(*) as count, pp.personid as ppcountpersonid 
									from portalperson pp 
									group by pp.personid) 
									as ppcount on (ppcount.ppcountpersonid = p.id) ";
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
						left join portalpersontoken ppt on (ppt.personid = p.id) "
						. $hideassociatedtable . 	
						"where not p.deleted
						and p.type='system' "
						. $pkeysql
						. $rulesql
						. $hideactivetokens
						. $hideassociated
						. $usersql
						. " order by p.id";
			//test query used to confirm no active codes are in the list
			$this->testquery = "select count(ppt.token)
						from person p 
						left join portalpersontoken ppt on (ppt.personid = p.id) "
						. $hideassociatedtable .
						" where not p.deleted
						and p.type='system'
						and ppt.expirationdate > curdate() "
						. $pkeysql
						. $rulesql
						. $hideactivetokens
						. $hideassociated
						. $usersql;
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
				$newdata[] = array_insert($row, array(""), 5);
			} else {
				foreach($personportalusers[$row[1]] as $portaluserid){
					if(isset($portalusers[$portaluserid])){
						$portaluser = $portalusers[$portaluserid];
						$portaluserinfo = $portaluser['portaluser.firstname'] . " " . $portaluser['portaluser.lastname'] . " (" . $portaluser['portaluser.username'] . ")";
						$newdata[] = array_insert($row, array($portaluserinfo), 5);
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
						6 => "Associated Portal Users");
						
		$titles = appendFieldTitles($titles, 6, $fieldlist, $activefields);

		$repeatedColumns = array(6);
		
		$formatters = array(0 => "fmt_idmagnify",
							5 => "fmt_activation_date",
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
		$titles = appendFieldTitles($titles, 5, $fieldlist, $activefields);
		
		$formatters = array(4 => "fmt_activation_code",
							5 => "fmt_activation_date");
		
		$data = array();
		$result = Query($this->query);
		while($row = DBGetRow($result)){
			$data[] = $row;
		}
		
		header("Pragma: private");
		header("Cache-Control: private");
		header("Content-disposition: attachment; filename=report.csv");
		header("Content-type: application/vnd.ms-excel");
		
		session_write_close();//WARNING: we don't keep a lock on the session file, any changes to session data are ignored past this point
		
		createCSV($data, $titles, $formatters, 0);
	}

}

?>