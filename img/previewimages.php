<html>
<head>
<style>

img {
	border: 0px;
	margin: 3px;
}

span {
	float: left;
	position: relative;
	display: block;
	font-size: 6pt;
	width: 22px;
	overflow: auto;
}

.clear {
	clear: both;
	height: 1px;
	overflow: hidden;
}

h1 {
	clear: both;
}

</style>
</head>
<body>


background color:
<select onchange="body.style.backgroundColor=this.value;">
<?

for ($x = 255; $x >= 0; $x -= 8) {
	echo '<option style="background-color: rgb('.$x.','.$x.','.$x.');" value="rgb('.$x.','.$x.','.$x.')">'.$x.'</option>';
}
?>
</select>

<?


$folders = array(
	"largeicons",
	"largeicons/tiny20x20",
	"icons",
	"icons/fugue",
	"icons/diagona/16",
	"icons/diagona/10",
	".",
	"themes/3dblue",
	"themes/cherrybubblegum",
	"themes/chrome",
	"themes/chromeblue",
	"themes/chromeorange",
	"themes/chromepurple",
	"themes/easy_invaders",
	"themes/gold",
	"themes/grass",
	"themes/grass2",
	"themes/greentheme",
	"themes/lemon",
	"themes/marble",
	"themes/min",
	"themes/mintgreen",
	"themes/nature",
	"themes/ocean",
	"themes/redmainblueback",
	"themes/redtheme",
	"themes/rose",
	"themes/rouge",
	"themes/sm",
	"themes/tangerine",
);

?>
<h1>Contents:</h1>
<ul>
<?
foreach ($folders as $folder) {
	echo '<li><a href="#'.$folder.'">'.$folder.'</a></li>';
}
?>
</ul>
<hr></hr>
<?
foreach ($folders as $folder) {
	
	echo '<a name="'.$folder.'"><h1>'.$folder.DIRECTORY_SEPARATOR.'</h1></a><div>';
	
	
	$icons = array();
	$dir = dir($folder);
	while (false !== ($entry = $dir->read())) {
		if (strpos($entry,".") === 0)
			continue;
		
		$ext = strtolower(substr($entry,strlen($entry) - 4,4));
		
		if (!in_array($ext,array(".gif",".png",".jpg")))
			continue;

		if (is_dir($folder.DIRECTORY_SEPARATOR.$entry))
			continue;
			
		$icons[] = $entry;
	}
	
	sort($icons);
	
	$count = 0;
	foreach ($icons as $entry) {
		
		echo '<img Title="'.$entry.'" src="'.$folder.DIRECTORY_SEPARATOR.$entry.'">'; //<span>'.$entry.'</span>
		
		if (++$count % 20 == 0)
			echo '<div class="clear"></div>';
	}
	
	echo '</div>';
}




?>
</body>
</html>
