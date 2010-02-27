<?

// optional $this->args["readonly"], example: true or false.
// optional $this->args["allowedFields"], single-letters, example: array('f','g','c')
// optional $this->args["ignoredFields"], specific fieldnums, example: array('c01')
// optional $this->args["showRemoveAll"], example: true or false
class FormRuleWidget extends FormItem {
	function render ($rulesJSON) {
		if (!empty($this->args["allowedFields"]))
			$allowedFields = json_encode($this->args["allowedFields"]);
		else
			$allowedFields = json_encode(array('f','g','c'));
		if (!empty($this->args["ignoredFields"]))
			$ignoredFields = json_encode($this->args["ignoredFields"]);
		else
			$ignoredFields = json_encode(array());

		$readonly = (!empty($this->args["readonly"])) ? json_encode(true) : json_encode(false);
		$showRemoveAllButton = !empty($this->args["showRemoveAllButton"]) ? json_encode(true) : json_encode(false);

		$inputname = $this->form->name."_".$this->name;
		if (!$rulesJSON || !is_array(json_decode($rulesJSON)))
			$rulesJSON = '[]';//'[{fieldnum:"f01", type:"text", logical:"and", op:"eq", val:"Kee-Yip"}, {fieldnum:"f02", type:"text", logical:"and", op:"eq", val:"Chan"}]';
		$html = '<input id="'.$inputname.'" name="'.$inputname.'" type="hidden" value="'.escapehtml($rulesJSON).'" />';
		// #ruleWidgetContainer
		$html .= '<div id="ruleWidgetContainer"></div>';
		$html .= '<script type="text/javascript" src="script/rulewidget.js.php"></script>';
		$html .= '<script type="text/javascript" src="script/datepicker.js"></script>';
		// custom javascript
		$html .= "<script type='text/javascript'>
			var ruleWidget = new RuleWidget($('ruleWidgetContainer'), $readonly, $allowedFields, $ignoredFields, $showRemoveAllButton);
			function rulewidget_update_value(event, pending, deleterule) {
				$('$inputname').value = '';
				if (!document.formvars) {
					return;
				}

				if (!deleterule)
					$('$inputname').value = ruleWidget.toJSON();
				if (pending)
					$('$inputname').value = 'pending';

				rulewidget_do_validation();
			}
			function rulewidget_do_validation() {
				form_do_validation($('".$this->form->name."'), $('".$inputname."'));
			}
			ruleWidget.container.observe('RuleWidget:Ready', rulewidget_update_value.bindAsEventListener(ruleWidget));
			ruleWidget.container.observe('RuleWidget:InColumn', rulewidget_update_value.bindAsEventListener(ruleWidget,true));
			ruleWidget.container.observe('RuleWidget:AddRule', rulewidget_update_value.bindAsEventListener(ruleWidget,false));
			ruleWidget.container.observe('RuleWidget:ChangeField', rulewidget_update_value.bindAsEventListener(ruleWidget));
			ruleWidget.container.observe('RuleWidget:DeleteRule', rulewidget_update_value.bindAsEventListener(ruleWidget, false, true));
			ruleWidget.container.observe('RuleWidget:RemoveAllRules', function() {
				this.clear_rules();
				rulewidget_update_value(null,false,true).bind(this);
			}.bindAsEventListener(ruleWidget));
			ruleWidget.startup($rulesJSON);
			</script>";
		return $html;
	}
}
?>
