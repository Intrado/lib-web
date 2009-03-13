<?

// Supported locales

$LOCALES = array("en_US" => "English",
				"es_US" => "Español",
				"fr_CA" => "Français");

if (isset($_SESSION['portaluser']['portaluser.preferences']['_locale']))
	$locale = $_SESSION['portaluser']['portaluser.preferences']['_locale'];
	
if (isset($_SESSION['_locale']))
	$locale = $_SESSION['_locale'];

if (isset($_SESSION['user']))
	$locale = $USER->getSetting('_locale', getSystemSetting('_locale'));

if (!isset($locale) || !isset($locale, $LOCALES))
	$locale = 'en_US';

putenv("LANGUAGE=$locale");
putenv("LC_ALL=$locale");
setlocale(LC_ALL,$locale);
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
