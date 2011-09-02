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
	
	$imagesettings = array(
		"uploadlogo" => "_logocontentid",
		"uploadloginpicture" => "_loginpicturecontentid",
		"uploadsubscriberloginpicture" => "_subscriberloginpicturecontentid"
	);
	$imagetypes = array("image/jpeg","image/gif","image/png"); // list of allowed image types
	$imagenames = array(); // store temp file names
	
	
	// Get image contents and check for errors
	foreach($imagesettings as $imagekey => $setting) {
		if(!$fileerror && isset($_FILES[$imagekey]) && $_FILES[$imagekey]['tmp_name']) {
			if (!in_array($_FILES[$imagekey]['type'], $imagetypes)) {
				$fileerror=true;
				error("Unknown file type " . $_FILES[$imagekey]['type'] . ". Accepted types: jpg, gif and png");
				break;
			}
			
			$imagename = secure_tmpname($imagekey,".img");
			if(!move_uploaded_file($_FILES[$imagekey]['tmp_name'],$imagename)) {
				error('Unable to complete file upload. Please try again');
				$fileerror=true;
				break;
			} else if (!is_file($imagename) || !is_readable($imagename)) {
				error('Unable to complete file upload. Please try again');
				$fileerror=true;
				break;
			}
			$imagenames[$imagekey] = $imagename;
		}
	}

	
	// Insert content id and associate with image settings
	if (!$fileerror) {
		foreach($imagenames as $imagekey => $imagename) {
			$file = file_get_contents($imagename);
			if($file){
				QuickUpdate("INSERT INTO content (contenttype, data) values	(?,?)",
					 $custdb,array($_FILES[$imagekey]['type'],base64_encode($file)));
				$contentid = $custdb->lastInsertId();
				setCustomerSystemSetting($imagesettings[$imagekey], $contentid, $custdb);
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

$custurl = QuickQueryRow("select urlcomponent from customer where id = ?", false,false, array($customerid));	
startWindow(_L('Upload Customer Images: %s',escapehtml($custurl)));
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
</table>
<? buttons(submit_button(_L("Save"),"save","tick"),submit_button(_L("Save and Return"),"done","tick"),icon_button(_L("Cancel"),"cross",false,"customers.php")) ?>
</form>
<?
endWindow();

include_once("navbottom.inc.php");
?>
