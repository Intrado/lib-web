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
		if(isset($this->params['phone'])){
			$phonequery = $this->params['phone'] ? " and ph.phone like '%" . DBSafe($this->params['phone']) . "%'" : "";
		} 
		if(isset($this->params['email'])){
			$emailquery = $this->params['email'] ? " and e.email like '%" . DBSafe($this->params['email']) . "%'" : "";
		}
		if(isset($this->params['personid'])){
			$personquery = $this->params['personid'] ? " and p.pkey like '%" . DBSafe($this->params['personid']) . "%'" : "";
		}
		$fieldquery = generateFields("p");
		
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
					left join phone ph on (ph.personid = p.id)
					left join email e on (e.personid = p.id)
					where not p.deleted
					and p.type='system'
					$phonequery
					$emailquery
					$personquery
					$usersql
					$rulesql		
					group by p.id
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
		$query .= "limit $pagestart, 500";
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
		showPageMenu($total,$pagestart,500);
		?>
			<table width="100%" cellpadding="3" cellspacing="1" class="list" id="searchresults">
			<tr class="listHeader">
				<td>ID#</td>
				<td>First Name</td>
				<td>Last Name</td>
				<td>Address</td>
				<td>Sequence</td>
				<td>Destination</td>
			<?
			for($i=0;$i<20; $i++){
				if($i<10){
					$num = "f0" . $i;
				} else {
					$num = "f" . $i;
				}
				if(in_array($num, array_keys($fieldlist))){
					?><td><?=$fieldlist[$num]?></td><?
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
									for($i=0;$i<20; $i++){
										if($i<10){
											$num = "f0" . $i;
										} else {
											$num = "f" . $i;
										}
										if(in_array($num, array_keys($fieldlist))){
											?><td><?=htmlentities($person[5+$count])?></td><?
											$count++;
										}
									}
								} else {
									for($i=0; $i<$fieldcount; $i++){
										?><td>&nbsp;</td><?
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
								for($i=0;$i<20; $i++){
									if($i<10){
										$num = "f0" . $i;
									} else {
										$num = "f" . $i;
									}
									if(in_array($num, array_keys($fieldlist))){
										?><td><?=htmlentities($person[5+$count])?></td><?
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
								
								for($i=0; $i<$fieldcount; $i++){
									?><td>&nbsp;</td><?
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
			
		<?
			$count=1;
			foreach($fieldlist as $index => $field){
				?>
				setColVisability(searchresultstable, 5+<?=$count?>, new getObj("hiddenfield".concat('<?=$index?>')).obj.checked);
				<?
				$count++;
			}
		?>
			</script>
		<?
		showPageMenu($total,$pagestart,500);
		endWindow();
	}
	
	function runCSV(){
		echo "Not ready yet";
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