<?

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

//set the charset if we are spitting out html
header('Content-type: text/html; charset=UTF-8') ;
?>
<!DOCTYPE html>
<!--[if lt IE 7]> <html class="no-js ie6 oldie" lang="en"> <![endif]-->
<!--[if IE 7]>    <html class="no-js ie7 oldie" lang="en"> <![endif]-->
<!--[if IE 8]>    <html class="no-js ie8 oldie" lang="en"> <![endif]-->
<!--[if (gte IE 9)|!(IE)]><!--> 
<html class="no-js" lang="en">
<!--<![endif]-->
<head>
	<meta http-equiv="Content-type" content="text/html; charset=UTF-8" />
	<meta http-equiv="X-UA-Compatible" content="IE=EmulateIE7" />
	<title>Manager: <?= preg_replace('/\\<.+>/','',$TITLE) ?></title>

	<script src="script/prototype.js" type="text/javascript"></script> <!-- updated to prototype 1.7 -->
	<script src="script/scriptaculous.js" type="text/javascript"></script>
	<script src="script/prototip.js.php" type="text/javascript"></script>
	<script src="script/utils.js"></script>
	<script src="script/sorttable.js"></script>
	<script src="script/form.js.php" type="text/javascript"></script>
	<script src="script/livepipe/livepipe.js" type="text/javascript"></script>
	<script src="script/livepipe/window.js" type="text/javascript"></script>
	<script src="script/modalwrapper.js" type="text/javascript"></script>
	
	<link href="css.php?hash=<?=crc32(serialize($_SESSION['colorscheme']))?>&newnav=true" type="text/css" rel="stylesheet" media="screen, print" />
	<link href="css.forms.php?hash=<?=crc32(serialize($_SESSION['colorscheme']))?>" type="text/css" rel="stylesheet" media="screen, print" />
	<link href="css/datepicker.css.php" type="text/css" rel="stylesheet" />
	<link href="css/prototip.css.php" type="text/css" rel="stylesheet" />
	<link href="css/style_print.css" type="text/css" rel="stylesheet" media="print" />
	
	<!--[if gte IE 9]>
	  <style type="text/css">
	    .gradient {
	       filter: none;
	    }
	  </style>
	<![endif]-->
</head>
<body>
<!-- ********************************************************************* -->

<div id="top_banner" class="banner cf">
</div><!--  end top_banner -->

<div class="content_wrap cf"><!-- tag ends in footer -->
<div class="container cf">

