<? 

class PreviewButton extends FormItem {
	function render ($value) {
		$n = $this->form->name."_".$this->name;
		
		// Check if any of these parameters are set
		// there are two permutation 
		// - x and xtarget
		$parameters = array("id","language","gender","text","fromname","from","subject");
		$str = '<input id="'.$n.'" name="'.$n.'" type="hidden" value=""/>';
		$str .= icon_button("Preview", "fugue/control","
					var form = event.findElement('form');
					var parameters = " . $this->formatParameters($parameters) . ";
					showPreview(parameters);return false;");
		$str .= '<br /><br />';
		return $str;
	}
	
	function formatParameters($perameters) {
		$jsonparameters = array();
		
		// For each parameter, check If static value is set 
		// then check if it has a target. if target is set,
		// then use either the default form getter or callback if defined.
		foreach($perameters as $parameter) {
			if (!isset($this->args[$parameter])) {
				if (isset($this->args[$parameter . 'target']) ) {
					if (isset($this->args['previewjscallback']))	
						$jsonparameters[] = "$parameter:$this->args['previewjscallback'](form,'{$this->args[$parameter . 'target']}')";
					else
						$jsonparameters[] = "$parameter:form_get_value(form, form.name + '_{$this->args[$parameter . 'target']}')";
				}
			} else {
				$jsonparameters[] = "$parameter:'{$this->args[$parameter]}'";
			}
		}
		return "{" . implode(",",$jsonparameters) . "}";
	}
}

?>