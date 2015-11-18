<?

$scheme = getCustomerData($CUSTOMERURL);

$topbg = fadecolor("3399ff", "FFFFFF", .2/2);

$CustomBrand = isset($scheme['productname']) ? $scheme['productname'] : "";
$custname = isset($scheme['customerName']) ? $scheme['customerName'] : "";
$samlEnabled = $scheme['_hasSAML'];
$forceLocal = isset($_GET['forceLocal']);
if($_SERVER['REQUEST_METHOD'] === 'GET' && $samlEnabled && !$forceLocal && !isset($_GET['f'])) {
	redirect("samllogin.php");
}

$samlURL = isset($scheme['_samlIdPEntityId']) ? $scheme['_samlIdPEntityId'] : "";
$samlIdPMetadataURL = isset($scheme['_samlIdPMetadataURL']) ? $scheme['_samlIdPMetadataURL']: "";

//Takes 2 hex color strings and 1 ratio to apply to to the primary:original
function fadecolor($primary, $fade, $ratio){
	$primaryarray = array(substr($primary, 0, 2), substr($primary, 2, 2), substr($primary, 4, 2));
	$fadearray = array(substr($fade, 0, 2), substr($fade, 2, 2), substr($fade, 4, 2));
	$newcolorarray = array();
	for($i = 0; $i<3; $i++){
		$newcolorarray[$i] = dechex(round(hexdec($primaryarray[$i]) * $ratio + hexdec($fadearray[$i])*(1-$ratio)));
	}
	$newcolor = "#" . implode("", $newcolorarray);
	return $newcolor;
}

if (!isset($scheme['_supportemail']))
	$scheme['_supportemail'] = "support@schoolmessenger.com";

if (!isset($scheme['_supportphone']))
	$scheme['_supportphone'] = "8009203897";


?>
<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-type" content="text/html; charset=UTF-8" />

	<title><?=$CustomBrand?> <?=$TITLE?></title>
	<link href='css/login.css' rel='stylesheet'>

	<!-- iOS Webpage Icons for Web Clip -->
	<link rel="apple-touch-icon" href="img/ios/apple-touch-icon-57x57.png" />
	<link rel="apple-touch-icon" sizes="72x72" href="img/ios/apple-touch-icon-72x72.png" />
	<link rel="apple-touch-icon" sizes="114x114" href="img/ios/apple-touch-icon-114x114.png" />
	<link rel="apple-touch-icon" sizes="144x144" href="img/ios/apple-touch-icon-144x144.png" />
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css">
	<script type="text/javascript">

function capslockCheck(e){
	var keypressed;
	var shiftkey;

	if(e.keyCode)
		keypressed = e.keyCode;
	else
		keypressed = e.which;

	if(e.shiftKey) {
		shiftkey = true;
	} else {
		if(keypressed == 16) {
			shiftkey = true;
		} else {
			shiftkey = false;
		}
	}
	if(((keypressed >= 65 && keypressed <= 90) && !shiftkey) || ((keypressed >= 97 && keypressed <= 122) && shiftkey)){
		new getObj('capslockwarning').style.display = 'block';
	} else {
		new getObj('capslockwarning').style.display = 'none';
	}
}

function getObj(name)
{
  if (document.getElementById)
  {
	this.obj = document.getElementById(name);
  }
  else if (document.all)
  {
	this.obj = document.all[name];
  }
  else if (document.layers)
  {
	this.obj = document.layers[name];
  }
  if(this.obj)
	this.style = this.obj.style;
}

<?
	if (isset($SETTINGS['googleanalytics']['trackingid'])) {
?>
		var _gaq = _gaq || [];
		_gaq.push(['_setAccount', '<?= $SETTINGS['googleanalytics']['trackingid'] ?>']);
		_gaq.push(['_setCookiePath', '/<?= $CUSTOMERURL ?>/']);
		_gaq.push(['_setCustomVar', 1, 'cust', '<?= $CUSTOMERURL ?>', 2]);
		_gaq.push(['_setSiteSpeedSampleRate', 100]);
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
</head>
<body>

<div id="top_banner" class="banner cf">
	<div class="banner_logo"><img src="logo.img.php" alt="School Messenger"/></div>
</div><!-- end top_banner .banner -->


<div class="window cf">

	<div class="window_body_wrap cf">

		<div id="window" class="window_body cf">
			<h3 class="indexdisplayname"><?=escapehtml($custname)?></h3>

			  <div><img src="loginpicture.img.php" alt=""></div>
