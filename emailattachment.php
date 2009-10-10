<? 
include_once("inc/common.inc.php");
include_once("inc/securityhelper.inc.php");
include_once("inc/content.inc.php");
include_once("obj/Content.obj.php");

if ($USER->authorize("sendemail") === false) {
	redirect('./');
}

if(isset($_GET["name"]) && isset($_GET["id"])) {
	if ($c = contentGet($_GET["id"] + 0)){
		list($contenttype,$data) = $c;
		if($data) {
			header("HTTP/1.0 200 OK");
			header('Content-type: ' . $contenttype);
			header("Pragma: private");
			header("Cache-Control: private");
			header("Content-disposition: attachment; filename=\"" . urldecode($_GET["name"]) . "\"");
			header("Content-Length: " . strlen($data));
			header("Connection: close");
			echo $data;
		}
	}
	exit();
}

$filename = '';
$contentid = '';
$size = 0;
//get any uploaded file and put in session queue (use session in case there is a form error)
$uploaderror = false;
$errormessage = '';

$maxattachmentsize = 2 * 1024 * 1024; //2m
$unsafeext = array(".ade",".adp",".asx",".bas",".bat",".chm",".cmd",".com",".cpl",
	".crt",".dbx",".exe",".hlp",".hta",".inf",".ins",".isp",".js",".jse",".lnk",
	".mda",".mdb",".mde",".mdt",".mdw",".mdz",".mht",".msc",".msi",".msp",".mst",
	".nch",".ops",".pcd",".pif",".prf",".reg",".scf",".scr",".sct",".shb",".shs",
	".url",".vb",".vbe",".vbs",".wms",".wsc",".wsf",".wsh",".zip",".dmg",".app");

if (isset($_FILES['emailattachment']['error']) && $_FILES['emailattachment']['error'] != UPLOAD_ERR_OK) {	
	switch($_FILES['emailattachment']['error']) {
	case UPLOAD_ERR_INI_SIZE:
	case UPLOAD_ERR_FORM_SIZE:
		$errormessage .= _L('The file you uploaded exceeds the maximum email attachment limit of 2048K');
		$uploaderror = true;
		break;
	case UPLOAD_ERR_PARTIAL:
		$errormessage .= _L('The file upload did not complete').'\n'._L('Please try again').'\n'._L('If the problem persists').'\n'._L('please check your network settings');
		$uploaderror = true;
		break;
	case UPLOAD_ERR_NO_FILE:
		if (CheckFormSubmit($form,"upload")) {
			$errormessage .= "Please select a file to upload";
			$uploaderror = true;
		}
		break;
	case UPLOAD_ERR_NO_TMP_DIR:
	case UPLOAD_ERR_CANT_WRITE:
	case UPLOAD_ERR_EXTENSION:
		$errormessage .= _L('Unable to complete file upload. Please try again');
		$uploaderror = true;
		break;
	}
} else if(isset($_FILES['emailattachment']) && $_FILES['emailattachment']['tmp_name']) {
	$newname = secure_tmpname("emailattachment",".dat");

	$filename = $_FILES['emailattachment']['name'];
	$size = $_FILES['emailattachment']['size'];
	
	$extdotpos = strrpos($filename,".");
	if ($extdotpos !== false)
		$ext = substr($filename,$extdotpos);

	$mimetype = $_FILES['emailattachment']['type'];
	$uploaderror = true;
	if(!move_uploaded_file($_FILES['emailattachment']['tmp_name'],$newname)) {
		$errormessage .= _L('Unable to complete file upload. Please try again');
	} else if (!is_file($newname) || !is_readable($newname)) {
		$errormessage .= _L('Unable to complete file upload. Please try again');
	} else if ($extdotpos === false) {
		$errormessage .= _L('The file you uploaded does not have a file extension\nPlease make sure the file has the correct extension and try again');
	} else if (array_search(strtolower($ext),$unsafeext) !== false) {
		$errormessage .= _L('The file you uploaded may pose a security risk and is not allowed. ').'\n'._L('Please check the help documentation for more information on safe and unsafe file types');
	} else if ($_FILES['emailattachment']['size'] >= $maxattachmentsize) {
		$errormessage .= _L('The file you uploaded exceeds the maximum email attachment limit of 2048K');
	} else if ($_FILES['emailattachment']['size'] <= 0) {
		$errormessage .= _L('The file you uploaded apears to be empty\nPlease check the file and try again');
	} else {
		$contentid = contentPut($newname,$mimetype);
		if ($contentid) {
			$uploaderror = false;
		} else {
			$errormessage .= _L('Unable to upload email attachment data, either the file was empty or there is a DB problem.');
			$errormessage .= _L('Unable to complete file upload. Please try again');
		}
	}
	@unlink($newname);	
}

?>

<html>
<head>
	<meta http-equiv="Content-type" value="text/html; charset=UTF-8" />
</head>

<body style="margin-left: 0px; margin-top: 1px; margin-bottom: 0px">
<form id="uploadform" action="emailattachment.php" method="post" enctype="multipart/form-data" onsubmit="" >
	<input type="hidden" name="MAX_FILE_SIZE" value="<?= $maxattachmentsize ?>">
	<input id="emailattachment" name="emailattachment" type="file" onChange="window.top.window.startUpload();this.form.submit();"/>	
</form>
<script src="script/prototype.js" type="text/javascript"></script>
<script language="javascript" type="text/javascript">
	window.top.window.stopUpload('<?= $contentid ?>','<?= addslashes($filename) ?>','<?= $size ?>','<?= addslashes($errormessage) ?>');
</script> 
</body>
</html>




	Content-disposition: attachment; filename=foo bar";
