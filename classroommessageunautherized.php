<?
$TITLE = "Classroom Messaging";
$PAGE = "notifications:classroom";
require_once('inc/common.inc.php');
require_once("obj/Schedule.obj.php");
require_once("inc/html.inc.php");
require_once('nav.inc.php');
require_once("inc/classroom.inc.php");
require_once("inc/table.inc.php");


startWindow(_L('Information'));
$schedule = DBFind("Schedule","from job j inner join schedule s on (j.scheduleid = s.id) where j.type = 'alert' and j.status = 'repeating'","s");
?>
<h3> <?=classroomnextavailable($schedule);?></h3>
<?

echo icon_button("Back", "fugue/arrow_180", null, "classroommessageoverview.php",'style="text-align:right"');

endWindow();
require_once('navbottom.inc.php');
?>
