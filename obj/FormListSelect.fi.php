<?
class FormListSelect extends FormItem {
	var $clearonsubmit = true;
	var $clearvalue = array();
	
	function render ($value) {
		global $USER;
		$n = $this->form->name."_".$this->name;
		
		$args = array();
		$extrasql = "";
		if (isset($this->args['jobid'])) {
			$args[] = $this->args['jobid'];
			$extrasql = " or exists (select * from joblist jl where jl.jobid = ? and jl.listid = list.id)";
		}
		// Need to pass in user id twice
		$args[] = $USER->id;  
		$args[] = $USER->id;
		// get lists this user has access to, both owned and subscribed
		$query = "(select id, name, (name +0) as digitsfirst from list 
					where type != 'alert' and (not deleted $extrasql) and userid = ?)
					UNION
					(select l.id as id, l.name as name, (l.name +0) as digitsfirst from list l inner join publish p on (l.id = p.listid)
					where not l.deleted
					and p.userid = ?
					and p.action = 'subscribe'
					and p.type = 'list'
					)
					order by digitsfirst, name";
		
		$lists = QuickQueryList($query,true, false, $args);
				
		// look up the list details for lists in $value
		$listdetails = array();
		foreach ($value as $listid) {
			// only get list stats if the user has access to the listid, ignore invalid listids
			if (isset($lists[$listid])) {
				$list = new PeopleList($listid);
				$renderedlist = new RenderedList2();
				$renderedlist->initWithList($list);
				$listdetails[$listid] = array("name" => $list->name, "total" => $renderedlist->getTotal());
			}
		}
		
		$str .= '<ul id='.$n.' class="radiobox">';
		
		// add a checkbox for every list this user can use and check ones already selected
		foreach ($lists as $id => $name) {
			$checked = isset($listdetails[$id]);
			$str .= '<li><input id="'. "$n-$id" .'" name="'.$n.'[]" type=checkbox value="'. $id .'" '. ($checked?'checked':'') .' onclick="formlistselectcheck(this.id, \''.$n.'\')"/>
			<label for="'. "$n-$id" .'">'. $name .'</label></li>';
		}
		
		$str .= '</ul>';
		
		// create a table for the list details
		$str .= '<div class="form_list_table">
				<table class="list">
					<thead>
						<tr class="listHeader">
							<th class="tcol70">'._L("List Name").'</th>
							<th width="tcol20">'._L("Count").'</th>
						</tr>
					</thead>
					<tbody id="'. $n .'-displaybody">
					';
					// keep track of the total people in all lists
					$grandtotal = 0;
					// add lists to the detail table
					foreach ($listdetails as $listid => $details) {
						$grandtotal += $details['total'];
						$str .= '
						<tr id="'. "$n-$listid" .'-display">
							<td id="'. "$n-$listid" .'-name">'. $details['name'] .'</td>
							<td id="'. "$n-$listid" .'-total">'. $details['total'] .'</td>
						</tr>';
					}
					$str .= '
					</tbody>
					<tfoot>
						<tr>
							<td class="border">
								<b>'._L("Total").'</b>
							</td>
							<td class="border">
								<div id="'. $n .'-grandtotal" class="listfooter">'. $grandtotal .'</div>
							</td>
						</tr>
					</tfoot>
				</table>
					</div>';
		
		return $str;
	}
	
	function renderJavascript() {
	return '
		function formlistselectcheck(listcheckbox, formitemname) {
			listcheckbox = $(listcheckbox);

			// see if there is already an entry for it in the lists display
			if ($(listcheckbox.id + "-display")) {
				// decrement total
				var listtotal = parseInt($(listcheckbox.id + "-total").innerHTML);
				var grandtotal = (parseInt($(formitemname + "-grandtotal").innerHTML) - listtotal);
				$(formitemname + "-grandtotal").update(grandtotal);
				// remove the displayed list stats
				$(listcheckbox.id + "-display").remove();
			}

			if (listcheckbox.checked) {
				// insert an item in the table of list statistics
				$(formitemname + "-displaybody").insert(
					new Element("tr", {id: listcheckbox.id + "-display"}).insert(
						new Element("td", {id: listcheckbox.id + "-name"}).insert(
							new Element("img", {src: "img/ajax-loader.gif"})
						)
					).insert(
						new Element("td", {id: listcheckbox.id + "-total"}).update()
					));
				cachedAjaxGet("ajax.php?type=liststats&listids=" + [listcheckbox.value].toJSON(),
					function (transport, listcheckboxid) {
						var stats = transport.responseJSON;
						var listid = $(listcheckboxid).value;
						// set the list name 
						$(listcheckboxid + "-name").update(stats[listid].name);
						// set the list total
						$(listcheckboxid + "-total").update(stats[listid].total);
						// set the grand total
						var grandtotal = parseInt($(formitemname + "-grandtotal").innerHTML) + stats[listid].total;
						$(formitemname + "-grandtotal").update(grandtotal);
					}, listcheckbox.id, true);
			}
		}';
	}
}
?>