<?

class ContactsReport extends ReportGenerator {

	function generateQuery(){
		global $USER;
		$this->params = $this->reportinstance->getParameters();
		$this->reporttype = $this->params['reporttype'];
		
		$orderquery = getOrderSql($this->params);
		$rulesql = getRuleSql($this->params, "p");
		
		$userJoin = " and p.userid = '$USER->id' ";
		
		$usersql = $USER->userSQL("p");
		$phonequery="";
		$emailquery="";
		$personquery="";
		$peoplequery = "";
		if(isset($this->params['phone']) && $this->params['phone'] != ""){
			$peoplephonelist = QuickQueryList("select personid from phone ph where 1 and ph.phone like '%" . DBSafe($this->params['phone']) . "%' group by personid");
		} 
		if(isset($this->params['email']) && $this->params['email'] != ""){
			$peopleemaillist = QuickQueryList("select personid from email e where 1 and e.email like '%" . DBSafe($this->params['email']) . "%'  group by personid");
		}
		if(isset($this->params['personid'])){
			$personquery = $this->params['personid'] ? " and p.pkey like '%" . DBSafe($this->params['personid']) . "%'" : "";
		}
		$fieldquery = generateFields("p");

		if(isset($peoplephonelist) && isset($peopleemaillist))
			$peoplelist = implode("','", array_intersect($peoplephonelist, $peopleemaillist));
		else if(isset($peoplephonelist))	
			$peoplelist = implode("','", $peoplephonelist);
		else if(isset($peopleemaillist))
			$peoplelist = implode("','", $peopleemaillist);
		
		if(isset($peoplelist))
			$peoplequery = " and p.id in ('" . $peoplelist . "') ";
		
		$this->query = "select SQL_CALC_FOUND_ROWS
					p.pkey as pkey, 
					p.id as pid,
					p." . FieldMap::GetFirstNameField() . " as firstname, 
					p." . FieldMap::GetLastNameField() . " as lastname, 
					concat(
							coalesce(a.addr1,''), ' ',
							coalesce(a.addr2,''), ' ',
							coalesce(a.city,''), ' ',
							coalesce(a.state,''), ' ',
							coalesce(a.zip,'')
						) as address
					$fieldquery	
					from person p
					left join address a on (a.personid = p.id)
					where not p.deleted
					and p.type='system'
					$peoplequery
					$personquery
					$usersql
					$rulesql
					$orderquery
					";
	}

