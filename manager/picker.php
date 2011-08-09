<!--
Title: Tigra Color Picker
URL: http://www.softcomplex.com/products/tigra_color_picker/
Version: 1.1
Date: 06/26/2003 (mm/dd/yyyy)
Note: Permission given to use this script in ANY kind of applications if
   header lines are left unchanged.
Note: Script consists of two files: picker.js and picker.html
-->


<?
function RGBdec2hex($r, $g, $b){
	$rgb = dechex(($r << 16) + ($g << 8) + $b);
	while(strlen($rgb) < 6){
		$rgb = '0' . $rgb;
	}
	return $rgb;
}


function TCBuildCell ($red, $green, $blue, $width, $height) {
?>
	<td style="border:0px;" bgcolor="#<?=RGBdec2hex($red, $green, $blue)?>">
		<a style="border: 0px;" href="javascript:P.S('<?=RGBdec2hex($red, $green, $blue)?>')" onmouseover="P.P('<?=RGBdec2hex($red, $green, $blue)?>')">
			<img style="border: 0px;" src="mimg/pixel.gif" width="<?=$width?>" height="<?=$height?>" border="0">
		</a>
	</td>
<?
}


function BuildCells(){
	$s = "";
	$max = 15;
	for($colorstep=17; $colorstep > 0; $colorstep-=2){
		if($colorstep < 2){
			$colorstep = 0;
		}
?>
		<tr>
<?
		$r=$max;
		$g=0;
		$b=0;
		do{
			$s .= TCBuildCell($r*$colorstep, $g*$colorstep, $b*$colorstep, 6, 6);
			if($g!=$max && $r==$max && $b==0){
				$g++;
			} else if($g==$max && $r>0){
				$r--;
			} else if($r==0 && $g==$max && $b!=$max){
				$b++;
			} else if($b==$max && $g>0){
				$g--;
			} else if($b==$max && $g==0 && $r!=$max){
				$r++;
			} else if($r==$max && $g==0){
				$b--;
			}
		} while(!($r==$max && $b==1));
		// Draw one more time for the end case;
		$s = $s . TCBuildCell($r*$colorstep, $g*$colorstep, $b*$colorstep, 6, 6);

?>
		</tr>
<?
	}
}
?>

<html>
<head>
	<title>Color Picker</title>
	<style>
		.bd { border : 0px; }
		.s  { width:181 }
	</style>
</head>
<body leftmargin="5" topmargin="5" marginheight="5" marginwidth="5">
<div>Hover over a color to preview it.  Click the color you want as your primary color.</div>
<table cellpadding="0" cellspacing="0" border="0" width="184">
	<tr>
		<td align="center">
			<div id=sam name=sam><table cellpadding=0 cellspacing=0 border=0 width=181 align=center ><tr><td align=center height=18><div id="samp"><font face=Tahoma size=2>sample <font color=white>sample</font></font></div></td></tr></table></div>
			<div id="p" name="p"><table cellpadding=0 cellspacing=0 border=0 align=center><?=BuildCells();?></table></div>
		</td>
	</tr>
</table>

<script language="JavaScript">
	var P = opener.TCP;
	P.doc = document;
	P.win = window;
	P.sample = document.getElementById('sam').style;
	P.divs[0] = document.getElementById('p').style
	if (!document.layers && document.body.innerHTML)
		P.o_samp = document.getElementById('samp');
	if (P.field.value) P.P(P.field.value, true);

</script>


</body>
</html>
