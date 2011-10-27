<?
class MessageGroupSelectMenu extends FormItem {
	function render ($value) {
		
		$n = $this->form->name."_".$this->name;
		$size = isset($this->args['size']) ? 'size="'.$this->args['size'].'"' : "";
		$isstatic = isset($this->args['static']) && $this->args['static'] == true && $value;
		$str = '<select id='.$n.' name="'.$n.'" '.$size . ' ' . ($isstatic?'disabled':'') . ' >';
		foreach ($this->args['values'] as $selectvalue => $selectname) {
			$checked = $value == $selectvalue;
			$str .= '<option value="'.escapehtml($selectvalue).'" '.($checked ? 'selected' : '').' >'.escapehtml($selectname).'</option>';
		}
		$str .= '</select>';

		$issurveytemplate = isset($this->args['surveytemplate']) && $this->args['surveytemplate'] == true;
		if (!$issurveytemplate)
			$str .= '<div id="'.$n.'_preview"></div>';
		
		return $str;
	}
		
	function renderJavascript($value) {
		$n = $this->form->name."_".$this->name;
		// jobtype.systempriority used for email message preview
		if (isset($this->args['jobpriority']))
			$jobpriority = $this->args['jobpriority'];
		else
			$jobpriority = 3; // general
		$str = '
			$("'.$n.'").observe("change", loadMessageGroupPreview.curry('.$jobpriority.'));
			getMessageGroupPreviewGrid($("'.$n.'").value, $("'.$n.'_preview"), '.$jobpriority.');
		';
		return $str;
		
	}
		
	function renderJavascriptLibraries() {
		$str = '
			<script type="text/javascript" src="script/getMessageGroupPreviewGrid.js"></script>
			<script type="text/javascript">
				function loadMessageGroupPreview(priority, event) {
					var formitem = event.element();
					container = $(formitem.id + "_preview");
					getMessageGroupPreviewGrid(formitem.value, container, priority);
				}
			</script>';
		return $str;
	}
}

class ValMessageTranslationExpiration extends Validator {
	var $onlyserverside = true;
	function validate ($value, $args,$requiredvalues) {
		global $USER;
		if(!isset($requiredvalues['date']))
			return true;
		$modifydate = QuickQuery("select min(modifydate) from message where messagegroupid = ? and autotranslate = 'translated'", false, array($value));
		if($modifydate != false) {
			if(strtotime("today") - strtotime($modifydate) > (7*86400))
				return _L('The selected message contains auto-translated content older than 7 days. Regenerate translations to schedule a start date');
			if(strtotime($requiredvalues['date']) - strtotime($modifydate) > (7*86400))
				return _L("Cannot schedule the job with a message containing auto-translated content older than 7 days from the Start Date");
		}
		return true;
	}
}
?>
