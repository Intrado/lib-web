<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
include_once("inc/common.inc.php");
include_once("inc/securityhelper.inc.php");
include_once("obj/Message.obj.php");
include_once("inc/table.inc.php");
include_once("inc/html.inc.php");
include_once("inc/utils.inc.php");
include_once("inc/form.inc.php");
include_once("obj/Message.obj.php");
include_once("obj/MessagePart.obj.php");
include_once("obj/AudioFile.obj.php");
include_once("obj/Voice.obj.php");
include_once("obj/FieldMap.obj.php");
include_once("inc/content.inc.php");
include_once("obj/Content.obj.php");
include_once("obj/MessageAttachment.obj.php");

////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if ($USER->authorize("sendprint") === false) {
	redirect('./');
}

$MESSAGETYPE = "print";


include("messageedit.inc.php");
