<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("common.inc.php");
require_once("../inc/table.inc.php");
require_once("../inc/html.inc.php");
require_once("../inc/utils.inc.php");
require_once("../obj/Validator.obj.php");
require_once("../obj/Form.obj.php");
require_once("../obj/FormItem.obj.php");

////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////

if(!$MANAGERUSER->authorizedAny(array("aspcallgraphs", "logcollector")))
	exit("Not Authorized");

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$PAGE = "dm:graphlogs";
$TITLE = _L('View ASP Graphs / Log Collector');

include_once("nav.inc.php");

function fmt_name($obj, $name) {
	$actionlinks = array();
	$actionlinks[] = action_link($obj["name"], $obj["icon"], $obj["url"]);
	return action_links($actionlinks);
}

function fmt_desc($obj, $name) {
	return htmlentities($obj["desc"]);
}

startWindow(_L('View ASP Graphs / Log Collector'));

$titles = array(
	"name" => "#Name",
	"desc" => "Description",
);

$formatters = array(
	"name" => "fmt_name",
	"desc" => "fmt_desc",
);

$aspReports = array();

if($MANAGERUSER->authorized("aspcallgraphs")) {

	$aspReports[] = new ArrayObject(array(
		"icon" => "calendar_view_day",
		"name" => "ASP Calls by Day",
		"url" => "aspcallsbyday.php",
		"desc" => "Average per day over a month.",
	));

	$aspReports[] = array(
		"icon" => "calendar_view_month",
		"name" => "ASP Calls by DM",
		"url" => "aspcallsbydm.php",
		"desc" => "Average over a month.",
	);

	$aspReports[] = array(
		"icon" => "chart_bar",
		"name" => "ASP Calls by DM",
		"url" => "graphcallsbydm.php",
		"desc" => "Prototype: backed by New Relic data.",
	);

	$aspReports[] = array(
		"icon" => "telephone",
		"name" => "ASP Calls by DM Time",
		"url" => "aspcallsbydmtime.php",
		"desc" => "Average over one day. Pick date from last two years.",
	);

	$aspReports[] = array(
		"icon" => "telephone",
		"name" => "ASP Calls by DM Same Day of Week",
		"url" => "aspcallsdayofweekdmtime.php",
		"desc" => "Multiple graphs over four weeks.",
	);

	$aspReports[] = array(
		"icon" => "telephone",
		"name" => "ASP Calls by DM Every Day",
		"url" => "aspcallsdayofweekdmtime.php",
		"desc" => "Multiple graphs over 30 days.",
	);

	$aspReports[] = array(
		"icon" => "time",
		"name" => "ASP Calls by Hour",
		"url" => "aspcallsbyhour.php",
		"desc" => "Average of a month.",
	);

	$aspReports[] = array(
		"icon" => "time",
		"name" => "ASP Calls by Time",
		"url" => "aspcallsbytime.php",
		"desc" => "Average of a month.",
	);

	$aspReports[] = array(
		"icon" => "time",
		"name" => "ASP Calls by Time One Day",
		"url" => "aspcallsbytimebyday.php",
		"desc" => "Average over one day. Pick date from last two years.",
	);

	$aspReports[] = array(
		"icon" => "time",
		"name" => "ASP Calls by Time Same Day of Week",
		"url" => "aspcallsdayofweekbytime.php",
		"desc" => "Multiple graphs over four weeks.",
	);

	$aspReports[] = array(
		"icon" => "time",
		"name" => "ASP Calls by Time Every Day",
		"url" => "aspcallsalldaysbytime.php",
		"desc" => "Multiple graphs over 30 days.",
	);

	$aspReports[] = array(
		"icon" => "telephone",
		"name" => "ASP Calls Graph Per DM Per Day",
		"url" => "aspcallsdmday.php",
		"desc" => "Pick date from last two years.",
	);

}

if($MANAGERUSER->authorized("aspcallgraphs")) {

	$aspReports[] = array(
		"icon" => "report",
		"name" => "Log Collector",
		"url" => "aspcallslogcollector.php",
		"desc" => "Info and controls.",
	);

}

if($MANAGERUSER->authorized("aspcallgraphs")) {

	$aspReports[] = array(
		"icon" => "find",
		"name" => "Call Search",
		"url" => "aspcallssearch.php",
		"desc" => "",
	);

}

if (!empty($aspReports)) {
	showObjects($aspReports, $titles, $formatters);
} else {
	echo "<div class='destlabel'><img src='img/largeicons/information.jpg' align='middle'> " . _L("No Reports Available") . "</div>";
}

endWindow();
include_once("navbottom.inc.php");
?>
