<?
// single address edit panel, from the manual add to list button
include_once("inc/common.inc.php");
include_once("inc/utils.inc.php");
include_once("inc/securityhelper.inc.php");

$ORIGINTYPE = "manualadd";

setCurrentPerson("new");

include("addressedit.inc.php");