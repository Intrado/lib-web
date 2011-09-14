<?
class WeekRepeatItem extends FormItem {
	function render ($value) {
		$n = $this->form->name."_".$this->name;
		$hastime = count($value) > 7;
		
		$str = '
				<input id="'.$n.'" name="'.$n.'" type="hidden" value="' . escapehtml(json_encode($value)) . '"/>
				<table border="0" cellpadding="2" cellspacing="1" class="list">
					<tr class="listHeader" align="left" valign="bottom">
						<th>Su</th>
						<th>M</th>
						<th>Tu</th>
						<th>W</th>
						<th>Th</th>
						<th>F</th>
						<th>Sa</th>
						' . ($hastime?'<th>Time</th>':'') . '
					</tr>
					<tr>
						<td><input id="itm0_'.$n.'" type="checkbox" '. ($value[0] ? 'checked' : '').' /></td>
						<td><input id="itm1_'.$n.'" type="checkbox" '. ($value[1] ? 'checked' : '').' /></td>
						<td><input id="itm2_'.$n.'" type="checkbox" '. ($value[2] ? 'checked' : '').' /></td>
						<td><input id="itm3_'.$n.'" type="checkbox" '. ($value[3] ? 'checked' : '').' /></td>
						<td><input id="itm4_'.$n.'" type="checkbox" '. ($value[4] ? 'checked' : '').' /></td>
						<td><input id="itm5_'.$n.'" type="checkbox" '. ($value[5] ? 'checked' : '').' /></td>
						<td><input id="itm6_'.$n.'" type="checkbox" '. ($value[6] ? 'checked' : '').' /></td>
					';
		
		if ($hastime) {
			$str .= '<td><select id="itm7_'.$n.'">';
			foreach ($this->args['timevalues'] as $selectvalue => $selectname) {
				$checked = $value[7] == $selectvalue;
				$str .= '<option value="'.escapehtml($selectvalue).'" '.($checked ? 'selected' : '').' >'.escapehtml($selectname).'</option>
					';
			}
			$str .= '</select></td>';
		}
		$str .=		'
					</tr>
				</table>
				<script type="text/javascript" language="javascript">
					function makerepeat(e) {
						var n = "' .$n. '";
						var values = Array();
						for(var i=0;i < 7;i++) {
							values.push($("itm" + i + "_" +  n).checked);
						}' .
						($hastime?'values.push($("itm7_" +  n).getValue());':'') .
						'$(n).value = values.toJSON();
						form_do_validation($("' . $this->form->name . '"), $("' . $n . '"));
					}
					document.observe("dom:loaded", function() {
						var n = "' .$n. '";
						for(var i=0;i < ' . ($hastime?'8':'7') . ';i++) {
							$("itm" + i + "_"+ n).observe("change",makerepeat);
						}
					});
				</script>
		';
		return $str;
	}
}
?>