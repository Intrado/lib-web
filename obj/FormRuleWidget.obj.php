<?
class FormRuleWidget extends FormItem {
	function render ($valueJSON) {
		$inputname = $this->form->name."_".$this->name;
		if (!$valueJSON || !is_array(json_decode($valueJSON)))
			$valueJSON = '[]';//'[{fieldnum:"f01", type:"text", logical:"and", op:"eq", val:"Kee-Yip"}, {fieldnum:"f02", type:"text", logical:"and", op:"eq", val:"Chan"}]';
		$html = '<input id="'.$inputname.'" name="'.$inputname.'" type="hidden" value="'.escapehtml($valueJSON).'" />';
		// #ruleWidgetContainer
		$html .= '<div id="ruleWidgetContainer"></div>';
		$html .= '<script type="text/javascript" src="script/rulewidget.js.php"></script>';
		$html .= '<script type="text/javascript" src="script/calendar.js"></script>';
		// custom javascript
		$html .= "<script type='text/javascript'>
			var ruleWidget = new RuleWidget($('ruleWidgetContainer'".(!empty($this->args['readonly']) ? ',true' : '')."));
			function rulewidget_update_value() {
				$('$inputname').value = ruleWidget.toJSON();
				form_do_validation($('".$this->form->name."'), $('".$inputname."'));
			}
			ruleWidget.container.observe('RuleWidget::Ready', rulewidget_update_value);
			ruleWidget.container.observe('RuleWidget:AddRule', rulewidget_update_value);
			ruleWidget.container.observe('RuleWidget:DeleteRule', rulewidget_update_value);
			ruleWidget.startup($valueJSON);
			</script>";
		return $html;
	}
}

class ValRules extends Validator {
	function validate ($valueJSON, $args) {
		$ruledata = json_decode($valueJSON);
		if (!is_array($ruledata) || empty($ruledata))
			return true; // Do not complain if no rules are specified

		$rulesfor = array();
		foreach ($ruledata as $data) {
			if (!isset($data->fieldnum, $data->logical, $data->op, $data->val))
				return _L('Incomplete rule data');
			if (isset($rulesfor[$data->fieldnum])) // Do not allow more than one rule per fieldnum
				return _L('There is already a rule for ' . $data->fieldnum);
			if (!Rule::initFrom($data->fieldnum, $data->logical, $data->op, $data->val))
				return _L('Failed to create the rule for ' . $data->fieldnum);
			$rulesfor[$data->fieldnum] = true;
		}
		return true;
	}
	
	function getJSValidator () {
		return 
			'function (name, label, value, args) {
				var ruledata = value.evalJSON();
				if (!ruledata.join)
					return true; // Do not complain if no rules are specified
				for (var i = 0; i < ruledata.length; ++i) {
					if (!ruledata[i].fieldnum || !ruledata[i].type || !ruledata[i].logical || !ruledata[i].op || !ruledata[i].val)
						return "'.addslashes(_L("Incomplete rule data")).'";
				}
				return true;
			}';
	}
}
?>
