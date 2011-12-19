<?

session_cache_limiter(false); //disable automatic cache headers when sessions are used

header("Expires: " . gmdate('D, d M Y H:i:s', time() + 60*60) . " GMT"); //exire in 1 hour, but if theme changes so will hash pointing to this file
header("Content-Type: text/css");
header("Cache-Control: private");

include_once("css/css.inc.php");
?>
