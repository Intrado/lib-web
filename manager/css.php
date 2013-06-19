<?

session_cache_limiter(false); //disable automatic cache headers when sessions are used

header("Expires: " . gmdate('D, d M Y H:i:s', time() + 60*60) . " GMT"); //exire in 1 hour
header("Content-Type: text/css");
header("Cache-Control: private");

include_once("css/css.inc.php");
?>

/* -------- Manager style overrides -------- */
.manager .banner {
	background-image: -webkit-gradient(linear, left top, left bottom, from(#FFFFFF), to(#346998));
	background-image: -webkit-linear-gradient(top, #FFFFFF, #346998);
	background-image: -moz-linear-gradient(top, #FFFFFF, #346998);
	background-image: -ms-linear-gradient(top, #FFFFFF, #346998);
	background-image: -o-linear-gradient(top, #FFFFFF, #346998);
	background-image: linear-gradient(top, #FFFFFF, #346998);
	border-top: 5px solid #363636;
	padding: 0 5px 5px;
}
.manager .banner .banner_logo {
	margin: 5px 0 0;
	display: inline;
	float: left;
}
.manager .banner_links_wrap {
	margin: 0px;
}
.manager .banner_links {
	background: none repeat scroll 0 0 padding-box #363636;
	border-radius: 0 0 8px 8px;
	display: inline;
	float: right;
	list-style-type: none;
	padding: 5px 3px 8px;
}
.manager .banner_links li {
	border: none;
}
.manager .primary_nav {
	padding: 5px 0 0;
	background: none #346998;
	border-bottom: 1px solid #346998;
}
.manager .actionlinks a {
	border: none;
	margin-right: 2px;
}
.manager .footer {
	background: none !important;
	border-top: none !important;
	box-shadow: none !important;
}
.manager .footer .timestamp {
	margin-left: 20px;
}