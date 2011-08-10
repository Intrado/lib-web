<?
require_once("common.inc.php");
require_once("../inc/table.inc.php");
require_once("../inc/form.inc.php");
require_once("../inc/html.inc.php");
require_once("../inc/utils.inc.php");
require_once("../obj/Phone.obj.php");
require_once("../inc/themes.inc.php");
require_once("../obj/Language.obj.php");


if (isset($_GET['id'])) {
	$_SESSION['customerid']= $_GET['id']+0;	
	redirect();	
}

if (!$MANAGERUSER->authorized("editcustomer")) {
	unset($_SESSION['customerid']);
	exit("Not Authorized");
}

$customerid = null;
if (isset($_SESSION['customerid'])) {
	$customerid = $_SESSION['customerid'];
	$query = "select s.dbhost, c.dbusername, c.dbpassword, c.urlcomponent, c.enabled, c.oem, c.oemid, c.nsid, c.notes from customer c inner join shard s on (c.shardid = s.id) where c.id = '$customerid'";
	$custinfo = QuickQueryRow($query,true);
	$custdb = DBConnect($custinfo["dbhost"], $custinfo["dbusername"], $custinfo["dbpassword"], "c_$customerid");
	if (!$custdb) {
		exit("Connection failed for customer: {$custinfo["dbhost"]}, db: c_$customerid");
	}
}

if (!$customerid) {
	exit("Connection failed for customer");
}

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////
if(isset($_POST['submit'])) {
	$fileerror = false;
	$logoname = "";
	$loginpicturename ="";
	if(isset($_FILES['uploadlogo']) && $_FILES['uploadlogo']['tmp_name']) {

		$logoname = secure_tmpname("uploadlogo",".img");
		if(!move_uploaded_file($_FILES['uploadlogo']['tmp_name'],$logoname)) {
			$fileerror=true;
		} else if (!is_file($logoname) || !is_readable($logoname)) {
			$fileerror=true;
		}
	}
	if(isset($_FILES['uploadloginpicture']) && $_FILES['uploadloginpicture']['tmp_name']) {

		$loginpicturename = secure_tmpname("uploadloginpicture",".img");
		if(!move_uploaded_file($_FILES['uploadloginpicture']['tmp_name'],$loginpicturename)) {
			$fileerror=true;
		} else if (!is_file($loginpicturename) || !is_readable($loginpicturename)) {
			$fileerror=true;
		}
	}
	$subscriberloginpicturename = "";
	if (isset($_FILES['uploadsubscriberloginpicture']) && $_FILES['uploadsubscriberloginpicture']['tmp_name']) {

		$subscriberloginpicturename = secure_tmpname("uploadsubscriberloginpicture",".img");
		if (!move_uploaded_file($_FILES['uploadsubscriberloginpicture']['tmp_name'], $subscriberloginpicturename)) {
			$fileerror = true;
		} else if (!is_file($subscriberloginpicturename) || !is_readable($subscriberloginpicturename)) {
			$fileerror = true;
		}
	}

	if($fileerror){
		error('Unable to complete file upload. Please try again');
	} else {
		//Logo
		if($logoname){
			$newlogofile = file_get_contents($logoname);
			if($newlogofile){
				QuickUpdate("INSERT INTO content (contenttype, data) values
							('" . $_FILES['uploadlogo']['type'] . "', '" . base64_encode($newlogofile) . "')", $custdb);
				$logocontentid = $custdb->lastInsertId();
				setCustomerSystemSetting('_logocontentid', $logocontentid, $custdb);
			}
		}

		// Login image
		if($loginpicturename){
			$newloginpicturefile = file_get_contents($loginpicturename);
			if($newloginpicturefile){
				QuickUpdate("INSERT INTO content (contenttype, data) values
							('" . $_FILES['uploadloginpicture']['type'] . "', '" . base64_encode($newloginpicturefile) . "')", $custdb);
				$loginpicturecontentid = $custdb->lastInsertId();
				setCustomerSystemSetting('_loginpicturecontentid', $loginpicturecontentid, $custdb);
			}
		}

		// Subscriber Login image
		if ($subscriberloginpicturename) {
			$newsubscriberloginpicturefile = file_get_contents($subscriberloginpicturename);
			if($newsubscriberloginpicturefile){
				QuickUpdate("INSERT INTO content (contenttype, data) values
							('" . $_FILES['uploadsubscriberloginpicture']['type'] . "', '" . base64_encode($newsubscriberloginpicturefile) . "')", $custdb);
				$subscriberloginpicturecontentid = $custdb->lastInsertId();
				setCustomerSystemSetting('_subscriberloginpicturecontentid', $subscriberloginpicturecontentid, $custdb);
			}
		}

		if($_POST['submit'] == "done"){
			redirect("customers.php");
		} else {
			redirect(); //the annoying custinfo above needs to be reloaded
		}
	}
}


////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

include_once("nav.inc.php");
startWindow(_L('Upload Customer Images'));
?>
<form name="fileupload" method="post" action="<?= $_SERVER["REQUEST_URI"]?>" enctype="multipart/form-data">
<table>
<tr>
	<td>Logo:</td>
	<td><img src='customerlogo.img.php?id=<?=$customerid?>'></td>
</tr>
<tr>
	<td>New Logo:</td>
	<td><input type='file' name='uploadlogo' size='30'></td>
</tr>

<tr>
	<td>Login Picture:</td>
	<td><img width="100px" src='customerloginpicture.img.php?id=<?=$customerid?>'></td>
</tr>
<tr>
	<td>New Login Picture:</td>
	<td><input type='file' name='uploadloginpicture' size='30'></td>
</tr>

<tr>
	<td>Subscriber Login Picture:</td>
	<td><img width="100px" src='customerloginpicture.img.php?subscriber&id=<?=$customerid?>'></td>
</tr>
<tr>
	<td>New Subscriber Login Picture:</td>
	<td><input type='file' name='uploadsubscriberloginpicture' size='30'></td>
</tr>
<tr>
	<td><?=submit_button(_L("Save"),"save","tick") . submit_button(_L("Save and Return"),"done","tick") ?></td>
</tr>

</table>
</form>
<?
endWindow();

include_once("navbottom.inc.php");
?>
