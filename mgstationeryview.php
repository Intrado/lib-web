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
require_once("obj/Job.obj.php");

$isPreview = isset($_GET["preview"]);

if ($isPreview) {
?>
<!DOCTYPE html>
<html>
    <script type="text/javascript" src="script/jquery-1.8.3.min.js"></script>
    <script type="text/javascript">
   		$().ready(function() {
   			if (typeof(parent.messagePrevewLoaded) != 'undefined') 
   	   			parent.messagePrevewLoaded($('body'))
    	});
	</script>
	<style type="text/css">
	.watermark {
	    color: #a6a6a6;
	    font-size: 100pt;
	    height: 50%;
	    left: 10px;
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
	
	.overlay {
		position: fixed;
		height: 100%;
		width: 100%;
		z-index: 1;
		background:url("img/previewoverlay.png");
	}
	
	
	</style>
	<body>
	
	<div id="overlay" class="overlay">
	<p></p>
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
			
		$stationeryBody = Message::format($emailstationeryparts);
		$stationeryBody = str_replace('<<', '&lt;&lt;', $stationeryBody);
		$stationeryBody = str_replace('>>', '&gt;&gt;', $stationeryBody);

		echo $stationeryBody;
	}	
		
		
	//echo "Your Stationery Here";
}	

if ($isPreview) {
?>
	</div>
	</body>
</html>
<? 
}
?>
