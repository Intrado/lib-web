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
	 * Send final output to the client
	 *
	 * This default implementation takes whatever HTML is rendered into
	 * this->pageOutput and wraps it up with standard page header/footer
	 * and the normal start/end window wrapper.
	 */
	function send() {
		global $PAGE, $TITLE, $USER;

		// If we got this far, then assemble some HTML and spit it out
		$PAGE = $this->options['page'];
		$TITLE = _L($this->options['title']);
		include_once("{$this->konadir}/nav.inc.php");

		if (count($this->parts)) {
			foreach ($this->parts as $part) {
				startWindow(_L($part['title']));
				echo $part['content'];
				endWindow();
			}
		}

		include_once("{$this->konadir}/navbottom.inc.php");
	}
}

