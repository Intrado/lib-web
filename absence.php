<?

include ("inc/common.inc.php");

$count = QuickUpdate("update persondata set f10 = if(round(rand() * 100) <= 4,curdate(),f10)");

echo "Wow there are $count kids absent today!";



?>