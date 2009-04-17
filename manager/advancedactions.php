<?
require_once("common.inc.php");
require_once("../inc/form.inc.php");
require_once("../inc/html.inc.php");
require_once("../inc/utils.inc.php");
require_once("../obj/Phone.obj.php");
require_once("../inc/themes.inc.php");

if (!$MANAGERUSER->authorized("editcustomer"))
	exit("Not Authorized");

if (isset($_GET['id'])) {
	$_SESSION['currentid']= $_GET['id']+0;
	redirect();
}
if (isset($_SESSION['currentid'])) {
	$currentid = $_SESSION['currentid'];
	$custinfo = QuickQueryRow("select s.dbhost, c.dbusername, c.dbpassword from customer c inner join shard s on (c.shardid = s.id) where c.id = '$currentid'");
	$custdb = DBConnect($custinfo[0], $custinfo[1], $custinfo[2], "c_$currentid");
	if (!$custdb) {
		exit("Connection failed for customer: $custinfo[0], db: c_$currentid");
	}
}


$ffields = QuickQueryList("select fieldnum, name from fieldmap where fieldnum like 'f%' order by id", true, $custdb);
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
		} if(GetFormData($f, $s, "ffield") == 0 || GetFormData($f, $s, "gfield")  == 0) { 
			error('Select both f and g fields');
		} else {
						
			if(CheckFormSubmit($f, "Run Now")){
				redirect("advancedactions.php");
			} else {
				redirect(); //the annoying custinfo above needs to be reloaded
			}
		}
	}
} else {
	$reloadform = 1;
}

if( $reloadform )
{
	ClearFormData($f);

	PutFormData($f, $s, "ffield",0, "text", "nomin", "nomax", false);
	PutFormData($f, $s, "gfield",0, "text", "nomin", "nomax", false);
}


function getDMSetting($dmid, $setting){
	return QuickQuery("select value from dmsetting where name = '" . $setting . "' and dmid = '" . $dmid . "'");
}

include_once("nav.inc.php");

//custom newform declaration to catch if manager password is submitted
NewForm($f);
?>
<br>
<? 
NewFormItem($f, $s, "ffield", "selectstart", null, null, "");
if(count($ffields)){
		NewFormItem($f, $s, "ffield", "selectoption", "Select f-field", 0);
	foreach($ffields as $fieldnum => $name){
		NewFormItem($f, $s, "ffield", "selectoption", "$fieldnum $name", $fieldnum);
	}
}
NewFormItem($f, $s, "ffield", "selectend");
?>
=&gt;
<? 
NewFormItem($f, $s, "gfield", "selectstart", null, null, "");
		NewFormItem($f, $s, "gfield", "selectoption", "Select g-field", 0);
if(count($ffields)){
	for($i = 1; $i < 10; $i++){
		if(!isset($gfields["g0$i"]))
			NewFormItem($f, $s, "gfield", "selectoption", "g0$i", "g0$i");
	}
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
?>
<script>