	function runHtml(){

		$fields = FieldMap::getOptionalAuthorizedFieldMaps();
		$fieldlist = array();
		foreach($fields as $field){
			$fieldlist[$field->fieldnum] = $field->name;
		}
		$fieldcount = count($fieldlist);
		$query = $this->query;
	
		$pagestart = isset($this->params['pagestart']) ? $this->params['pagestart'] : 0;
		$query .= "limit $pagestart, 100";
		$result = Query($query);
		$total = QuickQuery("select found_rows()");
		$personlist = array();
		$emaillist = array();
		$phonelist = array();
		//fetch data with main query and populate arrays using personid as the key
		while($row = DBGetRow($result)){
			$personlist[$row[1]] = $row;
			$phonelist[$row[1]] = DBFindMany("Phone", "from phone where personid = '$row[1]'");
			$emaillist[$row[1]] = DBFindMany("Email", "from email where personid = '$row[1]'");
		}

		startWindow("Search Results", "padding: 3px;");
		showPageMenu($total,$pagestart,100);
		?>
			<table width="100%" cellpadding="3" cellspacing="1" class="list" id="searchresults">
			<tr class="listHeader">
				<th>ID#</th>
				<th>First Name</th>
				<th>Last Name</th>
				<th>Address</th>
				<th>Sequence</th>
				<th>Destination</th>
			<?
			for($i=0;$i<=20; $i++){
				$num = sprintf("f%02d", $i);
				if(isset($fieldlist[$num])){
					$style = "";
					if(!$_SESSION['fields'][$num]){
						$style = ' style="display:none;" ';
					}
					?><th <?=$style?>><?=$fieldlist[$num]?></th><?
				}
			}
			
		?>
			</tr>
		<?
			$alt = 0;
			foreach($personlist as $person){
				echo ++$alt % 2 ? '<tr>' : '<tr class="listAlt">';
				
				$id = $person[1];
				?>
					<td><?=fmt_idmagnify($person,0)?></td>
					<td><?=$person[2]?></td>
					<td><?=$person[3]?></td>
					<td><?=$person[4]?></td>
					
					<? 
						$first=true;
						if(isset($phonelist[$id])){
							foreach($phonelist[$id] as $phone){
								if($phone->phone == ""){
									continue;
								}
								if(!$first) {
									echo $alt % 2 ? '<tr>' : '<tr class="listAlt">';
?>
										<td></td>
										<td></td>
										<td></td>
										<td></td>
<?
								}
?>
								<td><?=$phone->sequence+1?></td>
								<td><?=Phone::format($phone->phone)?></td>
<?
								if($first){
									$first = false;
									$count=0;
									for($i=0;$i<=20; $i++){
										$num = sprintf("f%02d", $i);
										if(isset($fieldlist[$num])){
											$style = "";
											if(!$_SESSION['fields'][$num]){
												$style = ' style="display:none;" ';
											}
											?><td <?=$style?>><?=htmlentities($person[5+$count])?></td><?
											$count++;
										}
									}
								} else {
									for($i=0;$i<=20; $i++){
										$num = sprintf("f%02d", $i);
										if(isset($fieldlist[$num])){
											$style = "";
											if(!$_SESSION['fields'][$num]){
												$style = ' style="display:none;" ';
											}
											?><td <?=$style?>>&nbsp;</td><?
										}
									}
								}
								?>
								</tr><?
							}
							if($first){
								?>
									<td>&nbsp;</td>
									<td>No Phone Numbers</td>
								<?
								$count=0;
								for($i=0;$i<=20; $i++){
									$num = sprintf("f%02d", $i);
									if(isset($fieldlist[$num])){
										$style = "";
										if(!$_SESSION['fields'][$num]){
											$style = ' style="display:none;" ';
										}
										?><td <?=$style?>><?=htmlentities($person[5+$count])?></td><?
										$count++;
									}
								}
							}
						}
						if(isset($emaillist[$id])){
							foreach($emaillist[$id] as $email){
								if($email->email == ""){
									continue;
								}
								echo $alt % 2 ? '<tr>' : '<tr class="listAlt">';
								?>
									<td></td>
									<td></td>
									<td></td>
									<td></td>
								
									<td><?=$email->sequence+1?></td>
									<td><?=$email->email?></td>
								<? 
								
								for($i=0;$i<=20; $i++){
									$num = sprintf("f%02d", $i);
									if(isset($fieldlist[$num])){
										$style = "";
										if(!$_SESSION['fields'][$num]){
											$style = ' style="display:none;" ';
										}
										?><td <?=$style?>>&nbsp;</td><?
									}
								}
								
								?>
								</tr><?
							}
						}
						
			}
		?>
			</table>
			<script langauge="javascript">
			var searchresultstable = new getObj("searchresults").obj;
			</script>
		<?
		showPageMenu($total,$pagestart,500);
		endWindow();
	}
	
	function runCSV(){
			
		$fields = FieldMap::getOptionalAuthorizedFieldMaps();
		$fieldlist = array();
		foreach($fields as $field){
			$fieldlist[$field->fieldnum] = $field->name;
		}
		$activefields = explode(",", isset($this->params['activefields']) ? $this->params['activefields'] : "");
		
		header("Pragma: private");
		header("Cache-Control: private");
		header("Content-disposition: attachment; filename=report.csv");
		header("Content-type: application/vnd.ms-excel");
	
		session_write_close();//WARNING: we don't keep a lock on the session file, any changes to session data are ignored past this point
	
		//generate the CSV header
		$header = '"ID#", "First Name", "Last Name", "Address"';

		foreach($activefields as $active){
			if(!$active) continue;
			$header .= ',"' . $fieldlist[$active] . '"';
		}
		echo $header;
		echo "\r\n";
	
		$result = Query($this->query);
	
		while ($row = DBGetRow($result)) {
			
			$reportarray = array($row[0], $row[2], $row[3], $row[4]);

			$count=0;
			foreach($fieldlist as $index => $field){
				if(in_array($index, $activefields)){
					$reportarray[] = $row[5+$count];
				}
				$count++;
			}
			$phonelist = DBFindMany("Phone", "from phone where personid = '$row[1]'");
			foreach($phonelist as $phone){
				$reportarray[] = $phone->phone;
			}
			$emaillist = DBFindMany("Email", "from email where personid = '$row[1]'");
			foreach($emaillist as $email){
				$reportarray[] = $email->email;
			}
			echo '"' . implode('","', $reportarray) . '"' . "\r\n";
		
		}
	}
	
	function getReportSpecificParams(){
		return $params;
	}
	
	function setReportFile(){
		$this->reportfile = "Contactsreport.jasper";
	}
	
	static function getOrdering(){
		global $USER;
		$fields = FieldMap::getAuthorizedFieldMaps();
	
		$ordering = array();
		$ordering["ID#"] = "p.pkey";

		foreach($fields as $field){
			$ordering[$field->name]= "p." . $field->fieldnum;
		}
		$ordering["Address"] = "address";
		return $ordering;
	}
}

?>