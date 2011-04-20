<?
require_once("common.inc.php");
require_once("../inc/form.inc.php");
require_once("../inc/html.inc.php");
require_once("../inc/utils.inc.php");
require_once("../obj/Phone.obj.php");
require_once("../inc/themes.inc.php");
require_once("../inc/table.inc.php");
require_once("../inc/formatters.inc.php");
require_once("XML/RPC.php");
require_once("authclient.inc.php");


if (!$MANAGERUSER->authorized("passwordcheck"))
	exit("Not Authorized");
	
loadManagerConnectionData();

if (isset($_GET["action"]) && $_GET["action"] == "resetpassword") {
	$params = array(new XML_RPC_Value($_GET["username"], 'string'), new XML_RPC_Value($_GET["customerurl"], 'string'));
	$method = "AuthServer.forgotPassword";
	$result = pearxmlrpc($method, $params, true);
	header('Content-Type: application/json');
	echo ($result && $result['result'] == "")?"true":"false";
	exit();	
}

function fmt_custurl($row, $index){
	global $MANAGERUSER, $CUSTOMERINFO;
	
	if ($row[0] == "Total")
		return '';

	if ($MANAGERUSER->authorized("logincustomer"))
		return "<a href='customerlink.php?id=" . $row[0] ."' target=\"_blank\">" . escapehtml($CUSTOMERINFO[$row[0]]['urlcomponent']) . "</a>";
	else
		return escapehtml(escapehtml($CUSTOMERINFO[$row[0]]['urlcomponent']));
}
function fmt_resetpassword($row, $index){
	global $CUSTOMERINFO;
	return "<a href='passwordcheck.php?action=resetpassword&customerurl=" . $CUSTOMERINFO[$row[0]]['urlcomponent'] ."&username=" . $row[2] ."' onclick='resetpassword(\"" . $CUSTOMERINFO[$row[0]]['urlcomponent'] ."\",\"" . $row[2] ."\");return false;' target=\"_blank\">reset</a>";
}

$badpasswords = array("1qaz2wsx","abc123","admin1","admin123","admin2","admin3","administrator123","aministrator1","asp123","bond007","changeme","changeMe","cookie123","drowssap1","letmein1","letmein123","login1","login123","monster7","ncc1701","nopass1","password0","password1","password123","password1234","password123456","password123456","password2","password3","p@ssw0rd","qazwsx123","qwer123","qwerty12345","reliance202","sch00l","sch00lm3ss3ng3r","schmsgr1","school1","school123","schoolmessenger1","schoolmessenger123","schoolmessenger2","thx1138","trustno1","changem3","hello123");

$badpassquery = "password in (password('" . implode("'), password('",$badpasswords) . "')) or password in (old_password('" . implode("'), old_password('",$badpasswords) . "'))";


include_once("nav.inc.php");
?>

<h2>Bad Password Check</h2>

<hr></hr>

<?




// With the list of customers ready, connect to each customer's shard and retrieve a bunch of helpful information about the customer.
$data = array();
$count = 0;
foreach ($CUSTOMERINFO as $cid => $cust) {
	$custdb = getPooledCustomerConnection($cid,true);
	
	//do stuff here
	$query = "select u.id, concat(u.login,if(not u.enabled,' (disabled)','')), count(*), u.lastlogin 
			from user u 
			inner join user u2 on 
				(u.id != u2.id and u.password=u2.password 
				and not u2.deleted and u2.enabled) 
			where u.password != '' and u.password !='new' 
			and not u.deleted
			group by u.id";
	$res = Query($query,$custdb);
	while ($row = DBGetRow($res)) {
		$data[] = array($cid,
					$row[0],
					$row[1],
					"Dupe pass ($row[2])",
					$row[3]
				);
	}
	
	$query = "select u.id, concat(u.login,if(not u.enabled,' (disabled)','')), u.lastlogin 
			from user u 
			where ($badpassquery)
			and not u.deleted";
	$res = Query($query,$custdb);
	while ($row = DBGetRow($res)) {
		$data[] = array($cid,
					$row[0],
					$row[1],
					"Weak pass",
					$row[2]
				);
	}
	
	echo ".";
	if (++$count % 20 == 0)
		echo "<wbr></wbr>";
	ob_flush();
	flush();
}


$titles = array(
	0 => "#ID",
	"url" => "#Name",
	1 => "#User ID",
	2 => "#User Login",
	3 => "#Reason",
	4 => "#Last login",
	"reset" => ""
);
$formatters = array(
	"url" => "fmt_custurl",
	"reset" => "fmt_resetpassword"
);

echo '<table id="bounced" class="list sortable">';

showTable($data,$titles,$formatters);

echo '</table>';

?>

<script>
function resetpassword(customerurl,username) {
	new Ajax.Request('passwordcheck.php', {
		method:'get',
		parameters: {action: 'resetpassword', customerurl: customerurl, username: username},
		onSuccess: function (response) {
			var result = response.responseJSON;
			if(!result) {
				alert("Unable to reset password, Most likely because the user do not have a email assigned to the account");
			}
		},
		onFailure: function(){
			alert("Failed to reset password");
		}
	});	
	return false;
}
</script>

<?
include_once("navbottom.inc.php");