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
		if (isset($this->args['jobtypeid']))
			$jobtypeid = $this->args['jobtypeid'];
		else
			$jobtypeid = 0;
		
		
		$str = '
			$("'.$n.'").observe("change", function (event) {
				loadMessageGroupPreview.curry('.$jobtypeid.');
				getMessageGroupPreviewGrid($("'.$n.'").value, $("'.$n.'_preview"), '.$jobtypeid.');
				Event.fire($("'.$this->form->name.'"), "MessageGroup:Change", $("'.$n.'").value);
			});
			getMessageGroupPreviewGrid($("'.$n.'").value, $("'.$n.'_preview"), '.$jobtypeid.');
		';
		
		if (isset($this->args["jobtypeidtarget"])) {
			$str .= "
			var form = $('" . $this->form->name . "');
			$(form.name + '_{$this->args["jobtypeidtarget"]}').observe('change', function(event) {
				var jobtypeid = form_get_value(form, form.name + '_{$this->args["jobtypeidtarget"]}');
				$('".$n."').observe('change', loadMessageGroupPreview.curry(jobtypeid));
				getMessageGroupPreviewGrid($('".$n."').value, $('".$n."_preview'), jobtypeid);
			});
			";
		}
		return $str;
		
	}
		
	function renderJavascriptLibraries() {
		$str = '
			<script type="text/javascript" src="script/getMessageGroupPreviewGrid.js"></script>
			<script type="text/javascript">
				function loadMessageGroupPreview(jobtypeid, event) {
					var formitem = event.element();
					container = $(formitem.id + "_preview");
					getMessageGroupPreviewGrid(formitem.value, container, jobtypeid);
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
