<?
require_once("common.inc.php");
require_once("../obj/Person.obj.php");
require_once("../obj/PeopleList.obj.php");
require_once("../obj/Rule.obj.php");
require_once("../obj/ListEntry.obj.php");
require_once("../obj/RenderedList.obj.php");
require_once("../obj/FieldMap.obj.php");
require_once("../obj/Organization.obj.php");
require_once("../obj/Section.obj.php");

if (!$USER->authorize('sendphone')) {
	header("Location: $URL/index.php");
	exit();
}

$list = new PeopleList($_SESSION['newjob']['list']);
$renderedlist = new RenderedList2();
$renderedlist->initWithList($list);


header("Content-type: text/xml");

?>
<CiscoIPPhoneText>
<Title>List Info - <?= htmlentities($list->name) ?></Title>

<Text><?
echo "Name:\t" . $list->name . "\r\n";
echo "Desc:\t" . $list->description . "\r\n";
echo "Total people:\t" . $renderedlist->getTotal() . "\r\n";



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


<? if ($USER->authorize('createlist') && userOwns("list", $list->id)) { ?>
	<SoftKeyItem>
	<Name>Add #</Name>
	<URL><?= htmlentities($URL . "/wiz2c_add.php") ?></URL>
	<Position>2</Position>
	</SoftKeyItem>
<? } ?>

</CiscoIPPhoneText>
