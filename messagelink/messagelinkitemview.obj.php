<?
class MessageLinkItemView {

	protected $templateDir;
	protected $templateName;
	protected $vars = array();

	public function __construct($template, $data = array()) {

		$messagelinkDir = dirname(__FILE__);
		$this->templateDir = $messagelinkDir . '/templates/';

		$this->templateName = $template;
		$this->vars = $data;
	}

	public function __get($name) {
		return $this->vars[$name];
	}

	public function render() {
		if (file_exists($this->templateDir . $this->templateName )) {
			include($this->templateDir . $this->templateName );
		} else {
			throw new Exception('Template file: ' . $this->templateDir . $this->templateName  . ' does not exist.');
		}
	}
}
?>