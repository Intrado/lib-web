<?
require_once("inc/common.inc.php");
require_once("inc/securityhelper.inc.php");
?>
<?
////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$PAGE = "notifications:jobs";
$TITLE = "";
$MESSAGESENDER = true;
?>
<html>
<head>
    <meta http-equiv="Content-type" content="text/html; charset=UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <script type="text/javascript" src="script/jquery.1.7.2.min.js"></script>
    <script type="text/javascript">
        jQuery.noConflict();

		// resize the iframe so that the content fits
		jQuery(window).load(function() {
			var height = jQuery(".wrap").height();
			jQuery(window.frameElement).height(height);
		});
    </script>
    <link href="css.php?hash=<?=crc32(serialize($_SESSION['colorscheme']))?>" type="text/css" rel="stylesheet" media="screen, print" />
    <link href="css.forms.php?hash=<?=crc32(serialize($_SESSION['colorscheme']))?>" type="text/css" rel="stylesheet" media="screen, print" />
</head>
<body class="newui" id="ms">

    <div class="wrap">
    <div>
    <div>

<?
include("navbottom.inc.php");
?>