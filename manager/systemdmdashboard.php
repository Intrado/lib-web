<?
require_once("common.inc.php");
require_once("../inc/form.inc.php");
require_once("../inc/html.inc.php");
require_once("../inc/table.inc.php");
require_once("../inc/formatters.inc.php");
require_once("dbmo//authserver/DmGroup.obj.php");
include_once("../inc/memcache.inc.php");

if (!$MANAGERUSER->authorized("systemdm"))
	exit("Not Authorized");


/////////////////////////////
// Display
/////////////////////////////
$TITLE = _L("Dashboard");
$PAGE = "dm:systemdmdashboard";

include_once("nav.inc.php");

startWindow(_L('Dashboard'));
?>

<div style="position:relative;width:1400px;height:400px;float:left;margin: 10px 10px 10px 10px;"><img src="graphcallsbydm.php"></div>

<div style="position:relative;width:525px;height:325px;float:left;margin: 10px 10px 10px 10px;"><iframe width="525" height="325" src="https://insights-embed.newrelic.com/embedded_widget/J8S1yOoQ8ANbIFJFdLE0571ckNJ7JgqC" frameborder="0" style="position:absolute;width:525px;height:325px"></iframe></div>
<div style="position:relative;width:525px;height:325px;float:left;margin: 10px 10px 10px 10px;"><iframe width="525" height="325" src="https://insights-embed.newrelic.com/embedded_widget/IyUqAEpSC0mICX3LYeQhA7Fuds-xqEL7" frameborder="0" style="position:absolute;width:525px;height:325px"></iframe></div>
<div style="position:relative;width:525px;height:325px;float:left;margin: 10px 10px 10px 10px;"><iframe width="525" height="325" src="https://insights-embed.newrelic.com/embedded_widget/RdTyUuiDOb79mJVVIImxsh3Tf1t0cf_p" frameborder="0" style="position:absolute;width:525px;height:325px"></iframe></div>

<div style="position:relative;width:525px;height:325px;float:left;margin: 10px 10px 10px 10px;"><iframe width="525" height="325" src="https://insights-embed.newrelic.com/embedded_widget/zFvVKCugUmbAOCifl7LCpDWn9KuDUY2A" frameborder="0" style="position:absolute;width:100%;height:100%"></iframe></div>
<div style="position:relative;width:525px;height:325px;float:left;margin: 10px 10px 10px 10px;"><iframe width="525" height="325" src="https://insights-embed.newrelic.com/embedded_widget/ocRRtS54AHTi9--ahRLvXzRxMURcfJmS" frameborder="0" style="position:absolute;width:100%;height:100%"></iframe></div>
<div style="position:relative;width:525px;height:325px;float:left;margin: 10px 10px 10px 10px;"><iframe width="525" height="325" src="https://insights-embed.newrelic.com/embedded_widget/xTX1YYKzVIGraG7TRlSMGZDofnwXOwAV" frameborder="0" style="position:absolute;width:100%;height:100%"></iframe></div>

<div style="position:relative;width:525px;height:325px;float:left;margin: 10px 10px 10px 10px;"><iframe width="525" height="325" src="https://insights-embed.newrelic.com/embedded_widget/vJB0IV3ZM7qcPirQjfNIYmKXBa2aHqkh" frameborder="0" style="position:absolute;width:100%;height:100%"></iframe></div>
<div style="position:relative;width:525px;height:325px;float:left;margin: 10px 10px 10px 10px;"><iframe width="525" height="325" src="https://insights-embed.newrelic.com/embedded_widget/VAUbQ_pB0HdZrdZZ9U8mTuQp-2qhqF5v" frameborder="0" style="position:absolute;width:100%;height:100%"></iframe></div>
<div style="position:relative;width:525px;height:325px;float:left;margin: 10px 10px 10px 10px;"><iframe width="525" height="325" src="https://insights-embed.newrelic.com/embedded_widget/5xdM5n0haDsD9W6FuaskbeB5rD9qaax8" frameborder="0" style="position:absolute;width:100%;height:100%"></iframe></div>

<?

endWindow();

include_once("navbottom.inc.php");
?>
