<?
include_once("inc/common.inc.php");
include_once("inc/formatters.inc.php");


?>
<script langauge="javascript">

function dolistbox (img, type, init, id) {
	if (!img.toggleset) {
		img.toggleset = true;
		img.toggle = init;
	}
	img.toggle = !img.toggle;
	img.src = "checkbox.png.php?type=" + type + "&toggle=" + img.toggle + "&id=" + id + "&foo=" + new Date();
}
</script>
<?

if (!$showpagemenu)
	$renderedlist->pagelimit = -1;
$pagestart = (isset($_GET['pagestart']) ? $_GET['pagestart'] + 0 : 0);

$data = $renderedlist->getPage($pagestart, $renderedlist->pagelimit);

if ($showpagemenu)
	showPageMenu($renderedlist->total,$renderedlist->pageoffset,$renderedlist->pagelimit);


if (isset($doscrolling) && $doscrolling && count($data) > 8) {
	echo '<div class="scrollTableContainer">';
}

echo '<table width="100%" cellpadding="3" cellspacing="1" class="list">';

$titles = array("0" => "In List",
				"2" => "ID#",
				"3" => "First Name",
				"4" => "Last Name",
				"5" => "Language");
$formatters = array("0" => "fmt_checkbox",
					"2" => "fmt_idmagnify",
					"6" => "fmt_phone",
					"7" => "fmt_email",
					"8" => "fmt_phone",
					"9" => "fmt_null");

if($USER->authorize('sendphone')){
	$titles[6] = "Phone 1";
	if($phonelabel = fetch_labels("phone", 0)){
		$titles[6] .= "(" . $phonelabel . ")";
	}
}
if($USER->authorize('sendemail')){
	$titles[7] = "Email 1";
	if($emaillabel = fetch_labels("email", 0)){
		$titles[7] .= "(" . $emaillabel . ")";
	}
}
if(getSystemSetting("_hassms") && $USER->authorize('sendsms')){
	$titles[8] = "SMS 1";
	if($smslabel = fetch_labels("sms", 0)){
		$titles[8] .= "(" . $smslabel . ")";
	}
}
$titles["9"] = "Address";


showTable($data, $titles,$formatters);

echo "</table>";

if (isset($doscrolling) && $doscrolling && count($data) > 8) {
	echo '</div>';
}

if ($showpagemenu)
	showPageMenu($renderedlist->total,$renderedlist->pageoffset,$renderedlist->pagelimit);


?>