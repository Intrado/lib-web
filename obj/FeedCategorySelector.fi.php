<?
/**
 * Creates a table of checkboxes which should be used to display feed categories
 *
 * args:
 * 	feedcategories => array(FeedCategory) list of feed category objects to display for selection
 *
 * User: nrheckman
 * Date: 2/14/14
 * Time: 2:02 PM
 */

class FeedCategorySelector extends MultiCheckBoxTable {
	var $availableCategories;

	/**
	 * @param Form $form the parent form
	 * @param string $name the name of the form item
	 * @param array $args additional arguments required to initialize this form item
	 */
	function __construct($form, $name, $args) {
		parent::__construct($form, $name, $args);

		$this->availableCategories = (isset($args['feedcategories'])? $args['feedcategories']: false);
		// apply hover descriptions to the checkbox and label columns
		$this->hoverColumns = array(0,1);
		$this->cssClass = "feedcategoryselector";
	}

	function render ($value) {
		if (!$this->availableCategories)
			return "";

		$columns = array();
		$hovers = array();
		foreach ($this->availableCategories as $feedCategory) {
			$typeHtml = "";
			foreach ($feedCategory->getTypes() as $type) {
				switch ($type) {
					case "rss":
						$typeHtml .= '<img class="categorytype rss" src="img/icons/pictos/p1/16/80.png" />';
						break;
					case "desktop":
						$typeHtml .= '<img class="categorytype desktop" src="img/icons/pictos/p1/16/160.png" />';
						break;
					case "push":
						$typeHtml .= '<img class="categorytype push" src="img/icons/pictos/p2/16/70.png" />';
				}
			}
			$columns[$feedCategory->id] = array(escapeHtml($feedCategory->name), $typeHtml);
			$hovers[$feedCategory->id] = $feedCategory->description;
		}
		$this->headers = array("", _L("Feed Category Name"), _L("Feed Types"));
		$this->columns = count($columns)? $columns: false;
		$this->hovers = count($hovers)? $hovers: false;

		return parent::render($value);
	}

	function renderJavascript($value) {
		$hoverdata = array(
			".{$this->cssClass} img.rss" => _L('RSS Feed'),
			".{$this->cssClass} img.desktop" => _L('Desktop Alert'),
			".{$this->cssClass} img.push" => _L('Push Notification')
		);
		return parent::renderJavascript($value). ' form_do_hover_by_selector('. json_encode($hoverdata). ');';
	}
}
?>