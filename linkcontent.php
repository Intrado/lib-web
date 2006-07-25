<?
include_once('inc/common.inc.php');
include_once('obj/Content.obj.php');

if(isset($_GET['id'])) {
	$content = new Content($_GET['id']);
	header('Content-type: ' . $content->contenttype);
	
	$data =  base64_decode($content->data);
	echo $data;
} else {
	echo "error";
}
?>