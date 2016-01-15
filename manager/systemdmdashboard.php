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
<div style="position:relative;width:425px;height:325px;float:left;margin: 10px 10px 10px 10px;"><iframe width="425" height="325" src="https://insights-embed.newrelic.com/embedded_widget/J8S1yOoQ8ANbIFJFdLE0571ckNJ7JgqC" frameborder="0" style="position:absolute;width:425px;height:325px"></iframe></div>
<div style="position:relative;width:425px;height:325px;float:left;margin: 10px 10px 10px 10px;"><iframe width="425" height="325" src="https://insights-embed.newrelic.com/embedded_widget/IyUqAEpSC0mICX3LYeQhA7Fuds-xqEL7" frameborder="0" style="position:absolute;width:425px;height:325px"></iframe></div>
<div style="position:relative;width:425px;height:325px;float:left;margin: 10px 10px 10px 10px;"><iframe width="425" height="325" src="https://insights-embed.newrelic.com/embedded_widget/9_hwRwCkhRWwyjD4TJa9eU0TKa2uJeSD" frameborder="0" style="position:absolute;width:425px;height:325px"></iframe></div>

<div style="position:relative;width:425px;height:325px;float:left;margin: 10px 10px 10px 10px;"><iframe width="425" height="325" src="https://insights-embed.newrelic.com/embedded_widget/vUP6yC6B3rnsaLTH9lrWj4GnoYW4CmV6" frameborder="0" style="position:absolute;width:425px;height:325px"></iframe></div>
<div style="position:relative;width:425px;height:325px;float:left;margin: 10px 10px 10px 10px;"><iframe width="425" height="325" src="https://insights-embed.newrelic.com/embedded_widget/ziBTPJtldCZxtQsL44mjua0IoYK3htSr" frameborder="0" style="position:absolute;width:425px;height:325px"></iframe></div>
<div style="position:relative;width:425px;height:325px;float:left;margin: 10px 10px 10px 10px;"><iframe width="425" height="325" src="https://insights-embed.newrelic.com/embedded_widget/v93lGsE-nxyVcfUYy41UBrJ3WjwEOx_D" frameborder="0" style="position:absolute;width:425px;height:325px"></iframe></div>

<div style="position:relative;width:425px;height:325px;float:left;margin: 10px 10px 10px 10px;"><iframe width="425" height="325" src="https://insights-embed.newrelic.com/embedded_widget/KdlDRE3kMKG99F0j-rK4jOM298I-Qp2o" frameborder="0" style="position:absolute;width:425px;height:325px"></iframe></div>
<div style="position:relative;width:425px;height:325px;float:left;margin: 10px 10px 10px 10px;"><iframe width="425" height="325" src="https://insights-embed.newrelic.com/embedded_widget/sLVWK5jBNKFYLale3u49fg5RAB92LAVf" frameborder="0" style="position:absolute;width:425px;height:325px"></iframe></div>
<div style="position:relative;width:425px;height:325px;float:left;margin: 10px 10px 10px 10px;"><iframe width="425" height="325" src="https://insights-embed.newrelic.com/embedded_widget/RdTyUuiDOb79mJVVIImxsh3Tf1t0cf_p" frameborder="0" style="position:absolute;width:425px;height:325px"></iframe></div>

<?

endWindow();

include_once("navbottom.inc.php");
?>
