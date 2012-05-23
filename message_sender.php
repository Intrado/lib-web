<?php 
error_reporting(E_ALL);
ini_set('display_errors', '1');

include_once("inc/common.inc.php");

$PAGE = "notifications:lists";
$TITLE = "List Builder";

// Moved this include into message_sender/index.php 
//include("nav.inc.php");

include("message_sender/index.php");

include("navbottom.inc.php"); ?>