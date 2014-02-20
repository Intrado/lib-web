<?
/**
 * Create a multi checkbox table with additional html fields
 *
 * args:
 * 	headers => array(<string>) is a list of column headers The strings are treated as html and
 * 		will not be escaped
 * 	columns => array(<string>,array(<string>)) is a map of actual value to a list of
 * 		additional column data to display for that value. The keys are treated as strings and
 * 		will be escaped. The value list strings are treated as html and will not be escaped
 * 	hovers (optional) => array(<string>,<string>) is a map of actual value to hover help stringk
 * 	hovercolumns (optional) => array(int) is a list of columns to apply the hover description (indexed starting with 0)
 * 	cssclass (optional) => string is an additional css class name to apply to the table
 *
 * User: nrheckman
 * Date: 2/12/14
 * Time: 12:48 PM
 */

class MultiCheckBoxTable extends FormItem {
	var $clearonsubmit = true;
	var $clearvalue = array();
	var $headers;
	var $columns;
	var $hovers;
	var $hoverColumns;
	var $cssClass;

	/**
	 * @param Form $form the parent form
	 * @param string $name the name of the form item
	 * @param array $args additional arguments required to initialize this form item
	 */
	function __construct($form, $name, $args) {
		parent::__construct($form, $name, $args);

		$this->headers = isset($args['headers'])? $args['headers']: false;
		$this->columns = isset($args['columns'])? $args['columns']: false;
		$this->hovers = isset($args['hovers'])? $args['hovers']: false;
		$this->hoverColumns = isset($args['hovercolumns'])? $args['hovercolumns']: array();
		$this->cssClass = isset($args['cssclass'])? $args['cssclass']: false;
	}

	function render ($value) {
		// validate that the data available to render this item is valid
		if (!$this->headers || !$this->columns) {
			error_log_helper("Unable to render, invalid data. headers = ". print_r($this->headers, true). ", columns = ". print_r($this->columns, true));
			return "";
		}

		$formItemName = $this->form->name."_".$this->name;

		// create the table and fill in the column headers
		// add an additional class, if provided. Note the addition of a space here, and not below
		$additionalClass = ($this->cssClass)? $additionalClass = " $this->cssClass": "";
		$str = "<table id='$formItemName' class='multicheckbox$additionalClass'>
					<thead><tr>";
		foreach ($this->headers as $header)
			$str .= '<th class="header">'. $header. '</th>';
		$str .= '</tr></thead><tbody>';

		// add all the data columns. The first one is the checkbox.
		$hoverdata = array();
		$counter = 1;
		foreach ($this->columns as $checkvalue => $columns) {
			// if the checkbox value is not false, create an input and check it if the value indicates it should be so
			// then append it as the first column
			if ($checkvalue != false) {
				$checked = $value == $checkvalue || (is_array($value) && in_array($checkvalue, $value));
				$str .= '<tr class="hover">';
				$firstColumn = '<input type="checkbox" value="'.escapehtml($checkvalue).'" '.($checked ? 'checked' : '').' name="'.$formItemName.'[]" />';
				array_unshift($columns, $firstColumn);
			} else {
				// otherwise, it's a break in the table and the columns should be treated as headers with the first column empty
				$str .= '<tr class="header">';
				array_unshift($columns, "");
			}

			$columnCounter = 0;
			foreach ($columns as $column) {
				$hoverId = $formItemName.'-'.$counter++;
				// avoid setting onClick behavior on the checkbox column
				$onClick = "";
				if ($columnCounter != 0)
					$onClick = "multiCheckBoxTableRowClick(this);";
				$str .= '<td id="'.$hoverId.'" onclick="'.$onClick.'">'.$column.'</td>';

				if (isset($this->hovers[$checkvalue]) && $this->hovers[$checkvalue]) {
					if (in_array($columnCounter, $this->hoverColumns)) {
						$hoverdata[$hoverId] = $this->hovers[$checkvalue];
					}
				}
				$columnCounter++;
			}
			$str .= '</tr>';
		}
		$str .= '</tbody></table>';

		if ($hoverdata)
			$str .= '<script type="text/javascript">form_do_hover(' . json_encode($hoverdata) .');</script>';

		return $str;
	}

	function renderJavascriptLibraries() {
		return '<script type="text/javascript">
			(function($) {
				document.multiCheckBoxTableRowClick = function(element) {
					var checkbox = $(element).parent().find("input").first();
					if (checkbox.attr("checked"))
						checkbox.removeAttr("checked");
					else
						checkbox.attr("checked", "checked");
					var form = checkbox.closest("form");
					form_do_validation(form[0], checkbox[0])
				}
			})(jQuery);
		</script>';
	}

	function renderJavascript($value) {
		$formItemName = $this->form->name."_".$this->name;
		// attach the form event handler to all the checkboxes so validation can occur on user interaction
		return '
			var inputs = document.getElementsByName("'.$formItemName.'[]");
			for (var i = 0; i < inputs.length; i++) {
				inputs[i].observe("click",form_event_handler);
				inputs[i].observe("blur",form_event_handler);
				inputs[i].observe("change",form_event_handler);
			};
		';
	}
}
?>