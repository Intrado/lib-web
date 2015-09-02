<?

/**
 * Creates a table of checkboxes which should be used to display twitter accounts
 *
 * args:
 * 	twitterTokens => array(TwitterToken) list of Twitter Token objects to display for selection
 */
class TwitterAccountSelector extends MultiCheckBoxTable {
	var $availableCategories = array();

	/**
	 * @param Form $form the parent form
	 * @param string $name the name of the form item
	 * @param array $args additional arguments required to initialize this form item
	 */
	function __construct($form, $name, $args) {
		parent::__construct($form, $name, $args);
		$this->twitterTokens = (isset($args['twittertokens']) ? $args['twittertokens'] : false);
		// apply hover descriptions to the checkbox and label columns
		$this->hoverColumns = array(0,1);
		$this->cssClass = "twitteraccountselector";
	}

	/**
	 * @param $value String JSON encoded array of twitter user_id strings;
	 */
	function render ($value) {
		if (! is_array($this->twitterTokens)) return '';
		$columns = $hovers = array();
		foreach ($this->twitterTokens as $token) {
			$html = '<div id="' . $this->getFieldId($token->user_id) . '"></div>';
			$columns[$token->user_id] = array($html);
			$hovers[$token->user_id] = $token->screen_name;
		}
		$this->headers = array('', _L('Twitter Account'));
		$this->columns = count($columns) ? $columns : false;
		$this->hovers = count($hovers) ? $hovers : false;

		// We'll decode the json array into a PHP array for parent to check the boxes correctly
		return parent::render(json_decode($value));
	}

	function getFieldId($user_id) {
		return $this->form->name . '_' . $this->name . '_twitteruser_' . $user_id;
	}

	function renderJavascript($value) {
		$str = parent::renderJavascript($value);
		if (! is_array($this->twitterTokens)) return $str;
		foreach ($this->twitterTokens as $token) {
			$str .= 'TwitterHelper.loadUserData("' . $this->getFieldId($token->user_id) . '", "' . escapehtml($token->user_id) . '");' . "\n";
		}
		return $str;
	}
													
	function renderJavascriptLibraries() {
		$script = parent::renderJavascriptLibraries();
		$script .= '<script type="text/javascript" src="script/TwitterHelper.js"></script>';
		return $script;
        }
}
?>
