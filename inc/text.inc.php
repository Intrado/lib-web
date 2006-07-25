<?

function SmartTruncate ($txt, $max) {
	if (strlen($txt) > $max)
		return substr($txt,0,$max-3) . "...";
	else
		return $txt;
}

?>