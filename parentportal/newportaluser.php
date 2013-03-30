<?
// dummy page to redirect to main index page (many customers have a link to this obsolete page, now send to index to send to portalauth)
$ppNotLoggedIn = 1;
require_once("common.inc.php");

// forward any params to index page
$params = http_build_query($_GET);

redirect("index.php?" . $params);

?>
