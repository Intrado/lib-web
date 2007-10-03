<?
require_once("common.inc.php");
require_once("../inc/securityhelper.inc.php");
require_once("../inc/html.inc.php");
require_once("parentportalutils.inc.php");
require_once("../inc/table.inc.php");
require_once("../obj/FieldMap.obj.php");
require_once("../obj/Person.obj.php");

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////
$allData = array();
if($_SESSION['customerid']){
	$firstnameField = FieldMap::getFirstNameField();
	$lastnameField = FieldMap::getLastNameField();
	$contactList = getContactIDs($_SESSION['portaluserid']);
	
	foreach($contactList as $personid){
		$result = Query("select j.id, j.startdate, j.name, j.type, u.firstname, u.lastname, rp.messageid, rp.personid
			from job j 
			left join reportperson rp on (rp.jobid = j.id)
			inner join user u on (u.id = j.userid)
			where j.startdate <= curdate() and j.startdate >= date_sub(curdate(),interval 30 day)
			and rp.personid = '" . $personid . "'
			and j.status in ('active', 'complete')
			order by j.startdate");
		$data = array();
		while($row = DBGetRow($result)){
			$data[] = $row;
		}
		$allData[$personid] = $data;
	}
	
	$titles = array("1" => "Date",
					"2" => "Subject",
					"SentBy" => "Sent By",
					"3" => "Type",
					"Actions" => "Actions"
				);
	
	$formatters = array("1" => "format_date",
						"SentBy" => "sender",
						"3" => "format_type",
						"Actions" => "message_action"
					);
}
////////////////////////////////////////////////////////////////////////////////
// Functions
////////////////////////////////////////////////////////////////////////////////

function message_action($row, $index){
	//index 0 is job id and index 7 is person id
	//index 3 is type
	if($row[3] == "phone"){
		return button("Play", "popup('previewmessage.php?jobid=" . $row[0] . "&personid=" . $row[7] . "', 400, 500);",null);
	} else {
		return "";
	}
}

function format_type($row, $index){
	return ucfirst($row[$index]);
}

function format_date($row, $index){
	return date("M d, Y", strtotime($row[$index]));
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
if($_SESSION['customerid']){
	foreach($contactList as $personid){
		$data = $allData[$personid];
		$person = new Person($personid);
		startWindow("Messages for " . $person->$firstnameField . " " . $person->$lastnameField . " from the last 30 days",'padding: 3px;');
		$scroll="";
		if(count($data) > 6)
			$scroll = 'class="scrollTableContainer"';
?>
		<div <?=$scroll?>>
			<table width="100%" cellpadding="3" cellspacing="1" class="list">
<?
				showTable($data, $titles, $formatters);
?>
			</table>
		</div>
<?
		endWindow();
?>
		<br>
<?
	}
} else {
	?><img src="img/bug_important.gif" >You are not associated with any contacts.  If you would like to add a contact, <a href="addcontact.php"/>Click Here</a><?
}
include_once("navbottom.inc.php");
?>