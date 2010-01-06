<?

include_once("inc/common.inc.php");
include_once("inc/content.inc.php");
include_once("obj/Content.obj.php");

session_write_close();//WARNING: we don't keep a lock on the session file, any changes to session data are ignored past this point

$customer = new Customer($USER->customerid);
if (isset($customer->logocontentid) && 0) {
	list($contenttype,$data) = contentGet($customer->logocontentid);
	header('Content-type: ' . $contenttype);
	echo $data;
} else {
	header('Content-type: image/GIF');
	readfile("img/spacer.gif");
}
?>