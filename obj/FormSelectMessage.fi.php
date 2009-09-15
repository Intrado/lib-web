<?
// Select message (phone, email, or sms)
class SelectMessage extends FormItem {
	function render ($value) {
		$n = $this->form->name."_".$this->name;
		$varname = str_replace(" ", "_", $n);
		$str = '<select id="'.$n.'" name="'.$n.'" onchange="'.$varname.'messageselect.getMessage();" >
			<option value=""> --- '._L("Select One").' --- </option>
		';
		foreach ($this->args['values'] as $selectid => $selectvals) {
			$checked = $value == $selectid;
			$str .= '<option value="'.escapehtml($selectid).'" '.($checked ? 'selected' : '').' >'.escapehtml($selectvals['name']).'</option>';
		}
		$str .= '</select>
		<table id="'.$n.'details" class="msgdetails" width="'.$this->args['width'].'">
		<tr><td class="msglabel">'._L("Last Used").':</td><td><span id="'.$n.'lastused" class="msginfo">...</span></td></tr>
		<tr id="'.$n.'-descTR"><td class="msglabel">'._L("Description").':</td><td><span id="'.$n.'description" class="msginfo">...</span></td></tr>';
		if ($this->args['type'] == 'email') {
			$str .= '<tr><td class="msglabel">'._L("From").':</td><td><span id="'.$n.'from" class="msginfo">...</span></td></tr>
			<tr><td class="msglabel">'._L("Subject").':</td><td><span id="'.$n.'subject" class="msginfo">...</span></td></tr>
			<tr><td class="msglabel">'._L("Attachment").'t:</td><td><span id="'.$n.'attachment" class="msgattachment">...</span></td></tr>';
		}
		if ($this->args['type'] == 'phone') {
			$str .= '<tr><td class="msglabel">'._L("Preview").':</td><td>'.icon_button("Play","play",null,null,'id="'.$n.'play"').'</td></tr>';
		}
		$str .= '<tr id="'.$n.'-bodyTR"><td class="msglabel">'._L("Body").':</td><td><textarea style="width:100%" rows="12" readonly id="'.$n.'body" >...</textarea></td></tr>
		</table>
		<script type="text/javascript" src="script/messageselect.js"></script>
			<script type="text/javascript">
			'.((isset($this->args['readonly']) && $this->args['readonly'])?'$("'.$n.'").hide()':'').'
			var '.$varname.'messageselect = new MessageSelect("'.$n.'","'.$this->args['type'].'");
		</script>';
		return $str;
	}
}
?>
