<?

// TODO: Perhaps FormTabber should not extend Form because FormTabber only contains child forms.
class FormTabber extends Form {
	var $children;
	var $layout;
	var $title;
	var $forms;

	function FormTabber ($name, $title, $icon, $layout, $children) {
		$this->layout = $layout;
		$this->children = $children;
		$this->title = $title;
		$this->icon = $icon;
		$this->forms = array();
		$this->gatherForms();
		
		parent::Form($name, array(), null, array(), false);
	}

	function gatherForms() {
		//if (!$this->name)
		//	return array();
			
		$this->forms = array();
		
		foreach ($this->children as $child) {
			if (is_array($child)) {
				$form = new Form($child['name'], $child['formdata'], null, array());
				$form->title = $child['title'];
				if (isset($child['icon']))
					$form->icon = $child['icon'];
				$this->forms[] = $form;
			}
		}
	}
	
	function getChild($name) {
		$formIndex = 0;
		foreach ($this->children as $child) {
			if ($child instanceof FormSplitter || $child instanceof FormTabber) {
				if ($child->name == $name)
					return $child;
				else if ($childform = $child->getChild($name))
					return $childform;
			} else if (is_array($child)) {
				if ($this->forms[$formIndex]->name == $name)
					return $this->forms[$formIndex];
				$formIndex++;
			}
		}
		return null;
	}
	
	function handleRequest() {
		$formIndex = 0;
		foreach ($this->children as $child) {
			if ($child instanceof FormSplitter || $child instanceof FormTabber) {
				$child->handleRequest(); // Will exit if appropriate.
			} else if (is_array($child)) {
				$this->forms[$formIndex]->handleRequest(); // Will exit if appropriate.
				$formIndex++;
			}
		}
	}

	function getSubmit() {
		$formIndex = 0;
		foreach ($this->children as $child) {
			if (is_array($child)) {
				if ($button = $this->forms[$formIndex]->getSubmit()) {
					$this->submittedform = $this->forms[$formIndex];
					return $button;
				}
				$formIndex++;
			} else {
				if ($button = $child->getSubmit()) {
					$this->submittedform = $child->getSubmittedForm();
					return $button;
				}
			}
		}
		return false;
	}
	
	function getSubmittedForm() {
		if (isset($this->submittedform))
			return $this->submittedform;
		else
			return null;
	}
	
	function renderJavascriptLibraries() {
		$html = '';
		
		$formIndex = 0;
		foreach ($this->children as $child) {
			if (is_array($child)) {
				$form = $this->forms[$formIndex];
				$html .= $form->renderJavascriptLibraries();
				$formIndex++;
			} else if ($child instanceof FormSplitter || $child instanceof FormTabber) {
				$html .= $child->renderJavascriptLibraries();
			}
		}
		return $html;
	}
	
	// $specificsection is an array of child formnames to render; otherwise, only the first child gets rendered.
	function render($specificsections = null, $showtitle = true, $ignorelibraries = true) {
		$classname = $this->layout;
		$html = '';
		
		if (!$ignorelibraries)
			$html .= $this->renderJavascriptLibraries();
			
		$html .= '<div style="padding:4px; ; " id="'.$this->name.'" class="'.$classname. ' FormSwitcherLayoutSection">';
		
		if ($showtitle) {
			$html .= '<span class="FormSwitcherLayoutSectionTitle">'.$this->title.'</span>';
			if ($this->icon)
				$html .= '<img class="FormSwitcherLayoutSectionIcon" id="'.$this->name.'icon" src="'.$this->icon.'"/>';
		}
		
		// Determine if any of the children is specified in $specificsections.
		if (!empty($specificsections)) {
			$specificsection = '';
			$formIndex = 0;
			foreach ($this->children as $child) {
				if (is_array($child)) {
					$form = $this->forms[$formIndex];
					if (in_array($form->name, $specificsections))
						$specificsection = $form->name;
					$formIndex++;
				} else if (in_array($child->name, $specificsections)) {
					$specificsection = $child->name;
				}
			}
		} else {
		}
		
		$renderCount = 0;
		$formIndex = 0;
		foreach ($this->children as $child) {
			if (is_array($child)) {
				$form = $this->forms[$formIndex];
				
				$html .= '<div style="margin: 4px; padding-bottom: 50px;" id="'.$form->name.'" class="FormSwitcherLayoutSection">';
				$html .= '<span class="FormSwitcherLayoutSectionTitle">'.$form->title.'</span>';
				if (isset($form->icon))
					$html .= '<img class="FormSwitcherLayoutSectionIcon" id="'.$form->name.'icon" src="'.$form->icon.'"/>';
				
				$specific = !empty($specificsection) && $specificsection == $form->name;
				if ($specific || (empty($specificsection) && $renderCount == 0)) {
					$html .= $form->render(true);
				}
				
				$html .= '</div>';
				
				$formIndex++;
			} else {
				$specific = !empty($specificsection) && $specificsection == $child->name;
				if ($specific || (empty($specificsection) && $renderCount == 0)) {
					if ($child instanceof FormSplitter)
						$html .= $child->render($specificsections, true, true);
					else
						$html .= $child->render($specificsections);
				} else {
					$html .= '<div style="margin: 4px; padding-bottom: 50px;" id="'.$child->name.'" class="FormSwitcherLayoutSection">';
					$html .= '<span class="FormSwitcherLayoutSectionTitle">'.$child->title.'</span>';
					if ($child->icon)
						$html .= '<img class="FormSwitcherLayoutSectionIcon" id="'.$child->name.'icon" src="'.$child->icon.'"/>';
					$html .= '</div>';
				}
			}
			
			$renderCount++;
		}
		
		$html .= '<div style="clear: both;"></div></div>';
		return $html;
	}
}

?>