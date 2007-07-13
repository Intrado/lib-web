<?

class ContactsReport extends ReportGenerator {

	function generateQuery(){
		global $USER;
		
		$instance = $this->reportinstance;
		$params = $this->params = $instance->getParameters();
		$this->reporttype = $params['reporttype'];
		$orders = array("order1", "order2", "order3");
		$orderquery = "";
		foreach($orders as $order){
			if(!isset($params[$order]))
				continue;
			$orderby = $params[$order];
			if($orderby == "") continue;
			if($orderquery == ""){
				$orderquery = " order by ";
			} else {
				$orderquery .= ", ";
			}
			$orderquery .= $orderby;
		}
		$rulesql = "";
		if(isset($params['rules']) && $params['rules']){
			$rules = explode("||", $params['rules']);
			foreach($rules as $rule){
				if($rule) {
					$rule = explode(";", $rule);
					$newrule = new Rule();
					$newrule->logical = $rule[0];
					$newrule->op = $rule[1];
					$newrule->fieldnum = $rule[2];
					$newrule->val = $rule[3];
					$rulesql .= " " . $newrule->toSql("p");
				}
			}
		}
		$usersql = $USER->userSQL("p");
		$phonequery="";
		$emailquery="";
		if(isset($params['phone'])){
			$phonequery = $params['phone'] ? " and ph.phone like '%" . DBSafe($params['phone']) . "%'" : "";
		} 
		if(isset($params['email'])){
			$emailquery = $params['email'] ? " and e.email like '%" . DBSafe($params['email']) . "%'" : "";
		}
		if(isset($params['personid'])){
			$emailquery = $params['personid'] ? " and p.pkey like '%" . DBSafe($params['personid']) . "%'" : "";
		}
		if($orderquery == "")
			$orderquery = "order by p.id";
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
					
					where 1
					$phonequery
					$emailquery
					$usersql
					$rulesql
					group by p.id
					$orderquery
					";
	}

	function runHtml($params = null){

		$fieldlist = $this->reportinstance->getFields();
		$activefields = $this->reportinstance->getActiveFields();
		$fieldcount = count($fieldlist);
		$options = $this->params;
		$query = $this->query;
		$pagestart = isset($options['pagestart']) ? $options['pagestart'] : 0;
		$query .= "limit $pagestart, 500";
		$result = Query($query);
		$total = QuickQuery("select found_rows()");
		$phonelist = array();
		$emaillist = array();
		$phonelist = array();
		while($row = DBGetRow($result)){
			$personlist[$row[1]] = $row;
			$phoneresult = Query("Select sequence, phone from phone where personid = '$row[1]' order by sequence");
			while($phonerow = DBGetRow($phoneresult)){
				if(!isset($phonelist[$row[1]]) || !is_array($phonelist[$row[1]])){
					$phonelist[$row[1]] = array();
				}
				$phonelist[$row[1]][] = $phonerow;
			}
			$emailresult = Query("Select sequence, email from email where personid = '$row[1]' order by sequence");
			while($emailrow = DBGetRow($emailresult)){
				if(!isset($emaillist[$row[1]]) || !is_array($emaillist[$row[1]])){
					$emaillist[$row[1]] = array();
				}
				$emaillist[$row[1]][] = $emailrow;
			}
		}
		
		startWindow("Search Information", "padding: 3px;"); 
		?>
			<table>
<?
				if(isset($options['personid']) && $options['personid'] != "") {
?>
				<tr><td>Person ID: <?=$options['personid']?></td></tr>
<?
				}
				if(isset($options['phone']) && $options['phone'] != "") {
?>
				<tr><td>Phone: <?=$options['phone']?></td></tr>
<?
				}
				if(isset($options['email']) && $options['email'] != "") {
?>			
				<tr><td>Email: <?=$options['email']?></td></tr>
<?
				}
?>
			</table>
		<? 
		endWindow();
		?>
		<br>
		<?
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
						if(isset($phonelist)){
							foreach($phonelist[$id] as $phone){
								if(!$first) {
									echo $alt % 2 ? '<tr>' : '<tr class="listAlt">';
									?><td></td><td></td><td></td><td></td><?
								}
								?>
								<td><?=$phone[0]+1?></td><td><?=fmt_phone_contact($phone[1])?></td>
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
											?><td><?=$person[5+$count]?></td><?
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
						}
						if(isset($emaillist[$id])){
							foreach($emaillist[$id] as $email){
								
								if(!$first) {
									echo $alt % 2 ? '<tr>' : '<tr class="listAlt">';
									?><td></td><td></td><td></td><td></td><?
								}
								?><td><?=$email[0] +1?></td><td><?=$email[1]?></td>
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
											?><td><?=$person[5+$count]?></td><?
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
	
	function setReportFile(){
		$this->reportfile = "contactsreport.jasper";
	}
}

?>