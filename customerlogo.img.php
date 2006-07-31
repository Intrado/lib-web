<?

include_once("inc/common.inc.php");
include_once("inc/content.inc.php");
include_once("obj/Customer.obj.php");
include_once("obj/Content.obj.php");

$customer = new Customer($USER->customerid);
if (isset($customer->logocontentid) && 0) {
	if ($IS_COMMSUITE) {

		$c = new Content($customer->logocontentid);
		header('Content-type: ' . $c->contenttype);
		$data = base64_decode($c->data);
		$size = strlen($data);
		echo $data;
	} else {
		list($contenttype,$data) = contentGet($customer->logocontentid);
		header('Content-type: ' . $contenttype);
		echo $data;
	}
} else {
	header('Content-type: image/GIF');
	readfile("img/spacer.gif");
}
?>