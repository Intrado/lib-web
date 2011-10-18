<?
require_once("common.inc.php");
require_once("../inc/table.inc.php");
require_once("../inc/form.inc.php");
require_once("../obj/Phone.obj.php");
require_once("../inc/html.inc.php");

if (!$MANAGERUSER->authorized("customercontacts"))
	exit("Not Authorized");

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

$f = "contactsearch";
$s = "main";
$reloadform = 0;

$data = array();
$titles = array();
$formatters = array();

if(CheckFormSubmit($f,$s))
{
	if(CheckFormInvalid($f))
	{
		error('Form was edited in another window, reloading data');
		$reloadform = 1;
	}
	else
	{
		MergeSectionFormData($f, $s);

		if( CheckFormSection($f, $s) ) {
			error('There was a problem trying to use your selections', 'Please verify that all required field information has been entered properly');
		} else {
			$number = preg_replace("/[^0-9]*/","",GetFormData($f, $s, "number"));

			$res = Query("select id, dbhost, dbusername, dbpassword from shard order by id");
			$shardinfo = array();
			while($row = DBGetRow($res)){
				$shardinfo[$row[0]] = array($row[1], $row[2], $row[3]);
			}

			$customerquery = Query("select id, shardid, urlcomponent from customer order by shardid, id");
			$customers = array();
			while ($row = DBGetRow($customerquery)) {
				$customers[] = $row;
			}
			$currhost = "";
			$custdb;
			$data = array();
			foreach ($customers as $cust) {

				if ($currhost != $cust[1]) {
					$dsn = 'mysql:dbname=c_'.$cust[0].';host='.$shardinfo[$cust[1]][0];
					$custdb = new PDO($dsn, $shardinfo[$cust[1]][1], $shardinfo[$cust[1]][2]);
					$custdb->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
					$currhost = $cust[1];
				}
				Query("use c_" . $cust[0], $custdb);

				$displayname = QuickQuery("select value from setting where name = 'displayname'", $custdb);
				if (GetFormData($f, $s, "type") == "sms") {
					$res = Query("select p.id, p.pkey, p.f01, p.f02 from person p left join sms s on (s.personid = p.id)
									where s.sms = '" . $number . "'", $custdb);
				} else if(GetFormData($f, $s, "type") == "phone") {
					$res = Query("select p.id, p.pkey, p.f01, p.f02 from person p left join phone ph on (ph.personid = p.id)
									where ph.phone = '" . $number . "'", $custdb);
				}
				$persons = array();
				while ($row = DBGetRow($res)) {
					$data[] = array_merge(array($cust[0], $cust[2], $displayname), $row, array(GetFormData($f, $s, "type"), $number));
				}
			}
		}
	}
} else {
	$reloadform = 1;
}

if( $reloadform )
{
	ClearFormData($f);
	PutFormData($f, $s, "type", "phone");
	PutFormData($f, $s, "number", "", "phone", "", "", true);
	PutFormData($f, $s, "Submit", "");

}


$titles = array(0 => "Customer ID",
				"url" => "Name",
				3 => "PID",
				4 => "ID#",
				5 => "First Name",
				6 => "Last Name",
				7 => "Type",
				8 => "Destination");

$formatters = array("url" => "fmt_customer_url",
					4 => "fmt_pkey",
					7 => "fmt_dest_type",
					8 => "fmt_phone_number");

// Customer phone formatter function because we can't use phone.obj.php
function fmt_phone_number($row, $index){
	if($row[$index])
		return "(" . substr($row[$index],0,3) . ") " . substr($row[$index],3,3) . "-" . substr($row[$index],6,4);
	else
		return "";
}

function fmt_pkey($row, $index){
	if($row[$index] == "")
		return "Address Book";
	else
		return $row[$index];

}

function fmt_dest_type($row, $index){
	return ucfirst($row[$index]);
}

//index 2 is display name
//index 1 is url
//index 0 is the id
function fmt_customer_url($row, $index){
	if (!isset($_GET['showdisabled']))
		$url = $row[2] . " (<a href=\"customerlink.php?id=" . $row[0] ."\" target=\"_blank\">" . $row[1] . "</a>)";
	else
		$url = '<span style="color: gray;">' . $row[1] . '</span>';
	return $url;
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$PAGE = "template:template";
$TITLE = "template";

include_once("nav.inc.php");
NewForm($f);
?>
<div>Contact Search</div>
<table>
	<tr>
		<td>Type</td>
		<td>
			<table>
				<tr>
					<td>Phone:</td>
					<td><? NewFormItem($f, $s, "type", "radio", null, "phone"); ?></td>
				</tr>
				<tr>
					<td>SMS:</td>
					<td><? NewFormItem($f, $s, "type", "radio", null, "sms"); ?></td>
				</tr>
			</table>
		</td>
	</tr>

	<tr>
		<td>Number:</td>
		<td><? NewFormItem($f, $s, "number", "text", 14) ?></td>
	</tr>
	<tr>
		<td><? NewFormItem($f, $s, "Submit", "submit")?></td>
	</tr>
</table>

<table class="list">
<?
	showTable($data, $titles, $formatters);
?>
</table>
<?
EndForm();
include_once("navbottom.inc.php");
?>