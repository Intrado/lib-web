<?

/**
 * Simple "view" class that can render a template with associated (model) data.
 *
 * Example usage:
 *
 * example template file => name.tpl.php: <? <div><?= $this->name ?></div> ?>
 * $view = new MessageLinkItemView("name.tpl.php", $modelData = array("name" => "Justin Burns"));
 * $view->render()
 *
 * will result in the following html included at the location $view->render() was called.
 * <div>Justin Burns</div>
 *
 * This allows child/nested views to be supported by setting a model/data attribute to a new MessageLinkItemView, ex.
 * $modelData = array("name" => "Justin Burns", "subview" => new MessageLinkItemView("subview.tpl.php", array(...))
 * then in your parent template, call the subview's render() method like so:
 * <div> <? $this->name ?></div>
 * <div><? $this->subview->render() </div>
 *
 * @author: Justin Burns <jburns@schoolmessenger.com
 * @date: Feb 27, 2014
 *
 */
class MessageLinkItemView {

	public $template;
	public $vars = array();

	/**
	 * @param string $template name of template file to use for view
	 * @param array $data template data
	 */
	public function __construct($template, $data = array()) {

		$this->template = dirname(__FILE__) . '/templates/' . $template;
		$this->vars = $data;
	}

	/**
	 *
	 * @param string $name
	 * @return mixed
	 */
	public function __get($name) {
		$value = null;
		if (isset($this->vars[$name]))
			$value = $this->vars[$name];
		return $value;
	}

	/**
	 * if template file exists, includes the template's resulting html
	 * at the location $view->render() is called.  Takes advantage of the "magic"
	 * __get() method to dynamically create instance properties/variables,
	 * based on arbitrary array $data passed in upon construction
	 *
	 * @throws Exception
	 */
	public function render() {
		if (file_exists($this->template)) {
			$this->includeTemplate($this->template);
		} else {
			$this->throwException('Template file: ' . $this->template  . ' does not exist.');
		}
	}

	/**
	 * @param $template
	 */
	public function includeTemplate($template) {
		include_once($template);
	}

	/**
	 * @param $exceptionMessage
	 * @throws Exception
	 */
	public function throwException($exceptionMessage) {
		throw new Exception($exceptionMessage);
	}

	public function getTemplate() {
		return $this->template;
	}
}
?>