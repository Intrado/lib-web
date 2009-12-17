<?php

abstract class SwitchableForm extends Form {
	// Returns boolean; true if the user has the correct privileges.
	abstract function authorized();

	// Returns boolean; true if the form is saved.
	// This function does not need to call $this->validate(); the FormSwitcher does that.
	abstract function save();
}

class FormSwitcher {
	var $name;

	// A reference to the current form, changes as the user clicks on a different tab or accordion.
	var $currentform;

	// Contains the layout FormSwitcherLayoutSection along with forms.
	// The forms must extend SwitchableForm.
	var $formstructure;

	// An associative array of references to switchable forms, collected from $this->formstructure in $this->collectForms().
	// The forms must extend SwitchableForm.
	var $forms; // $this->forms[$formname]

	// An associative array of references to switchable forms, collected from $this->formstructure in $this->collectForms().
	// The forms must extend SwitchableForm.
	var $parentformnames; // $parentform = $this->parentformnames[$childformname]

	// NOTE: Forms within $formstructure must extend SwitchableForm.
	// NOTE: Forms within $formstructure must have predefined unique names.
	function FormSwitcher ($name, $formstructure) {
		$this->formstructure = $formstructure;
		$this->name = $name;

		$this->collectForms($this->formstructure);
	}

	// This function recursively adds forms from $formstructure into $this->forms and $this->parentformnames.
	function collectForms($formstructure) {
		if (isset($formstructure['_form']) && $formstructure['_form'] instanceof SwitchableForm) {
			$this->forms[$formstructure['_form']->name] = $formstructure['_form'];

			if (!empty($formstructure['_parentformname']))
				$this->parentformnames[$formstructure['_form']->name] = $formstructure['_parentformname'];
		}

		foreach ($formstructure as $key => $value) {
			if (is_array($value))
				$this->collectForms($value);
		}
	}

	function getParentForm($childformname) {
		$parentname = isset($this->parentformnames[$childformname]) ? $this->parentformnames[$childformname] : null;
		return (!is_null($parentname) && isset($this->forms[$parentname])) ? $this->forms[$parentname] : null;
	}

	function handleRequest() {
	}

	function validate() {
		$errors = $this->_validate($this->currentform);
	}

	// Returns an associative array of error messages, indexed by each form's name; example: $errorsmap[$currentform->name] = $currentform->validate().
	// This function recurses along $currentform's treebranch, starting with the current form, working its way up to the top-most parent form.
	// Each form's validate() method is invoked.
	function _validate($currentform) {
		$errorsmap = array($currentform->name => $currentform->validate());

		if ($this->getParentForm($currentform->name))
			$errorsmap = array_merge($errorsmap, $this->_validate($this->getParentForm($currentform->name)));

		return $errorsmap;
	}

	function save() {
		$this->_save($this->currentform);
	}

	// This function recurses along $currentform's treebranch, starting with the top-most parent, working its way back down to $currentform.
	// However, if any parent form fails to save(), then the rest of the forms within this branch are skipped.
	function _save($currentform) {
		// FAILURE.
		if ($this->getParentForm($currentform->name) && !$this->_save($this->getParentForm($currentform->name))) {

			return false;

		// FAILURE.
		} else if (($errors = $currentform->validate()) || !$currentform->save()) {

			if ($errors) {
				// TODO: handle error messages.
			}

			return false;

		// SUCCESS.
		} else {

			return true;

		}
	}

	function render() {
		$html = "<div id='{$this->name}'>" . $this->renderFormStructure($this->formstructure, null, '') . "</div>";
		$html .= "<script type='text/javascript'>
			(function() {
				var formswitcherid = '{$this->name}';

				var load_layout = function(formswitcherid, classname) {
					$$('#' + formswitcherid + ' .' + classname).each(function(container, classname) {
						var kids = container.childElements();

						var layoutsections = kids.findAll(function(kid) {
							return kid.match('.FormSwitcherLayoutSection');
						});

						var layout;
						if (classname == 'horizontaltabs' || classname == 'verticaltabs' || classname == 'accordion') {
							if (classname == 'horizontaltabs')
								layout = new Tabs(container, {'vertical':false, 'showDuration':0, 'hideDuration':0});
							else if (classname == 'verticaltabs')
								layout = new Tabs(container, {'vertical':true, 'showDuration':0, 'hideDuration':0});
							else if (classname == 'accordion')
								layout = new Accordion(container);

							layoutsections.each(function (layoutsection) {
								this.add_section(layoutsection.identify());
								this.update_section(layoutsection.identify(), {
									'title': layoutsection.down('span.FormSwitcherLayoutSectionTitle'),
									'icon': 'img/icons/diagona/16/160.gif',
									'content': layoutsection
								});

								if (!this.firstSection)
									this.firstSection = layoutsection.identify();

							}.bindAsEventListener(layout));

							layout.show_section(layout.firstSection);

						} else if (classname == 'verticalsplit') {
							var split = make_split_pane(true, layoutsections.length);

							for (var i = 0; i < layoutsections.length; i++) {
								split.down('.SplitPane', i).insert(layoutsections[i]);
							}

							container.insert(split);
						}

					}.bindAsEventListener(this, classname));
				};

				load_layout(formswitcherid, 'horizontaltabs');
				load_layout(formswitcherid, 'verticaltabs');
				load_layout(formswitcherid, 'accordion');
				load_layout(formswitcherid, 'verticalsplit');
			})();
		</script>";

		return $html;
	}

	function renderFormStructure($formstructure, $callbacks, $fullkey) {
		$html = "";

		if (!empty($formstructure['_layout'])) {
			$layout = $formstructure['_layout'];
			$html .= "<div id='$fullkey' class='$layout FormSwitcherLayoutSection' style='padding:4px'>";
		} else {
			$html .= "<div id='$fullkey' class='FormSwitcherLayoutSection' style='padding:4px'>";
		}

		if (!empty($formstructure['_title'])) {
			$title = $formstructure['_title'];
			$html .= "<span class='FormSwitcherLayoutSectionTitle'>$title</span>";
		}

		foreach ($formstructure as $key => $value) {
			if (is_array($value)) {

				$html .= $this->renderFormStructure($value, $callbacks, $fullkey . $key);

			} else if ($key == '_form' && $value instanceof SwitchableForm) {

				$html .= $value->render();

			} else if ($key[0] == '_') {
				continue;
			} else { // Arbitrary HTML.

				$html .= "<div id='{$fullkey}{$key}' class='FormSwitcherLayoutSection' style='padding:4px'>$value</div>";

			}
		}

		return $html . "</div>";
	}
}

?>