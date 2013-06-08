<?
header('Content-type: text/html; charset=UTF-8') ;

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<meta http-equiv="Content-type" content="text/html; charset=UTF-8" />
	<meta http-equiv="X-UA-Compatible" content="IE=edge" />

	<script type="text/javascript" src="script/jquery.1.7.2.min.js"></script>
<?if (!isset($NOPROTOTYPE) || !$NOPROTOTYPE) {?>
	<script type="text/javascript">
		jQuery.noConflict();
	</script>
	<script src="script/prototype.js" type="text/javascript"></script> <!-- updated to prototype 1.7 -->
	<script src="script/scriptaculous.js" type="text/javascript"></script>
	<script src="script/utils.js"></script>
	<script type="text/javascript" src="script/datepicker.js"></script>
	<link href="css/datepicker.css.php" type="text/css" rel="stylesheet" />
	<link href="css/newui_datepicker.css" type="text/css" rel="stylesheet" />
	<script src="script/modalwrapper.js" type="text/javascript"></script>
	<script src="script/livepipe/livepipe.js" type="text/javascript"></script>
	<script src="script/livepipe/window.js" type="text/javascript"></script>
	<script src="script/prototip.js.php" type="text/javascript"></script>
	<link href="css/prototip.css.php" type="text/css" rel="stylesheet" />
	<script src="script/form.js.php" type="text/javascript"></script>
<?}?>
	<script src="script/sorttable.js"></script>
	<script src="script/bootstrap-modal.js" type="text/javascript"></script>
	<script src="script/session_warning.js" type="text/javascript"></script>

	<link href="css.php?hash=<?=crc32(serialize($_SESSION['colorscheme']))?>" type="text/css" rel="stylesheet" media="screen, print" />
	<link href="css.forms.php?hash=<?=crc32(serialize($_SESSION['colorscheme']))?>" type="text/css" rel="stylesheet" media="screen, print" />
	<link href="css/style_print.css" type="text/css" rel="stylesheet" media="print" />
	
	<!--[if IE 8]>
		<script src="script/respond.min.js" type="text/javascript"></script>
	<![endif]-->

	<script type="text/javascript">
		sessionKeepAliveWarning(<?=$SESSION_WARNING_TIME?>);
	</script>
	
</head>


<body style="margin: 0px; background: white;" onBeforeUnLoad="if(typeof(unloadsession) != 'undefined') {unloadsession();}">
	<script>
		var _brandtheme = "<?=getBrandTheme();?>";
	</script>
<? 
//load validator for rules, handle rule add/delete to form submit (contactsearchformdata.inc.php)
?>

<div class="content_wrap iframe_content_wrap cf">
<div class="modal hide fade default-modal" id="defaultmodal">
	<div class="modal-header">
		<button class="close" aria-hidden="true" data-dismiss="modal" type="button">x</button>
		<h3></h3>
	</div>
	<div class="modal-body"></div>
</div>
	<div class="content">