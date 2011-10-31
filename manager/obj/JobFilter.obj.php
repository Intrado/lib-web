<?


class JobFilter {
	var $form;
	var $settings;
	function JobFilter($defaulttype) {
		
		// default settings
		$this->settings = array(
			"dispatchtype" => 'system',
			"priorities" => array(1,2,3),
			"jobstatus" => "active",
			"destinationtype" => $defaulttype
		);
		
		foreach($this->settings as $key => $values) {
			if (isset($_REQUEST[$key])) {
				$this->settings[$key] = $_REQUEST[$key];
			}
		}
		
		$helpstepnum = 1;
		$dispatchtypes = array('customer' => 'SmartCall','system' => 'ASP');
		$formdata["dispatchtype"] = array(
			"label" => _L('Dispatch Type'),
			"value" => $this->settings['dispatchtype'],
			"validators" => array(
				array("ValRequired"),
				array("ValInArray", "values" => array_keys($dispatchtypes))
			),
			"control" => array("SelectMenu", "values" => $dispatchtypes),
			"helpstep" => $helpstepnum
		);
		$prinames = array (1 => "Emergency", 2 => "High", 3 => "General");
		$formdata["priorities"] = array(
			"label" => _L('Priority'),
			"value" => $this->settings['priorities'],
			"validators" => array(
				array("ValRequired"),
				array("ValInArray", "values" => array_keys($prinames))
			),
			"control" => array("MultiCheckBox", "values" => $prinames),
			"helpstep" => $helpstepnum
		);
		
		$destinationtypes = array('phone' => 'Phone','sms' => 'SMS','email' => 'Email');
		$formdata["destinationtype"] = array(
			"label" => _L('Destination Type'),
			"value" => $this->settings['destinationtype'],
			"validators" => array(
				array("ValRequired"),
				array("ValInArray", "values" => array_keys($destinationtypes))
			),
			"control" => array("SelectMenu", "values" => $destinationtypes),
			"helpstep" => $helpstepnum
		);
		
		$buttons = array(submit_button(_L('Refresh'),"submit","arrow_refresh"));

		$this->form = new Form("activeemailjobs",$formdata,false,$buttons);
		
	}	

	function handleChages() {
		//check and handle an ajax request (will exit early)
		//or merge in related post data
		$this->form->handleRequest();
		
		$datachange = false;
		$errors = false;
		//check for form submission
		if ($button = $this->form->getSubmit()) {
			//checks for submit and merges in post data
			$ajax = $this->form->isAjaxSubmit(); //whether or not this requires an ajax response
		
			if ($this->form->checkForDataChange()) {
				$datachange = true;
			} else if (($errors = $this->form->validate()) === false) {
				//checks all of the items in this form
				$postdata = $this->form->getData(); //gets assoc array of all values {name:value,...}
				
				foreach($this->settings as $key => $values) {
					if (isset($postdata[$key])) {
						$this->settings[$key] = $postdata[$key];
					}
				}
				
				switch ($postdata['destinationtype']) {
					case 'email':
						$url = "customeractiveemailjobs.php?" .  http_build_query($this->settings);
						break;
					case 'sms':
						$url = "customeractivesmsjobs.php?" .  http_build_query($this->settings);
						break;
					case 'phone':
					default:
						$url = "customeractivejobs.php?" .  http_build_query($this->settings);
				}
				if ($ajax)
					$this->form->sendTo($url);
				else
					redirect($url);
			}
		}
	}

	function render() {
		startWindow(_L('Active Jobs Filter'));
		echo $this->form->render();
		endWindow();
	}

}
?>