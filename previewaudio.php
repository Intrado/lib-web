<?
include_once('inc/common.inc.php');
include_once('inc/html.inc.php');
require_once("inc/table.inc.php");


////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$TITLE = "Audio Preview";

include_once("popup.inc.php");

echo buttons(button("Done",isset($_GET['close']) ? "window.close()" : "window.history.go(-1)"));

startWindow('Audio Preview', 'padding: 3px;');

?>

<div align="center">
	<div id="player"></div>	
	<script type="text/javascript" language="javascript" src="script/niftyplayer.js.php"></script>
	<script language="JavaScript" type="text/javascript">
		embedPlayer("audio.wav.php/mediaplayer_preview.wav?id=<?= $_GET['id']; ?>","player");
	</script>
<br><a href="audio.wav.php/download_preview.wav?id=<? print $_GET['id']; ?>&download=true">Click here to download</a>
</div>
<?

endWindow();

include_once('popupbottom.inc.php');
?>
