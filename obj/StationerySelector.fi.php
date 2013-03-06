<?

class StationerySelector extends FormItem {
	function render ($value) {
		$selectortype = isset($this->args['layoutselector'])?"layout":"stationery";
		
		$n = $this->form->name."_".$this->name;
		$str = '
		<div class="stationeryselector">
		<select id=' .$n. ' name="'.$n.'" onchange="$(\'stationerypreview\').src = \'mgstationeryview.php?preview&' . $selectortype . '=\' + this.value">
		<option value="">-- Select ' . ucfirst($selectortype) . ' --</option>
		';
		foreach ($this->args['values'] as $selectvalue => $radioname) {
			$str .= '<option value="' . $selectvalue . '">' . escapehtml($radioname) . '</option>';
		}
		$str .= "</select>
		</div>
		<div class=\"stationerypreviewfield\">
		<fieldset id=\"stationerypreviewfield\">
		<legend>Email " . ucfirst($selectortype) . " Preview:</legend>
		<iframe id=\"stationerypreview\"  src=\"blank.html\"></iframe>
		</fieldset>
		</div>";

		return $str;
	}
	
	function renderJavascriptLibraries() {
	
		$str = "<script type=\"text/javascript\">
						function messagePrevewLoaded(area) {
						
								if(area.height() > 370) {
									jQuery('#stationerypreview').height(area.height() + 30);
								} else {
									jQuery('#stationerypreview').height(400);
								}
								area.bind('click', function(event) {
									// Trigger submit on click won't work in form
									//jQuery('button[name=\"submit\"]').trigger(event);
								});
						}
		</script>";
		return $str;
	}
}

?>