<?php


// NOTE: Putting this class in FormSplitter for now, but SubForm may get refactored out because it adds little to the base Form except a $parentform and $title.
class SubForm extends Form {
	var $parentform;
	var $title;
	
	function SubForm($parentform, $title, $icon, $formdata) {
		$this->parentform = $parentform;
		$this->title = $title;
		$this->icon = $icon;
		parent::Form($this->parentform->name, $formdata, null, array());
	}
}

class FormSplitter extends Form {
	var $parentform;
	var $children;
	var $layout;
	var $title;
	var $subforms;

	function FormSplitter ($name, $title, $icon, $layout, $buttons, $children) {
		$this->layout = $layout;
		$this->children = $children;
		$this->title = $title;
		$this->icon = $icon;
		$this->formdata = array();
		$this->name = $name;
		$this->gatherSubforms();
		
		parent::Form($name, $this->collectFormData(), null, $buttons);
	}

	function gatherSubforms() {
		$this->subforms = array();
		
		// Subforms and FormSplitters are rendered first.
		foreach ($this->children as $child) {
			if (is_array($child)) {
				$this->subforms[] = new SubForm($this, $child['title'], isset($child['icon']) ? $child['icon'] : null, $child['formdata']);
			} else if ($child instanceof FormSplitter) {
				$child->parentform = $this;
				$child->name = $this->name;
				$this->subforms = array_merge($this->subforms, $child->gatherSubforms());
			}
		}
		
		return $this->subforms;
	}
	
	function collectFormData() {
		$this->formdata = array();
		
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
		if (isset($_REQUEST['formsnum'])) {
			$form = $_REQUEST['formsnum'] == $this->name ? $this : $this->getChild(trim($_REQUEST['formsnum']));
			
			if (!$form) {
				$result = array();
			} else {
				$result = array("formsnum" => $form->serialnum);
			}
			
			header("Content-Type: application/json");
			echo json_encode($result);
			
			exit();
		} else if (isset($_REQUEST['loadtab'])) {
			$form = $_REQUEST['loadtab'] == $this->name ? $this : $this->getChild(trim($_REQUEST['loadtab']));
			
			if (!$form) {
				$result = array();
			} else {
				// Exits with a json response containing the rendered tab.
				if ($form instanceof FormTabber || $form instanceof FormSplitter) {
					// $_REQUEST['specificsections'] must be a json-encoded array of form names, otherwise no specific tab will be specified.
					$specificsections = isset($_REQUEST['specificsections']) ? json_decode($_REQUEST['specificsections']) : null;
					$content = $form->render(is_array($specificsections) ? $specificsections : null, false, true);
				} else {
					$content = $form->render();
				}
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
	
	function renderJavascriptLibraries() {
		$html = parent::renderJavascriptLibraries();
		
		foreach ($this->children as $child) {
			if ($child instanceof FormSplitter || $child instanceof FormTabber) {
				if ($child->name != $this->name || $child->name == '' || $this->name == '') {
					$html .= $child->renderJavascriptLibraries();
				}
			}
		}
		return $html;
	}
	
	function render($specificsections = null, $showtitle = true, $ignorelibraries = false) {
		$classname = $this->layout;
		$posturl = $_SERVER['REQUEST_URI'];
		$posturl .= mb_strpos($posturl,"?") !== false ? "&" : "?";
		$posturl .= "form=". $this->name;
		
		$html = '';
		
		if (!$this->parentform && $this->name) {
			if (!$ignorelibraries) {
				$html .= $this->renderJavascriptLibraries();
			}
		
			$html .= '<div style="padding:2px;" class="FormSwitcherLayoutSection FormSplitterParentFormContainer">';
			if ($showtitle) {
				$html .= '<span class="FormSwitcherLayoutSectionTitle">'.$this->title.'</span>';
				if ($this->icon)
					$html .= '<img class="FormSwitcherLayoutSectionIcon" id="'.$this->name.'icon" src="'.$this->icon.'"/>';
			}
			$html .= '<form id="'.$this->name.'" class="newform FormSplitterParentForm '.$classname.' FormSwitcherLayoutSection" name="'.$this->name.'" method="POST" action="'.$posturl.'">';
			$html .= '<input name="'.$this->name.'-formsnum" type="hidden" value="' . $this->serialnum . '">';
		} else {
			$html .= '<div style="padding:2px; ; " class="'.$classname.' FormSwitcherLayoutSection">';
			$html .= '<span class="FormSwitcherLayoutSectionTitle">'.$this->title.'</span>';
			if ($this->icon)
					$html .= '<img class="FormSwitcherLayoutSectionIcon" src="'.$this->icon.'"/>';
		}
		
		// Subforms and FormSplitters are rendered first.
		$subformIndex = 0;
		foreach ($this->children as $child) {
			if (is_array($child)) {
				$html .= '<div style="margin: 2px; padding-bottom: 20px;" class="FormSwitcherLayoutSection">';
				$html .= '<span class="FormSwitcherLayoutSectionTitle">'.$this->subforms[$subformIndex]->title.'</span>';
				if ($this->subforms[$subformIndex]->icon)
					$html .= '<img class="FormSwitcherLayoutSectionIcon" src="'.$this->subforms[$subformIndex]->icon.'"/>';
				$html .= $this->subforms[$subformIndex]->render(true);
				$html .= '</div>';
				$subformIndex++;
			} else if ($child instanceof FormSplitter) {
				$html .= $child->render($specificsections, true, true);
			} else if (is_string($child)) {
				$html .= '<div style="padding:2px; ; " class="FormSwitcherLayoutSection">'.$child.'</div>';
			}
		}
		
		if (!$this->parentform && $this->name)
			$html .= '</form>';
		
		// Tabbers are not part of this splitter's FORM element.
		foreach ($this->children as $child) {
			if ($child instanceof FormTabber)
				$html .= $child->render($specificsections);
		}
		
		foreach ($this->buttons as $code) {
			$html .= $code;
		}
		
		$html .= '<div style="clear: both;"></div></div>';
		
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
		
		$html .= parent::renderJavascript();
		
		return $html;
	}
}

?>