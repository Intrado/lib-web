<?
require_once("common.inc.php");
require_once("../inc/form.inc.php");
require_once("../inc/html.inc.php");
require_once("../inc/table.inc.php");
require_once("../inc/utils.inc.php");
require_once("../obj/Phone.obj.php");
require_once("../inc/themes.inc.php");

if (!$MANAGERUSER->authorized("ffield2gfield"))
	exit("Not Authorized");

if (!isset($_GET['cid']))
	exit("Missing customer id");

$currentid = $_GET['cid'] + 0;
$custinfo = QuickQueryRow("select s.dbhost, c.dbusername, c.dbpassword, c.urlcomponent, c.enabled, c.oem, c.oemid, c.nsid, c.notes from customer c inner join shard s on (c.shardid = s.id) where c.id = '$currentid'");
$custdb = DBConnect($custinfo[0], $custinfo[1], $custinfo[2], "c_$currentid");
if (!$custdb) {
	exit("Connection failed for customer: $custinfo[0], db: c_$currentid");
}

$ffields = QuickQueryList("select fieldnum, name from fieldmap where fieldnum like 'f%' and options like '%multisearch%' and options like '%searchable%' and options not like '%grade%' and fieldnum not in ('f01','f02','f03') order by id", true, $custdb);
$gfields = QuickQueryList("select fieldnum, name from fieldmap where fieldnum like 'g%' order by id", true, $custdb);

$f = "advancedactions";
$s = "main";
$reloadform = 0;
$refreshdm = false;

if(CheckFormSubmit($f,$s))
{
	//check to see if formdata is valid
	if(CheckFormInvalid($f))
	{
		error('Form was edited in another window, reloading data');
		$reloadform = 1;
	}
	else
	{
		MergeSectionFormData($f, $s);

		//do check
		if( CheckFormSection($f, $s) ) {
			error('There was a problem trying to save your changes', 'Please verify that all required field information has been entered properly');
		} else {
			
			$ffield = GetFormData($f,$s,"ffield");
			$gfield = GetFormData($f,$s,"gfield");
			$gfieldnum = substr($gfield,1) + 0;
			
			// verify data
			echo "<pre>Verifying existing data is ok to move\n";

			$query = "select m.name,m.userid,concat(u.login,if(u.deleted,' (deleted)',if(not u.enabled,' (disabled)',''))) from messagepart mp left join message m on (m.id = mp.messageid) left join user u on (m.userid=u.id) where fieldnum='$ffield' and not m.deleted";
			$data = QuickQueryMultiRow($query,false,$custdb);
			if (count($data) > 0) {
				echo '<table border=1 cellspacing=0>';
				
				showTable($data,array("msg name","userid","login"));
				echo '</table>';
				exit("FAILURE: messagepart exists with this ffield");				
			}
			// verify ffield exists and is multisearch
			$query = "select count(*) from fieldmap where fieldnum='$ffield' and options like '%multisearch%' and options like '%searchable%' and options not like '%grade%'";
			if (QuickQuery($query,$custdb) != 1) die ("FAILURE: missing searchable multiselect field $ffield");
			// verify gfield does not exist
			$query = "select count(*) from fieldmap where fieldnum='$gfield'";
			if (QuickQuery($query,$custdb) > 0) die ("FAILURE: $gfield already exists");
			// verify no groupdata for gfield
			$query = "select count(*) from groupdata where fieldnum=$gfieldnum";
			if (QuickQuery($query,$custdb) > 0) die ("FAILURE: groupdata exists for $gfield");

			echo "Moving Ffield to Gfield\n";

			$query = "insert into reportgroupdata (fieldnum, jobid, personid, value) select $gfieldnum, jobid, personid, $ffield from reportperson where $ffield!=''";
			QuickUpdate($query,$custdb);
			$query = "update reportperson set $ffield=''";
			QuickUpdate($query,$custdb);
			$query = "insert into groupdata (fieldnum, personid, value, importid) select $gfieldnum, id, $ffield, importid from person where importid is not null and $ffield!=''";
			QuickUpdate($query,$custdb);
			$query = "update person set $ffield=''";
			QuickUpdate($query,$custdb);
			$query = "update persondatavalues set fieldnum='$gfield' where fieldnum='$ffield'";
			QuickUpdate($query,$custdb);
			$query = "update importfield set mapto='$gfield' where mapto='$ffield'";
			QuickUpdate($query,$custdb);
			$query = "update rule set fieldnum='$gfield' where fieldnum='$ffield'";
			QuickUpdate($query,$custdb);
			$query = "update fieldmap set fieldnum='$gfield' where fieldnum='$ffield'";
			QuickUpdate($query,$custdb);
			$query = "select accessid, value from permission where name='datafields' and value like '%$ffield%'";
			$res = Query($query,$custdb);
			while ($row = DBGetRow($res)) {
				$newvalue = str_replace($ffield, $gfield, $row[1]);
				$query = "update permission set value='$newvalue' where name='datafields' and accessid=$row[0]";
				QuickUpdate($query,$custdb);
			}

			echo "SUCCESS\n</pre>";
			echo '<hr></hr><a href="customers.php">Back to Customer list</a>';
			
			exit();
			
		}
	}
} else {
	$reloadform = 1;
}

if( $reloadform )
{
	ClearFormData($f);

	PutFormData($f, $s, "ffield",0, "text", "nomin", "nomax", true);
	PutFormData($f, $s, "gfield",0, "text", "nomin", "nomax", true);
}

$custurl = QuickQuery("select c.urlcomponent from customer c where c.id = ?", false, array($currentid));

include_once("nav.inc.php");

//custom newform declaration to catch if manager password is submitted
NewForm($f);
?>
<h2>F field to G field migration for customer: <?=$custurl?></h2>

<br>
<? 
NewFormItem($f, $s, "ffield", "selectstart", null, null, "");
NewFormItem($f, $s, "ffield", "selectoption", "Select f-field", "");
foreach($ffields as $fieldnum => $name){
	NewFormItem($f, $s, "ffield", "selectoption", "$fieldnum $name", $fieldnum);
}
NewFormItem($f, $s, "ffield", "selectend");
?>
=&gt;
<? 
NewFormItem($f, $s, "gfield", "selectstart", null, null, "");
NewFormItem($f, $s, "gfield", "selectoption", "Select g-field", "");
for($i = 1; $i < 10; $i++){
	if(!isset($gfields["g0$i"]))
		NewFormItem($f, $s, "gfield", "selectoption", "g0$i", "g0$i");
}
NewFormItem($f, $s, "gfield", "selectend");
?>
<br />

<?
NewFormItem($f, $s, "Run Now", "submit");

EndForm();
?>
<br>
<?
include_once("navbottom.inc.php");