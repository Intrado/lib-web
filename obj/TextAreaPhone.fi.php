<?

class TextAreaPhone extends FormItem {
	function render ($value) {
		$n = $this->form->name."_".$this->name;
		if (!$value)
			$value = '{"gender": "female", "text": ""}';
		$vals = json_decode($value);
		$rows = isset($this->args['rows']) ? 'rows="'.$this->args['rows'].'"' : "";
		$str = '<input id="'.$n.'" name="'.$n.'" type="hidden" value="'.escapehtml($value).'"/>
			<textarea id="'.$n.'-textarea" style="clear:both; width:'.$this->args['width'].'" name="'.$n.'-textarea" '.$rows.'/>'.escapehtml($vals->text).'</textarea>
			<div>
				<input id="'.$n.'-female" name="'.$n.'-gender" type="radio" value="female" '.($vals->gender == "female"?"checked":"").'/><label for="'.$n.'-female">'._L('Female').'</label><br />
				<input id="'.$n.'-male" name="'.$n.'-gender" type="radio" value="male" '.($vals->gender == "male"?"checked":"").'/><label for="'.$n.'-male">'._L('Male').'</label><br />
			</div>
			<div>'.icon_button(_L("Play"),"fugue/control",null,null,"id=\"".$n."-play\"").'</div>
			<script type="text/javascript">
				$("'.$n.'-play").observe("click", function(e) {
					var val = $("'.$n.'-textarea").value;
					var gender = ($("'.$n.'-female").checked?"female":"male");
					if (val) {
						popup(\'previewmessage.php?parentfield='.$n.'-textarea&language='.urlencode($this->args['language']).'&gender=\'+ gender, 400, 400,\'preview\');
					}
				});

				$("'.$n.'-textarea").observe("change", textAreaPhone_storedata.curry("'.$n.'"));
				$("'.$n.'-textarea").observe("blur", textAreaPhone_storedata.curry("'.$n.'"));
				$("'.$n.'-textarea").observe("keyup", textAreaPhone_storedata.curry("'.$n.'"));
				$("'.$n.'-textarea").observe("focus", textAreaPhone_storedata.curry("'.$n.'"));
				$("'.$n.'-textarea").observe("click", textAreaPhone_storedata.curry("'.$n.'"));
				$("'.$n.'-female").observe("click", textAreaPhone_storedata.curry("'.$n.'"));
				$("'.$n.'-male").observe("click", textAreaPhone_storedata.curry("'.$n.'"));

				var textAreaPhone_keyupTimer = null;
				function textAreaPhone_storedata(formitem, event) {
					var form = event.findElement("form");
					if (textAreaPhone_keyupTimer) {
						window.clearTimeout(textAreaPhone_keyupTimer);
					}
					textAreaPhone_keyupTimer = window.setTimeout(function () {
							var val = $(formitem).value.evalJSON();
							val.text = $(formitem+"-textarea").value;
							val.gender = ($(formitem+"-female").checked?"female":"male");
							$(formitem).value = Object.toJSON(val);
							form_do_validation(form, $(formitem));
						},
						event.type == "keyup" ? 500 : 100
					);
				}
			</script>
		';
		return $str;
	}
}
?>