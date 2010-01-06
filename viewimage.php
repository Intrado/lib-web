<?php
include_once("inc/common.inc.php");
include_once("inc/securityhelper.inc.php");
include_once("inc/content.inc.php");
include_once("obj/Content.obj.php");

if (!isset($_GET['id']))
	exit();

// TODO: Check against $_SESSION['usercontentids'] for security.
$contentid = $_GET['id'] + 0;

if ($content = contentGet($contentid)){
	list($contenttype,$data) = $content;
	
	if($data) {
		// TODO: May need to set last-modified header so that images can be cached. http://php.net/manual/en/function.header.php
		
		header("HTTP/1.0 200 OK");
		header('Content-type: ' . $contenttype);
		header("Pragma: private");
		header("Cache-Control: private");
		//header("Content-disposition: attachment; filename=\"" . $filename . "\"");
		header("Content-Length: " . strlen($data));
		header("Connection: close");
		echo $data;
	}
}


// Keeping a session array of contentids = true, for the messageparts that you are
// editing or viewing.
?>