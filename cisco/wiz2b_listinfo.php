<?
require_once("common.inc.php");
require_once("../obj/Person.obj.php");
require_once("../obj/PeopleList.obj.php");
include_once("../obj/Rule.obj.php");
include_once("../obj/ListEntry.obj.php");
include_once("../obj/RenderedList.obj.php");
include_once("../obj/FieldMap.obj.php");

if (!$USER->authorize('sendphone')) {
	header("Location: $URL/index.php");
	exit();
}

$list = new PeopleList($_SESSION['newjob']['list']);
$renderedlist = new RenderedList($list);
$renderedlist->calcStats();


header("Content-type: text/xml");

?>
<CiscoIPPhoneText>
<Title>List Info - <?= htmlentities($list->name) ?></Title>

<Text><?
echo "Name:\t" . $list->name . "\r\n";
echo "Desc:\t" . $list->description . "\r\n";

echo "From rules:\t\t" . ($renderedlist->totalrule ? $renderedlist->totalrule : 0) . "\r\n";
echo "From adds:\t\t" . $renderedlist->totaladded . "\r\n";
echo "--------------------\r\n";
echo "Total people:\t" . $renderedlist->total . "\r\n";



?></Text>

<SoftKeyItem>
<Name>Use List</Name>
<URL><?= htmlentities($URL . "/wiz3_message.php") ?></URL>
<Position>1</Position>
</SoftKeyItem>

<SoftKeyItem>
<Name>Back</Name>
<URL><?= htmlentities($URL . "/wiz2_list.php") ?></URL>
<Position>3</Position>
</SoftKeyItem>


<? if ($USER->authorize('createlist')) { ?>
	<SoftKeyItem>
	<Name>Add #</Name>
	<URL><?= htmlentities($URL . "/wiz2c_add.php") ?></URL>
	<Position>2</Position>
	</SoftKeyItem>
<? } ?>

</CiscoIPPhoneText>
