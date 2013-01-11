<?
$PAGETITLE = preg_replace('/\\<.+>/','',$TITLE);

$SESSION_WARNING_TIME = isset($SETTINGS['feature']['session_warning_time']) ?
	$SETTINGS['feature']['session_warning_time']*1000 : 1200000;

function setBodyClass () {
	$theme = $_SESSION['colorscheme']['_brandtheme'];
	echo 'class="' . $theme . '"';
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
//set the charset if we are spitting out html
header('Content-type: text/html; charset=UTF-8') ;
?>
<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-type" content="text/html; charset=UTF-8" />
	<meta http-equiv="X-UA-Compatible" content="IE=edge" />

	<title><?= getBrand();?>: <?= $PAGETITLE ?></title>

	<script src="script/prototype.js" type="text/javascript"></script> <!-- updated to prototype 1.7 -->
	<script src="script/scriptaculous.js" type="text/javascript"></script>
	<script src="script/prototip.js.php" type="text/javascript"></script>
	<script src="script/utils.js"></script>
	<script src="script/sorttable.js"></script>
	<script src="script/form.js.php" type="text/javascript"></script>
	<script src="script/livepipe/livepipe.js" type="text/javascript"></script>
	<script src="script/livepipe/window.js" type="text/javascript"></script>
	<script src="script/modalwrapper.js" type="text/javascript"></script>

	<link href="css.php?hash=<?=crc32(serialize($_SESSION['colorscheme']))?>" type="text/css" rel="stylesheet" media="screen, print" />
	<link href="css.forms.php?hash=<?=crc32(serialize($_SESSION['colorscheme']))?>" type="text/css" rel="stylesheet" media="screen, print" />
	<link href="css/datepicker.css.php" type="text/css" rel="stylesheet" />
	<link href="css/newui_datepicker.css" type="text/css" rel="stylesheet" />
	<link href="css/prototip.css.php" type="text/css" rel="stylesheet" />
	<link href="css/style_print.css" type="text/css" rel="stylesheet" media="print" />

	<!--[if IE 8]>
	<script src="script/respond.min.js" type="text/javascript"></script>
	<![endif]-->

</head>

<!--[if IE 7]>    <body class="<?=getBrandTheme();?> ie7" <?= isset($MESSAGESENDER) && $MESSAGESENDER == true?' id="ms"':''?>> <![endif]-->
<!--[if IE 8]>    <body class="<?=getBrandTheme();?> ie8" <?= isset($MESSAGESENDER) && $MESSAGESENDER == true?' id="ms"':''?>> <![endif]-->
<!--[if gt IE 8]><!--> <body <?=setBodyClass();?> <?= isset($MESSAGESENDER) && $MESSAGESENDER == true?' id="ms"':''?> ><!--<![endif]-->
<script>
	var _brandtheme = "<?=getBrandTheme();?>";

	<?
		if (isset($SETTINGS['googleanalytics']['trackingid'])) {
	?>
	var _gaq = _gaq || [];
	_gaq.push(['_setAccount', '<?= $SETTINGS['googleanalytics']['trackingid'] ?>']);
	_gaq.push(['_setCookiePath', '/<?= $CUSTOMERURL ?>/']);
	_gaq.push(['_setCustomVar', 1, 'cust', '<?= $CUSTOMERURL ?>', 2]);
	_gaq.push(['_setSiteSpeedSampleRate', <?= $SETTINGS['googleanalytics']['samplerate'] ?>]);
	_gaq.push(['_trackPageview']);
	(function() {
		var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
		ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
		var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
	})();
	<?
	}
	?>
</script>

<!-- ********************************************************************* -->

<div class="wrap"><!-- ends in nonavbottom.inc -->

	<script type="text/javascript">
		Event.observe(window, 'load', function() {
			sessionKeepAliveWarning(<?=$SESSION_WARNING_TIME?>);
		});
	</script>

	<div class="content_wrap cf"><!-- tag ends in footer -->

		<div class="container cf">
