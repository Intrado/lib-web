<?php

// TODO: Needs refactoring.
class FormSplitter extends Form {
	var $parentform;
	var $children;
	var $layout;
	var $title;
	var $subforms;

	function FormSplitter ($name, $title, $layout, $buttons, $children) {
		$this->layout = $layout;
		$this->children = $children;
		$this->title = $title;
		$this->formdata = array();
		$this->name = $name;
		$this->gatherSubforms();
		parent::Form($name, (!$this->parentform) ? $this->collectFormData() : array(), null, $buttons);
	}

	function gatherSubforms() {
		$this->subforms = array();
		
		if (!$this->name)
			return;
		
		// Subforms and FormSplitters are rendered first.
		foreach ($this->children as $child) {
			if (is_array($child)) {
				$subform = new SubForm($this, $child['title'], $child['formdata']);
				$this->subforms[] = $subform;
			} else if ($child instanceof FormSplitter) {
				$child->parentform = $this;
				$child->name = $this->name;
				$child->gatherSubforms();
				$this->subforms = array_merge($this->subforms, $child->subforms);
			}
		}
	}
	
	function collectFormData() {
		// Subforms and FormSplitters are rendered first.
		foreach ($this->subforms as $subform) {
			$this->formdata = array_merge($this->formdata, $subform->formdata);
		}
		return $this->formdata;
	}
	
	
	function getChild($name) {
		$subformIndex = 0;
		foreach ($this->children as $child) {
			if ($child instanceof FormSplitter || $child instanceof FormTabber) {
				if ($child->name == $name)
					return $child;
				if ($childform = $child->getChild($name))
					return $childform;
			} else if (is_array($child)) {
				if ($this->subforms[$subformIndex]->name == $name) {
					return $this->subforms[$subformIndex];
				}
				$subformIndex++;
			}
		}
		return null;
	}
	
	function handleRequest() {
		if (isset($_REQUEST['loadtab'])) {
			$form = $this->getChild(trim($_REQUEST['loadtab']));
			
			if (!$form) {
				$result = array();
			} else {
				// Exits with a json response containing the rendered tab.
				if ($form instanceof FormTabber || $form instanceof FormSplitter)
					$content = $form->render(null, false);
				else
					$content = $form->render();
				$result = array("element" => $form->name, "content" => $content);
			}
			
			header("Content-Type: application/json");
			echo json_encode($result);
			exit();
		}
		
		if (!isset($_REQUEST['form']) || $_REQUEST['form'] != $this->name) {
			foreach ($this->children as $child) {
				if ($child instanceof FormSplitter || $child instanceof FormTabber) {
					if ($child->name == $this->name)
						continue;
					$child->handleRequest(); // Will exit if appropriate.
				}
			}
		}
		
		parent::handleRequest(); // Will exit if appropriate.
	}

	function getSubmit() {
		if (!isset($_POST['submit']))
			return false;
		if ($_REQUEST['form'] == $this->name) {
			$this->submittedform = $this;
			return $_POST['submit'];
		}
		
		foreach ($this->children as $child) {
			if ($child instanceof FormTabber) {
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
	
	
	function render($specifictabs = null, $showtitle = true) {
		$classname = $this->layout;
		$posturl = $_SERVER['REQUEST_URI'];
		$posturl .= mb_strpos($posturl,"?") !== false ? "&" : "?";
		$posturl .= "form=". $this->name;
		
		$html = '';
		
		if (!$this->parentform && $this->name) {
			$html .= '<div id="'.$this->name.'"  style="padding:4px;" class="FormSwitcherLayoutSection">';
			if ($showtitle)
				$html .= '<span class="FormSwitcherLayoutSectionTitle">'.$this->title.'</span>';
			$html .= '<form class="newform '.$classname.' FormSwitcherLayoutSection" name="'.$this->name.'" method="POST" action="'.$posturl.'">';
			$html .= '<input name="'.$this->name.'-formsnum" type="hidden" value="' . $this->serialnum . '">';
		} else {
			$html .= '<div style="padding:4px; ; " id="'.$this->name.'" class="'.$classname.' FormSwitcherLayoutSection">';
			$html .= '<span class="FormSwitcherLayoutSectionTitle">'.$this->title.'</span>';
		}
		
		// Subforms and FormSplitters are rendered first.
		$subformIndex = 0;
		foreach ($this->children as $child) {
			if (is_array($child)) {
				$html .= '<div style="margin: 4px; padding-bottom: 50px;" class="FormSwitcherLayoutSection">';
				$html .= '<span class="FormSwitcherLayoutSectionTitle">'.$this->subforms[$subformIndex]->title.'</span>';
				$html .= $this->subforms[$subformIndex]->render();
				$html .= '</div>';
				$subformIndex++;
			} else if ($child instanceof FormSplitter) {
				$html .= $child->render($specifictabs);
			} else if (is_string($child)) {
				$html .= '<div style="padding:4px; ; " class="FormSwitcherLayoutSection">'.$child.'</div>';
			}
		}
		
		if (!$this->parentform && $this->name) {
			$this->collectFormData();
			
			$html .= '
				<script type="text/javascript">
					form_load("'.$this->name.'",
						"'.$posturl.'",
						'.json_encode($this->formdata).',
						'.json_encode($this->helpsteps).',
						'.($this->ajaxsubmit ? "true" : "false").'
					);
				</script>
			';
		}

		if (!$this->parentform && $this->name)
			$html .= '</form>';
		
		// Tabbers are not part of this splitter's FORM element.
		foreach ($this->children as $child) {
			if ($child instanceof FormTabber)
				$html .= $child->render($specifictabs);
		}
		
		foreach ($this->buttons as $code) {
			$html .= $code;
		}
		
		$html .= '<div style="clear: both;"></div></div>';
		
		return $html;
	}
}

?>