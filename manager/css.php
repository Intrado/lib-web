<?

session_cache_limiter(false); //disable automatic cache headers when sessions are used

header("Expires: " . gmdate('D, d M Y H:i:s', time() + 60*60) . " GMT"); //exire in 1 hour, but if theme changes so will hash pointing to this file
header("Content-Type: text/css");
header("Cache-Control: private");

if (isset($_GET["newnav"])) {
	include_once("../inc/themes.inc.php");
	$_SESSION['colorscheme'] = array();
	$_SESSION['colorscheme']['_brandtheme'] = "forest";
	$_SESSION['colorscheme']['_brandprimary'] = "0D8336";
	$_SESSION['colorscheme']['_brandratio'] = ".2";
	$_SESSION['colorscheme']['_brandtheme1'] = $COLORSCHEMES["forest"]["_brandtheme1"];
	$_SESSION['colorscheme']['_brandtheme2'] = $COLORSCHEMES["forest"]["_brandtheme2"];
}

include_once("css/css.inc.php");
?>
