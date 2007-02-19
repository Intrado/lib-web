<?
// single address edit panel, via manual add from address book
include_once("inc/common.inc.php");
include_once("inc/utils.inc.php");
include_once("inc/securityhelper.inc.php");

$ORIGINTYPE = "manualaddbook";

setCurrentPerson("new");

include("addressedit.inc.php");