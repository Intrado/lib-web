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
if(isset($_SESSION['customerid']) && $_SESSION['customerid']){
	$firstnameField = FieldMap::getFirstNameField();
	$lastnameField = FieldMap::getLastNameField();
	$contactList = getContactIDs($_SESSION['portaluserid']);
	
	foreach($contactList as $personid){
		$result = Query("select j.id, j.startdate, j.name, j.type, u.firstname, u.lastname, rp.messageid, rp.personid , u.email
			from job j 
			left join reportperson rp on (rp.jobid = j.id)
			inner join user u on (u.id = j.userid)
			where 
			j.startdate <= curdate() and j.startdate >= date_sub(curdate(),interval 30 day)
			and rp.personid = '" . $personid . "'
			and j.status in ('active', 'complete')
			and j.questionnaireid is null
			order by j.startdate");
		$data = array();
		$number = 1;
		while($row = DBGetRow($result)){
			$data[] = array_merge(array($number), $row);
			$number++;
		}
		$allData[$personid] = $data;
	}
	
	$titles = array("0" => "##",
					"2" => "Date",
					"3" => "#Job Name",
					"SentBy" => "#Sent By",
					"4" => "#Delivery Type",
					"Actions" => "Actions"
				);
	
	$formatters = array("2" => "format_date",
						"SentBy" => "sender",
						"4" => "format_type",
						"Actions" => "message_action"
					);
}
////////////////////////////////////////////////////////////////////////////////
// Functions
////////////////////////////////////////////////////////////////////////////////

function message_action($row, $index){
	//index 1 is job id
	//index 8 is person id
	//index 4 is type
	if($row[4] == "phone"){
		return button("Play", "popup('previewmessage.php?jobid=" . $row[1] . "&personid=" . $row[8] . "', 400, 500);",null);
	} if($row[4] == "email"){
		return button("Read", "popup('previewemail.php?jobid=" . $row[1] . "&personid=" . $row[8] . "', 400, 500);",null);
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
	//index 5 is first name
	//index 6 is last name
	//index 4 is type
	//index 9 is email
	if($row[4] == 'email'){
		return "<a href='mailto:" . $row[9] . "'>" . $row[5] . " " . $row[6] . "</a>";
	} else {
		return $row[5] . " " . $row[6];
	}
}
////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$TITLE="Welcome - " . $_SESSION['portaluser']['portaluser.firstname'] . " " . $_SESSION['portaluser']['portaluser.lastname'];
$PAGE = 'welcome:welcome';
include_once("nav.inc.php");
if(isset($_SESSION['customerid'])){
	?><div><b>Messages from the last 30 days</b></div><br><?
	$counter = 1000;
	foreach($contactList as $personid){
		$counter++;
		$data = $allData[$personid];
		$person = new Person($personid);
		startWindow($person->$firstnameField . " " . $person->$lastnameField,'padding: 3px;', true);
		$scroll="";
		if(count($data) > 6)
			$scroll = 'class="scrollTableContainer"';
?>
		<div <?=$scroll?>>
			<table width="100%" cellpadding="3" cellspacing="1" class="list sortable" id="tableid<?=$counter?>">
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
	startWindow("No Associations");
	
	?>
	<br>
		<img src="img/bug_important.gif" >You are not associated with any contacts.  If you would like to add a contact, <a href="addcontact1.php"/>Click Here</a>
	<br>
	<br>
	<?
	
	endWindow();
}
include_once("navbottom.inc.php");
?>