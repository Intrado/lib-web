<?php 
error_reporting(E_ALL);
ini_set('display_errors', '1');

include_once("inc/common.inc.php");

$PAGE = "start:start";
$TITLE = "Welcome";

include("nav.inc.php");

include("dashboard/index.php");

include("navbottom.inc.php"); ?>