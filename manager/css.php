<?

session_cache_limiter(false); //disable automatic cache headers when sessions are used

header("Expires: " . gmdate('D, d M Y H:i:s', time() + 60*60) . " GMT"); //exire in 1 hour, but if theme changes so will hash pointing to this file
header("Content-Type: text/css");
header("Cache-Control: private");

$COLORSCHEMES = array(
	"aspmanager" => array(
		"displayname" => "ASPManager",
		"_brandprimary" => "336699", /* mid green */
		"_brandtheme1" => "6699CC",
		"_brandtheme2" => "99CCFF",
		"_brandratio" => ".2"
	)
);
if (isset($_GET["newnav"])) {

	$_SESSION['colorscheme'] = array();
	$_SESSION['colorscheme']['_brandtheme'] = "aspmanager";
	$_SESSION['colorscheme']['_brandprimary'] = $COLORSCHEMES["aspmanager"]["_brandprimary"];
	$_SESSION['colorscheme']['_brandratio'] = $COLORSCHEMES["aspmanager"]["_brandratio"];
	$_SESSION['colorscheme']['_brandtheme1'] = $COLORSCHEMES["aspmanager"]["_brandtheme1"];
	$_SESSION['colorscheme']['_brandtheme2'] = $COLORSCHEMES["aspmanager"]["_brandtheme2"];
}

include_once("css/css.inc.php");
?>
