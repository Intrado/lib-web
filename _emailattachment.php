<? 
include_once("inc/common.inc.php");
include_once("inc/securityhelper.inc.php");
include_once("inc/content.inc.php");
include_once("inc/appserver.inc.php");
include_once("obj/Content.obj.php");

if ($USER->authorize("sendemail") === false) {
	redirect('./');
}

if(isset($_GET["name"]) && isset($_GET["id"])) {
	if (contentAllowed($_GET['id'])) {
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
	redirect('unauthorized.php');
}

//get any uploaded file and put in session queue (use session in case there is a form error)

$maxattachmentsize = 2 * 1024 * 1024; //2m
if (isset($_SESSION['maxattachmentsize'])) {
	$maxattachmentsize = $_SESSION['maxattachmentsize'];
}

$unsafeext = array(".ade",".adp",".asx",".bas",".bat",".chm",".cmd",".com",".cpl",
	".crt",".dbx",".exe",".hlp",".hta",".inf",".ins",".isp",".js",".jse",".lnk",
	".mda",".mdb",".mde",".mdt",".mdw",".mdz",".mht",".msc",".msi",".msp",".mst",
	".nch",".ops",".pcd",".pif",".prf",".reg",".scf",".scr",".sct",".shb",".shs",
	".url",".vb",".vbe",".vbs",".wms",".wsc",".wsf",".wsh",".zip",".dmg",".app");

$result = handleFileUpload('emailattachment', $maxattachmentsize, $unsafeext, null, true);


if (is_array($result)) {
	permitContent($result['contentid']);
	$filename = $result['filename'];
	$contentid = $result['contentid'];
	$size = $result['sizebytes'];
} else if (is_string($result)) {
	$errormessage = $result;
	$filename = '';
	$contentid = '';
	$size = 0;
}
?>

<html>
<head>
	<meta http-equiv="Content-type" content="text/html; charset=UTF-8" />
	<style>
		/* resetting styles */
		html, body, iframe, form {
			margin: 0;
			padding: 0;
			border: 0;
			background: transparent;
			overflow: hidden;
			font-size: 95%;
			vertical-align: baseline;
		}
	</style>
	<script type="text/javascript">
	function load() {
		if (window.top.window.stopUpload != undefined) {
			window.top.window.stopUpload('<?=$contentid?>','<?= addslashes($filename) ?>','<?= $size ?>','<?= isset($errormessage)?addslashes($errormessage):'' ?>', '<?=$_GET['formname']?>', '<?=$_GET['itemname']?>');
		}
	}
	</script>
</head>
<body onload="load()">
<form id="uploadform" action="_emailattachment.php?formname=<?=$_GET['formname']?>&itemname=<?=$_GET['itemname']?>" method="post" enctype="multipart/form-data" onsubmit="" >
	<input type="hidden" name="MAX_FILE_SIZE" value="<?= $maxattachmentsize ?>">
	<input id="emailattachment" name="emailattachment" type="file" onChange="window.top.window.startUpload();this.form.submit();"/>	
</form>
</body>
</html>