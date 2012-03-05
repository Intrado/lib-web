<?

$scheme = getCustomerData($CUSTOMERURL);

if ($scheme == false) {
	$scheme = array("_brandtheme" => "classroom",
					"colors" => array(
						"_brandprimary" => "3e693f",
						"_brandtheme1" => "3e693f",
						"_brandtheme2" => "b47727",
						"_brandratio" => ".2"));
}
$theme = $scheme['_brandtheme'];
$primary = $scheme['colors']['_brandprimary'];
$theme1 = $scheme['colors']['_brandtheme1'];
$theme2 = $scheme['colors']['_brandtheme2'];
$globalratio = $scheme['colors']['_brandratio'];

$topbg = fadecolor($theme2, "FFFFFF", $globalratio/2);

$CustomBrand = isset($scheme['productname']) ? $scheme['productname'] : "";
$custname = isset($scheme['customerName']) ? $scheme['customerName'] : "";

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
	

header('Content-type: text/html; charset=UTF-8') ;


?>
<html>
<head>
	<meta http-equiv="Content-type" value="text/html; charset=UTF-8" />
	<title><?=$CustomBrand?> <?=$TITLE?></title>
	<link href='css/css.inc.php' type='text/css' rel='stylesheet' media='screen'>
	<link href='css/login.css.php' type='text/css' rel='stylesheet' media='screen'>
	
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

</script>
</head>
<body>

<div id="top_banner" class="banner cf">
	<div class="banner_logo"><img src="logo.img.php" /></div>
</div><!-- end top_banner .banner -->


<div class="window cf">

	<div class="window_body_wrap cf">

		<div id="window" class="window_body cf">
			<h3 class="indexdisplayname"><?=escapehtml($custname)?></h3>
		
			  <div><img src="loginpicture.img.php" alt=""></div>
