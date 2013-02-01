<? 
require_once("inc/common.inc.php");
require_once("inc/securityhelper.inc.php");
require_once("obj/Message.obj.php");
require_once("obj/MessageGroup.obj.php");
require_once("obj/MessagePart.obj.php");
require_once("obj/Language.obj.php");
require_once("obj/FieldMap.obj.php");
require_once("obj/Voice.obj.php");
require_once("layouts/layouts.inc.php");

$isPreview = isset($_GET["preview"]);

if ($isPreview) {
?>
	<style type="text/css">
	.watermark {
	    color: #a6a6a6;
	    font-size: 100pt;
	    height: 50%;
	    left: -70px;
	    margin: 0;
	    position: absolute;
	    top: 0;
	    transform: rotate(-35deg);
	    width: 50%;
	    z-index: 1;
	    zoom: 1;
		filter: alpha(opacity=50);
		opacity: 0.5;
	}
	div.editableBlock {
		border: none;
		padding: 1px;
		background-color: #FFFF99;
		border: 1px dashed #999999;
		padding: 0px;
	}
	</style>
	<div class="watermark">
	<p>Preview</p>
	</div>
	
	<div>
<?
}

// Basic validation 
if (isset($_GET['layout']) && in_array($_GET['layout'], array_keys($layouts)))
	echo file_get_contents("layouts/{$_GET['layout']}.html");
	
if (isset($_GET['stationery'])) {
	if (!userCanSee("messagegroup", $_GET['stationery']))
		exit();
	
	$stationery = new MessageGroup($_GET['stationery']);
	if ($stationery->type == "stationery" &&
		$emailstationery = $stationery->getMessage("email", "html", "en")) {
		
		$emailstationeryparts = DBFindMany("MessagePart", "from messagepart where messageid = ? order by sequence", false, array($emailstationery->id));
			
		echo Message::format($emailstationeryparts);
	}	
		
		
	//echo "Your Stationery Here";
}	

if ($isPreview) {
?>
</div>
<? 
}
?>