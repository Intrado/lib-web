<? 
require_once("inc/common.inc.php");
require_once("inc/securityhelper.inc.php");
require_once("obj/Message.obj.php");
require_once("obj/MessageGroup.obj.php");
require_once("obj/MessagePart.obj.php");
require_once("obj/Language.obj.php");
require_once("obj/FieldMap.obj.php");
require_once("obj/Voice.obj.php");

?>


<style type="text/css">
.watermark {
    color: #D9D9D9;
    font-size: 100pt;
    height: 50%;
    left: -70px;
    margin: 0;
    position: absolute;
    top: 0;
    transform: rotate(-35deg);
    width: 50%;
    z-index: -1;
}
</style>
<div class="watermark">
<p>Preview</p>
</div>

<div>
<?

$layouts = array(
		"onecolumn",
		"twocolumns",
		"threecolumns",
		"1:2:3",
		"2:3:2"
);
// Basic validation 
if (isset($_GET['layout']) && in_array($_GET['layout'], $layouts))
	echo file_get_contents("layouts/{$_GET['layout']}.html");
	
if (isset($_GET['stationery'])) {
	$stationery = new MessageGroup($_GET['stationery']);
	if ($stationery->type == "stationery" &&
		$emailstationery = $stationery->getMessage("email", "html", "en")) {
		
		$emailstationeryparts = DBFindMany("MessagePart", "from messagepart where messageid = ? order by sequence", false, array($emailstationery->id));
			
		echo Message::format($emailstationeryparts);
	}	
		
		
	//echo "Your Stationery Here";
}	
	
?>
</div>