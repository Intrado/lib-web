<?

//Preview Email from portal only

////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
include_once("common.inc.php");
include_once("../obj/FieldMap.obj.php");
include_once("../inc/form.inc.php");
include_once("../inc/html.inc.php");
require_once("../inc/table.inc.php");
require_once("../inc/utils.inc.php");
require_once("../inc/date.inc.php");
require_once("../inc/securityhelper.inc.php");
require_once("../inc/formatters.inc.php");
require_once("../inc/reportutils.inc.php");
require_once("../obj/Phone.obj.php");
require_once("../obj/Message.obj.php");
require_once("../obj/AudioFile.obj.php");
require_once("../obj/MessagePart.obj.php");


////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

if(isset($_GET['jobid']) && isset($_GET['personid'])){
	$_SESSION['previewmessage_jobid'] = $_GET['jobid']+0;
	$_SESSION['previewmessage_personid'] = $_GET['personid']+0;
	$_SESSION['previewmessageid'] = QuickQuery("select messageid from reportperson where jobid = '" . $_SESSION['previewmessage_jobid'] . "'
												 and personid = '" . $_SESSION['previewmessage_personid'] . "'");
	redirect();
}

$jobid = isset($_SESSION['previewmessage_jobid']) ? $_SESSION['previewmessage_jobid'] : 0;
$personid = isset($_SESSION['previewmessage_personid']) ? $_SESSION['previewmessage_personid'] : 0;
$messageid = isset($_SESSION['previewmessageid']) ? $_SESSION['previewmessageid'] : 0;

//find all unique fields used in this message

$messagefields = DBFindMany("FieldMap", "from fieldmap where fieldnum in (select distinct fieldnum from messagepart where messageid='$messageid')");

$dopreview = 0;
$fields = array();

//fetch historic data
if($jobid != 0 && $personid != 0){
	$query = "select
			rp." . FieldMap::GetFirstNameField() . ",
			rp." . FieldMap::GetLastNameField()
			. generateFields("rp")
			. ",
			rc.email as destination, 
			j.name as jobname, 
			j.startdate as startdate,
			m.name as messagename
			from reportperson rp
			left join reportcontact rc on (rc.jobid = rp.jobid and rc.personid = rp.personid and rc.type = rp.type)
			left join job j on (j.id = rp.jobid)
			left join message m on (m.id = rp.messageid)
			where j.id = '$jobid'
			and rp.personid = '$personid'";
	$historicdata = QuickQueryRow($query, true);
}

$messageparts = DBFindMany("MessagePart", "from messagepart where messageid = '" . $messageid . "' order by sequence");
$data = Message::format($messageparts);
$message = "";

if (!isset($errors))
	$errors = array();

$txtpart = "";
while (true) {
	//get dist to next field and type of field
	$pos_f = strpos($data,"<<");

	if ($pos_f !== false) {
		$pos = $pos_f;
	} else {
		break;
	}

	//make a text part up to the pos of the field
	$txt = substr($data,0,$pos);
	if (strlen($txt) > 0) {
		$message .= $txt;
	}

	$pos += 2; // pass over the begintoken

	$endtoken = ">>";
	$length = @strpos($data,$endtoken,$pos+1); // assume at least one char for audio/field name

	if ($length === false) {
		$errors[] = "Can't find end of field, was expecting '$endtoken'";
		$length = 0;
	} else {
		$length -= $pos;

		$token  = substr($data,$pos,$length);


		if (strpos($token,":") !== false) {
			list($fieldname,$defvalue) = explode(":",$token);
		} else {
			$fieldname = $token;
			$defvalue = "";
			$maxlen = null;
		}
		$fieldname = DBSafe($fieldname);
		$query = "select fieldnum from fieldmap where name='$fieldname'";

		$fieldnum = QuickQuery($query);

		if ($fieldnum !== false) {
			$message .= " " . $historicdata[$fieldnum] . " ";
		} else {
			$errors[] = "Can't find field named '$fieldname'";
		}
	}
	//skip the end if we found it
	if ($length)
		$skip = $pos + $length +2;
	else
		$skip = $pos + $length ;

	$data = substr($data,$skip );
}

////////////////////////////////////////////////////////////////////////////////
// Display Functions
////////////////////////////////////////////////////////////////////////////////


////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$TITLE = "Message Preview";

include_once("popup.inc.php");

buttons(button('Done', 'window.close()'));


startWindow('Message Information', 'padding: 3px;');
?>
	<table width="100%" border="0" cellpadding="3" cellspacing="0">
		<tr>
			<th align="right" class="windowRowHeader bottomBorder">Message For:</td>
			<td class="bottomBorder"><?=$historicdata['f01'] . " " . $historicdata['f02']?></td>
		</tr>
		<tr>
			<th align="right" class="windowRowHeader bottomBorder">Job Name:</td>
			<td class="bottomBorder"><?=$historicdata['jobname']?></td>
		</tr>
		<tr>
			<th align="right" class="windowRowHeader bottomBorder">Date:</td>
			<td class="bottomBorder"><?=date("M d, Y", strtotime($historicdata['startdate']))?></td>
		</tr>
		<tr>
			<th align="right" class="windowRowHeader bottomBorder">Message Name:</td>
			<td class="bottomBorder"><?=$historicdata['messagename']?></td>
		</tr>
		<tr>
			<th align="right" class="windowRowHeader bottomBorder">Email:</td>
			<td class="bottomBorder"><?=$historicdata['destination']?></td>
		</tr>
	</table>
<?
endWindow();


startWindow('Message Preview', 'padding: 3px;');
	if(count($errors)){
		error($errors);
	} else {
		?><div><?=$message?></div><?
	}
endWindow();

include_once("popupbottom.inc.php");

?>