<?php


// NOTE: Putting this class in FormTabber.obj.php for now, but SubForm may get refactored out because it adds little to the base Form except a $parentform and $title.
class SubForm extends Form {
	var $parentform;
	var $title;
	
	function SubForm($parentform, $title, $formdata) {
		$this->parentform = $parentform;
		$this->title = $title;
		parent::Form($this->parentform->name, $formdata, null, array());
	}
}

// TODO: Perhaps FormTabber should not extend Form because FormTabber only contains child forms.
class FormTabber extends Form {
	var $children;
	var $layout;
	var $title;
	var $forms;

	function FormTabber ($name, $title, $layout, $children) {
		$this->layout = $layout;
		$this->children = $children;
		$this->title = $title;
		$this->forms = array();
		$this->gatherForms();
		
		parent::Form($name, array(), null, array());
	}

	function gatherForms() {
		//if (!$this->name)
		//	return array();
			
		$this->forms = array();
		
		foreach ($this->children as $child) {
			if (is_array($child)) {
				$form = new Form($child['name'], $child['formdata'], null, array());
				$form->title = $child['title'];
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
				$this->forms[$formIndex]->handleRequest();
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
	
	// $specifictabs is an array of child formnames to render; otherwise, only the first child gets rendered.
	function render($specifictabs = null, $showtitle = true) {
		$classname = $this->layout;
		$html = '';
		
		$html .= '<div style="padding:4px; ; " id="'.$this->name.'" class="'.$classname. ' FormSwitcherLayoutSection">';
		
		if ($showtitle) {
			$html .= '<span class="FormSwitcherLayoutSectionTitle">'.$this->title.'</span>';
		}
		
		$renderCount = 0;
		$formIndex = 0;
		foreach ($this->children as $child) {
			if (is_array($child)) {
				$form = $this->forms[$formIndex];
				
				$html .= '<div style="margin: 4px; padding-bottom: 50px;" id="'.$form->name.'" class="FormSwitcherLayoutSection">';
				$html .= '<span class="FormSwitcherLayoutSectionTitle">'.$form->title.'</span>';
				
				if ((!empty($specifictabs) && in_array($form->name, $specifictabs)) || ($renderCount == 0)) {
					$html .= $form->render();
				}
				
				$html .= '</div>';
				
				$formIndex++;
			} else {
				if ((!empty($specifictabs) && in_array($child->name, $specifictabs)) || ($renderCount == 0)) {
					$html .= $child->render($specifictabs);
				} else {
					$html .= '<div style="margin: 4px; padding-bottom: 50px;" id="'.$child->name.'" class="FormSwitcherLayoutSection">';
					$html .= '<span class="FormSwitcherLayoutSectionTitle">'.$child->title.'</span>';
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