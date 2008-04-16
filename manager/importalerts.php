<?
include_once("common.inc.php");
include_once("../inc/formatters.inc.php");
include_once("../inc/form.inc.php");
include_once("../inc/table.inc.php");
include_once("../inc/html.inc.php");

$clear = false;
if(isset($_GET['cid'])){
	$_SESSION['cid'] = $_GET['cid'] +0;
	$clear = true;
}

if(isset($_GET['importid'])){
	$_SESSION['importid'] = $_GET['importid']+0;
	$clear = true;
}

if($clear){
	redirect();
}

if(isset($_SESSION['importid'])){
	$importid = $_SESSION['importid'];
}

if(isset($_SESSION['cid'])){
	$customerid = $_SESSION['cid'];
}


if(!isset($customerid, $importid)){
	echo "You got here without using the proper URL.  Please return to the imports page and use the Import Alert links";
	exit();
}

// DB Connection
$custinfo = QuickQueryRow("select s.dbhost, s.dbusername, s.dbpassword from shard s left join customer c on (s.id = c.shardid) where c.id = " . $customerid);
$custdb = DBConnect($custinfo[0], $custinfo[1], $custinfo[2], "c_" . $customerid);

$import = QuickQueryRow("select id, name, description, lastrun, updatemethod, datamodifiedtime, alertoptions from import where id = " . $importid, true, $custdb);
if($import['alertoptions']){
	$importalert = sane_parsestr($import['alertoptions']);
} else {
	$importalert = array();
}
//var_dump($importalert);

$f="importalerts";
$s="main";
$reloadform = 0;

if(CheckFormSubmit($f, $s)){
	if(CheckFormInvalid($f)){
		error('Form was edited in another window, reloading data');
		$reloadform = 1;
	} else {
		MergeSectionFormData($f, $s);
		if( CheckFormSection($f, $s) ) {
			error('There was a problem trying to save your changes', 'Please verify that all required field information has been entered properly');
		} else {
			$minsize = ereg_replace("[^0-9]*","",GetFormData($f, $s, "minsize"));
			$maxsize = ereg_replace("[^0-9]*","",GetFormData($f, $s, "maxsize"));
			if($maxsize !== "" && $maxsize < $minsize){
				error("Max size must be greater than min size", "If you don't want a max size, set it to blank");
			} else {
				$importalert['minsize'] = $minsize;
				$importalert['maxsize'] = $maxsize;
				$importalert['daysold'] = GetFormData($f, $s, "daysold");
				$importalerturl = http_build_query($importalert, false, "&");
				QuickUpdate("update import set alertoptions = '" . DBSafe($importalerturl) . "' where id = " . $importid, $custdb);
				redirect("customerimports.php");
			}
		}
	}
} else {
	$reloadform = 1;
}

if($reloadform){
	ClearFormData($f);
	PutFormData($f, $s, "minsize", isset($importalert['minsize']) && $importalert['minsize'] ? number_format($importalert['minsize']) : "", "text");
	PutFormData($f, $s, "maxsize", isset($importalert['maxsize']) && $importalert['maxsize'] ? number_format($importalert['maxsize']) : "", "text");
	PutFormData($f, $s, "daysold", isset($importalert['daysold']) ? $importalert['daysold'] : "", "text");
	PutFormData($f, $s, "managerpassword", "", "text");
	PutFormData($f, $s, "Save", "");
}


include_once("nav.inc.php");
NewForm($f,"onSubmit='if(new getObj(\"managerpassword\").obj.value == \"\"){ window.alert(\"Enter Your Manager Password\"); return false;}'");
?>
<div>Import Alerts for <?=$import['name']?></div>
<table>
	<tr><td>Min Size:</td><td><? NewFormItem($f, $s, "minsize", "text", 10, 20)?></td></tr>
	<tr><td>Max Size:</td><td><? NewFormItem($f, $s, "maxsize", "text", 10, 20)?></td></tr>
	<tr><td>Days Old:</td><td><? NewFormItem($f, $s, "daysold", "text", 10, 20)?></td></tr>
</table>
<div><? NewFormItem($f, $s, "Save", 'submit'); ?></div>
<?
managerPassword($f, $s);
EndForm();
include_once("navbottom.inc.php");
?>