<?php

class Form {
	var $name;

	function Form($name, $data) {
		$this->name = $name;
	}

	function render() {
		return "<form id='{$this->name}' style='margin:0; padding: 10px; border: solid 2px blue'></form>";
	}
}

class FormSwitcher {
	var $name;
	var $formstructure;

	var $currentform;

	function FormSwitcher ($name, $formstructure) {

		$this->formstructure = $formstructure;
		$this->name = $name;

	}

	function handleRequest() {
	}

	function _render($formstructure, $callbacks, $fullkey) {
		$html = "";

		if (!empty($formstructure['_layout'])) {
			$layout = $formstructure['_layout'];
			$html .= "<div id='$fullkey' class='$layout Structure' style='border: solid 1px rgb(130,230,255); padding: 20px'>$fullkey<br/>";
		} else {
			$html .= "<div id='$fullkey' class='Structure' style='border: solid 1px rgb(230,230,230); padding: 20px'>$fullkey<br/>";
		}

		if (!empty($formstructure['_title'])) {
			$title = $formstructure['_title'];
			$html .= "<span class='Title'>$title</span>";
		}

		foreach ($formstructure as $key => $value) {
			if ($key[0] == '_')
				continue;

			if (is_array($value)) {

				$html .= $this->_render($value, $callbacks, $fullkey . $key);

			} else if ($value instanceof Form) {

				$html .= $value->render();

			}
		}

		return $html . "</div>";
	}

	function render() {
		$html = "<div id='{$this->name}'>" . $this->_render($this->formstructure, null, '') . "</div>";
		$html .= "<script type='text/javascript'>
			(function() {
				var formswitcherID = '{$this->name}';

				var make_tabs = function(formswitcherID, vertical) {
					$$('#'+formswitcherID + (vertical ? ' .verticaltabs' : ' .horizontaltabs')).each(function(container, vertical) {
						var kids = container.childElements();
						var tabs = new Tabs(container, {'vertical':vertical});
						kids.each(function (kid) {
							if (!kid.match('.Structure'))
								return;

							this.add_section(kid.identify());
							this.update_section(kid.identify(), {
								'title': kid.down('span.Title'),
								'icon': 'img/icons/diagona/16/160.gif',
								'content': kid
							});

							if (!this.firstSection)
								this.firstSection = kid.identify();
						}.bindAsEventListener(tabs, vertical));
						tabs.show_section(tabs.firstSection);
					}.bindAsEventListener(this, vertical));
				};

				make_tabs(formswitcherID, false);
				make_tabs(formswitcherID, true);
			})();
		</script>";

		return $html;
	}
};

?>