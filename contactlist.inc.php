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

$data = $renderedlist->getPage($pagestart, $renderedlist->pagelimit, true);
$titles = array(
				"2" => "ID#",
				"3" => "First Name",
				"4" => "Last Name",
				"5" => "Language",
				"6" => "Primary Phone",
				"7" => "Primary Email",
				"8" => "Address");

$formatters = array(
					"6" => "fmt_phone",
					"7" => "fmt_email",
					"8" => "fmt_null");

// Append the flex field values to the list
$extraFields = FieldMap::getAuthorizedMapNames();
unset($extraFields[FieldMap::getFirstNameField()]); // Remove first name since we already get that specifically in the query
unset($extraFields[FieldMap::getLastNameField()]); // Remove last name since we already get that specifically in the query
unset($extraFields[FieldMap::getLanguageField()]); // Remove last name since we already get that specifically in the query

$counter = 6; // Start at 5 since we are starting at the 5th value in the query result
foreach ($extraFields as $field => $name) {
		unset($extraFields[$field]);
		$extraFields["$counter"] = $name; // Change the index from field name 'f05' to a number like 8
		$counter++;
}
$index = 9; // Start after last index in $titles
foreach ($extraFields as $field) {
	$titles["$index"] = $field;
	$index++;
}


if ($showpagemenu) {
	showPageMenu($renderedlist->total,$renderedlist->pageoffset,$renderedlist->pagelimit);
}
echo "\n";
echo '<table width="100%" cellpadding="3" cellspacing="1" class="list">';
showTable($data, $titles,$formatters);
echo "\n</table>";
if ($showpagemenu) {
	showPageMenu($renderedlist->total,$renderedlist->pageoffset,$renderedlist->pagelimit);
}


?>











