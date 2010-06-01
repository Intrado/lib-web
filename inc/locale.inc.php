<?

// Supported locales

$LOCALES = array("en_US" => "English",
				"es_US" => "Español");

if (isset($_SESSION['_locale']))
	$LOCALE = $_SESSION['_locale'];

if (isset($_SESSION['portaluser']['portaluser.preferences']['_locale']))
	$LOCALE = $_SESSION['portaluser']['portaluser.preferences']['_locale'];

if (!isset($LOCALE) || !isset($LOCALES[$LOCALE])) {
	$LOCALE = 'en_US';
}

putenv("LANGUAGE=$LOCALE");
putenv("LC_ALL=$LOCALE");
setlocale(LC_ALL,$LOCALE);
bindtextdomain("messages", "./locale");
bind_textdomain_codeset("messages", 'UTF-8');
textdomain("messages");

function _L($text) {
	$string = _($text);
	
	if (!$string)
		$string = $text;
	
	$numArgs = func_num_args();
	if ($numArgs > 1) {
		$args = array($string);
		for ($i = 1; $i < $numArgs; $i++)
			$args[] = func_get_arg($i);

		$string = call_user_func_array("sprintf", $args);
	}	
	
	return $string;
}

?>
