<?

/**
 * Abstract class for pages with multiple parts
 *
 * Each "part" of the page is rendered in its own start/end window container and
 * must have its own title supplied.
 *
 * DEPENDENCIES
 * obj/PageBase.obj.php
 */
abstract class PageMultiPart extends PageBase {

	/**
	 * Page parts
	 */
	var $parts = Array();

	/**
	 * Add a page part; all the parts will be finally rendered in sequence
	 *
	 * @param string $title The title that will show in this part's window header
	 * @param string $content The content that will appear within this part's window body
	 */
	function addPart($title, $content) {
		$this->parts[] = Array(
			'title' => $title,
			'content' => $content
		);
	}

	/**
	 * Override to make one "window" per part
	 */
	function sendPageOutput() {
		if (! count($this->parts)) return;
		foreach ($this->parts as $part) {
			startWindow(_L($part['title']));
			echo $part['content'];
			endWindow();
		}
	}
}

