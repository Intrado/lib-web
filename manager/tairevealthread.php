<?
require_once("common.inc.php");
require_once("../inc/table.inc.php");
require_once("../obj/User.obj.php");
require_once("../obj/Access.obj.php");
require_once("../obj/Permission.obj.php");
require_once("../obj/Person.obj.php");
require_once("../obj/FieldMap.obj.php");
require_once("../obj/Validator.obj.php");
require_once("../obj/Form.obj.php");
require_once("../obj/FormItem.obj.php");
require_once("../inc/html.inc.php");
require_once("dbmo/tai/Thread.obj.php");
require_once("dbmo/tai/Message.obj.php");
require_once("dbmo/tai/UserMessage.obj.php");


if (!isset($_GET['customerid']) && !isset($_GET['threadid'])) {
	redirect("tairevealrequests.php");
}

loadManagerConnectionData();
$custdb = getPooledCustomerConnection($_GET['customerid']);

$thread = DBFind("Thread", "from tai_thread where id=?",false,array($_GET['threadid']),$custdb);
if ($thread === null) {
	redirectToReferrer();
}


$threadperson = DBFind("Person","from person p inner join user u on (u.personid = p.id) inner join tai_thread t on (u.id = t.originatinguserid) where u.id != 1 and t.id=? ","p", array($_GET['threadid']),$custdb);

$TITLE = "Reveal Originating User ID";

include_once("navpopup.inc.php");

startWindow(_L('Identity Request: Thread %s on Customer: %s',$_GET['threadid'],$_GET['customerid']));
?>
<table>
<?

// Switch into Schoolmessenger User role on customer to grab fields
global $_dbcon;
$savedbcon = $_dbcon;
$_dbcon = $custdb;
$fields = FieldMap::retrieveFieldMaps();
// restore global db connection
$_dbcon = $savedbcon;

foreach($threadperson->_fieldlist as $field ){
	if (isset($fields[$field]))
		echo "<tr><td align='right'>{$fields[$field]->name}:</td><td>{$threadperson->$field}</td></tr>";
	else
		echo "<tr><td align='right'>$field:</td><td>{$threadperson->$field}</td></tr>";
}
?>
</table>
<?

endWindow();



include_once("navbottom.inc.php");
?>
