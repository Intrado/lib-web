<?php

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
			$html .= "<div id='$fullkey' class='$layout Structure' style='padding:4px'>";
		} else {
			$html .= "<div id='$fullkey' class='Structure' style='padding:4px'>";
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

			} else { // Arbitrary HTML.

				$html .= "<div id='{$fullkey}{$key}' class='Structure' style='padding:4px'>$value</div>";

			}
		}

		return $html . "</div>";
	}

	function render() {
		$html = "<div id='{$this->name}'>" . $this->_render($this->formstructure, null, '') . "</div>";
		$html .= "<script type='text/javascript'>
			(function() {
				var formswitcherid = '{$this->name}';

				var load_layout = function(formswitcherid, classname) {
					$$('#' + formswitcherid + ' .' + classname).each(function(container, classname) {
						var kids = container.childElements();

						var structures = kids.findAll(function(kid) {
							return kid.match('.Structure');
						});

						var layout;
						if (classname == 'horizontaltabs' || classname == 'verticaltabs' || classname == 'accordion') {
							if (classname == 'horizontaltabs')
								layout = new Tabs(container, {'vertical':false, 'showDuration':0, 'hideDuration':0});
							else if (classname == 'verticaltabs')
								layout = new Tabs(container, {'vertical':true, 'showDuration':0, 'hideDuration':0});
							else if (classname == 'accordion')
								layout = new Accordion(container);

							structures.each(function (structure) {
								this.add_section(structure.identify());
								this.update_section(structure.identify(), {
									'title': structure.down('span.Title'),
									'icon': 'img/icons/diagona/16/160.gif',
									'content': structure
								});

								if (!this.firstSection)
									this.firstSection = structure.identify();

							}.bindAsEventListener(layout));

							layout.show_section(layout.firstSection);

						} else if (classname == 'verticalsplit') {
							var split = make_split_pane(true, structures.length);

							for (var i = 0; i < structures.length; i++) {
								split.down('.SplitPane', i).insert(structures[i]);
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
};

?>