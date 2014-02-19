<?php
/**
 * Unit test class for the MultiCheckBoxTable form item
 *
 * User: nrheckman
 * Date: 2/14/14
 * Time: 9:42 AM
 */

require_once(realpath(dirname(dirname(__FILE__)) .'/konaenv.php'));
require_once(realpath(dirname(dirname(__FILE__)) .'/PhpStub.php'));
// ----------------------------------------------------------------------------
require_once("{$konadir}/inc/utils.inc.php");
require_once("{$konadir}/obj/Form.obj.php");
require_once("{$konadir}/obj/FormItem.obj.php");
require_once("{$konadir}/obj/MultiCheckBoxTable.fi.php");

class MultiCheckBoxTableTest extends PHPUnit_Framework_TestCase {
	var $form = null;

	// before each test
	public function setUp() {
		$this->form = new Form("testform", array(), array(), array());
	}

	public function test_AllValidOptions() {
		$checkboxValues = array("1", "2");
		$firstCheckboxValue = $checkboxValues[0];
		$secondCheckboxValue = $checkboxValues[1];
		$args = array(
			"headers" => array("box", "value_name", "more info"),
			"columns" => array(),
			"hovers" => array($firstCheckboxValue => "this is the hover text"),
			"hovercolumns" => array(1,2)
		);
		foreach ($checkboxValues as $value)
			$args['columns'][$value] = array("value $value label", "additional column");

		$formItem = new MultiCheckBoxTable($this->form, "testitem", $args);
		$renderedData = $formItem->render(array($firstCheckboxValue));

		// be sure that somethign was rendered
		$this->assertNotEmpty($renderedData, 'missing headers should cause no rendering to take place');

		// check that the headers were added
		foreach ($args['headers'] as $header)
			$this->assertTrue((strpos($renderedData, $header) !== false), "Header '$header' was not added!");

		// check that all checkboxes were added
		foreach ($checkboxValues as $value)
			$this->assertTrue((strpos($renderedData, '<input type="checkbox" value="'.escapehtml($value).'"') !== false),
				"Checkbox with value '$value' was not rendered into the form item");

		// check that the first checkbox was added and is checked
		$this->assertTrue((strpos($renderedData, '<input type="checkbox" value="'.escapehtml($firstCheckboxValue).'" checked') !== false),
			"Checkbox '$firstCheckboxValue' was not set to checked");

		// check that the second checkbox was added and is not checked
		$this->assertFalse(strpos($renderedData, '<input type="checkbox" value="'.escapehtml($secondCheckboxValue).'" checked'),
			"Checkbox '$secondCheckboxValue' was set to checked");

		// check that the columns were added
		foreach ($args['columns'][$firstCheckboxValue] as $column)
			$this->assertTrue((strpos($renderedData, $column) !== false), "Column '$column' was not added");

		// check that the hovers were added
		$this->assertTrue((strpos($renderedData, '<script type="text/javascript">form_do_hover') !== false),
			"Hover initialization script was not added");
	}

	public function test_NoHovers() {
		$checkboxValue = "1";
		$args = array(
			"headers" => array("box", "value_name", "more info"),
			"columns" => array($checkboxValue => array("value 1 label", "additional column"))
		);

		$formItem = new MultiCheckBoxTable($this->form, "testitem", $args);
		$renderedData = $formItem->render("");

		// check that no hovers were added
		$this->assertFalse(strpos($renderedData, '<script type="text/javascript">form_do_hover'),
			"Hover initialization script was added, and should not have been");
	}

	public function test_MissingHeaders() {
		$checkboxValue = "1";
		$args = array(
			"columns" => array($checkboxValue => array("value 1 label", "additional column")),
			"hovers" => array($checkboxValue => "this is the hover text")
		);

		$formItem = new MultiCheckBoxTable($this->form, "testitem", $args);
		$renderedData = $formItem->render("");

		// should return an empty string (no data rendered)
		$this->assertEmpty($renderedData, "Should not have rendered anything when no headers are provided");
	}

	public function test_MissingColumns() {
		$checkboxValue = "1";
		$args = array(
			"headers" => array("box", "value_name", "more info"),
			"hovers" => array($checkboxValue => "this is the hover text")
		);

		$formItem = new MultiCheckBoxTable($this->form, "testitem", $args);
		$renderedData = $formItem->render("");

		// should return an empty string (no data rendered)
		$this->assertEmpty($renderedData, "Should not have rendered anything when no columns are provided");
	}
} 