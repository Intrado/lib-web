<?
class FormRuleWidget extends FormItem {
	function render ($rulesJSON) {
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
			var ruleWidget = new RuleWidget($('ruleWidgetContainer'".(!empty($this->args['readonly']) ? ',true' : '')."));
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
			ruleWidget.startup($rulesJSON);
			</script>";
		return $html;
	}
}

class ValRules extends Validator {
	function validate ($valueJSON, $args) {
		$msgPleaseFinish = addslashes(_L("Please finish adding your rule"));
		$msgIncompleteRule = addslashes(_L("Incomplete rule data"));
		$msgRuleAlreadyExists = addslashes(_L("Rule already exists"));
		$msgUnauthorizedFieldmap = addslashes(_L("Unauthorized fieldmap"));
	
		if ($valueJSON == 'pending')
			return $msgPleaseFinish;
		
		$ruledata = json_decode($valueJSON);
		if (!is_array($ruledata) || empty($ruledata)) // Do not complain if no rules are specified
			return true;

		$rulesfor = array();
		foreach ($ruledata as $data) {
			if (!isset($data->fieldnum, $data->logical, $data->op, $data->val))
				return $msgIncompleteRule;
			if (isset($rulesfor[$data->fieldnum])) // Do not allow more than one rule per fieldnum
				return $msgRuleAlreadyExists;
			if (!Rule::initFrom($data->fieldnum, $data->logical, $data->op, $data->val))
				return $msgUnauthorizedFieldmap;
			$rulesfor[$data->fieldnum] = true;
		}
		return true;
	}
	
	function getJSValidator () {
		$msgPleaseFinish = addslashes(_L("Please finish adding your rule"));
		$msgIncompleteRule = addslashes(_L("Incomplete rule data"));
		$msgRuleAlreadyExists = addslashes(_L("Rule already exists"));
		$msgUnauthorizedFieldmap = addslashes(_L("Unauthorized fieldmap"));
	
		return "
			function (name, label, value, args) {
				if (value == 'pending')
					return '$msgPleaseFinish';
					
				var ruledata = value.evalJSON();
				if (!ruledata || !ruledata.join)
					return true; // Do not complain if no rules are specified
					
				for (var i = 0; i < ruledata.length; ++i) {
					if (!ruledata[i].fieldnum || !ruledata[i].type || !ruledata[i].logical || !ruledata[i].op)
						return '$msgIncompleteRule';
				}
				return true;
			}
		";
	}
}
?>
