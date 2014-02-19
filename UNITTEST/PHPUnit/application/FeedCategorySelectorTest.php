<?
/**
 * Unit test class for the FeedCategorySelection form item
 *
 * User: nrheckman
 * Date: 2/14/14
 * Time: 2:12 PM
 */

require_once(realpath(dirname(dirname(__FILE__)) .'/konaenv.php'));
require_once(realpath(dirname(dirname(__FILE__)) .'/PhpStub.php'));
require_once(realpath(dirname(dirname(__FILE__)) .'/DBStub.php'));
// ----------------------------------------------------------------------------
require_once("{$konadir}/inc/locale.inc.php");
require_once("{$konadir}/inc/utils.inc.php");
require_once("{$konadir}/obj/Form.obj.php");
require_once("{$konadir}/obj/FormItem.obj.php");
require_once("{$konadir}/obj/MultiCheckBoxTable.fi.php");
require_once("{$konadir}/obj/FeedCategorySelector.fi.php");
require_once("{$konadir}/inc/DBMappedObject.php");
require_once("{$konadir}/obj/FeedCategory.obj.php");

class FeedCategorySelectorTest extends PHPUnit_Framework_TestCase {
	var $form = null;

	// before each test
	public function setUp() {
		$this->form = new Form("testform", array(), array(), array());
	}

	public function test_AllValidOptions() {
		$first = new SpyFeedCategory();
		$first->id = 1;
		$first->name = "The first category";
		$first->description = "This is the very first category description";
		$first->spyTypes = array("rss", "desktop", "push");

		$second = new SpyFeedCategory();
		$second->id = 2;
		$second->name = "The second category";
		$second->description = "This is the second category description";
		$second->spyTypes = array("rss", "desktop");

		$third = new SpyFeedCategory();
		$third->id = 3;
		$third->name = "The third category";
		$third->description = "This is the third category description";
		$third->spyTypes = array();

		$fourth = new SpyFeedCategory();
		$fourth->id = 4;
		$fourth->name = "The fourth category";
		$fourth->description = "This is the fourth category description";
		$fourth->spyTypes = array("push");

		$feedCategories = array($first, $second, $third, $fourth);

		$args = array("feedcategories" => $feedCategories);
		$formItem = new FeedCategorySelector($this->form, "testitem", $args);
		$renderedData = $formItem->render(array($first->id));

		// be sure that somethign was rendered
		$this->assertNotEmpty($renderedData, 'Nothing was rendered');

		// check that all checkboxes were added
		foreach ($feedCategories as $feedCategory)
			$this->assertTrue((strpos($renderedData, '<input type="checkbox" value="'.escapehtml($feedCategory->id).'"') !== false),
				"Checkbox with value '$feedCategory->id' was not rendered into the form item");

		// check that the first checkbox was added and is checked
		$this->assertTrue((strpos($renderedData, '<input type="checkbox" value="'.escapehtml($first->id).'" checked') !== false),
			"Checkbox '$first->id' was not set to checked");

		// check that the second checkbox was added and is not checked
		$this->assertFalse(strpos($renderedData, '<input type="checkbox" value="'.escapehtml($second->id).'" checked'),
			"Checkbox '$second->id' was set to checked");
	}

	public function test_AllCategoryTypes() {
		$category = new SpyFeedCategory();
		$category->id = 1;
		$category->name = "The first category";
		$category->description = "This is the very first category description";
		$category->spyTypes = array("rss", "desktop", "push");

		$feedCategories = array($category);

		$args = array("feedcategories" => $feedCategories);
		$formItem = new FeedCategorySelector($this->form, "testitem", $args);
		$renderedData = $formItem->render(array($category->id));

		// check that all category types were added
		foreach ($category->getTypes() as $type)
			$this->assertTrue((strpos($renderedData, '<img class="categorytype '. $type) !== false),
				"Image for type '$type' was not rendered into the form item");
	}

	public function test_MissingFeedCategories() {
		$formItem = new FeedCategorySelector($this->form, "testitem", array());
		$renderedData = $formItem->render("");

		// should return an empty string (no data rendered)
		$this->assertEmpty($renderedData, "Should not have rendered anything when no categories are provided");
	}
}

class SpyFeedCategory extends FeedCategory {
	var $spyTypes;

	public function getTypes() {
		return $this->spyTypes;
	}
}
?>