<?
include_once('inc/common.inc.php');
include_once('inc/html.inc.php');
include_once('popup.inc.php');
?>
<?= button("done",$_GET['close'] ? "window.close()" : "window.history.go(-1)"); ?>
<div align="center">

<OBJECT ID="MediaPlayer" WIDTH=320 HEIGHT=42
CLASSID="CLSID:22D6F312-B0F6-11D0-94AB-0080C74C7E95"
STANDBY="Loading Windows Media Player components..."
TYPE="application/x-oleobject">

<PARAM NAME="FileName" VALUE="preview.wav.php/mediaplayer_preview.wav?id=<?= $_GET['id']; ?>">
<param name="controller" value="true">
<EMBED SRC="preview.wav.php/embed_preview.wav?id=<?= $_GET['id']; ?>" AUTOSTART="TRUE"></EMBED>
</OBJECT>


<br><a href="preview.wav.php/download_preview.wav?id=<?= $_GET['id']; ?>&download=true">Click here to download</a>
</div>
<?
include_once('popupbottom.inc.php');
?>
