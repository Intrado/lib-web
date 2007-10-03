<?
require_once("common.inc.php");
require_once("../inc/securityhelper.inc.php");
require_once("../inc/html.inc.php");
require_once("parentportalutils.inc.php");
require_once("../inc/table.inc.php");

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////
$data = false;
if($_SESSION['customerid']){
	$contactList = getContactIDs($_SESSION['portaluserid']);
	
	$result = Query("select j.id, j.startdate, j.name, j.type, u.firstname, u.lastname, rp.messageid, rp.personid
		from job j 
		left join reportperson rp on (rp.jobid = j.id)
		inner join user u on (u.id = j.userid)
		where j.startdate <= curdate() and j.startdate >= date_sub(curdate(),interval 30 day)
		and rp.personid in ('" . implode("','", $contactList) . "') "
		. "order by j.startdate");
	$data = array();
	while($row = DBGetRow($result)){
		$data[] = $row;
	}
	
	$titles = array("1" => "Date",
					"2" => "Subject",
					"SentBy" => "Sent By",
					"3" => "Type",
					"Actions" => "Actions"
				);
	
	$formatters = array("SentBy" => "sender",
						"Actions" => "message_action"
					);
}
////////////////////////////////////////////////////////////////////////////////
// Functions
////////////////////////////////////////////////////////////////////////////////

function message_action($row, $index){
	//index 0 is job id and index 7 is person id
	return button("Play", "popup('previewmessage.php?jobid=" . $row[0] . "&personid=" . $row[7] . "', 400, 500);",null);
}

function sender($row, $index){
	//index 4 is first name
	//index 5 is last name
	return $row[4] . " " . $row[5];
}
////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$TITLE="Welcome - " . $_SESSION['portaluser']['portaluser.firstname'] . " " . $_SESSION['portaluser']['portaluser.lastname'];
$PAGE = 'welcome:welcome';
include_once("nav.inc.php");
startWindow("My messages from the last 30 days");
?>
<table width="100%" cellpadding="3" cellspacing="1" class="list">
<?
if($data){
	showTable($data, $titles, $formatters);
} else {
?>
	<tr><td>You are not associated with any contacts.  If you would like to add a contact, <a href="addcontact.php"/>Click Here</a></td></tr>
<?
}
?>
</table>
<?
endWindow();
include_once("navbottom.inc.php");
?>