<?

$CONTENT_URLS = array("get" => array(array("localhost","80","/foobar/xxx-get.php")),
					"put" => array(array("localhost","80","/foobar/xxx-put.php")),
					"tts" => array(array("localhost","8080","/phone/Tts"))
				);

function connectToContentServer($type) {
	global $CONTENT_URLS;
	foreach ($CONTENT_URLS[$type] as $server) {
		list($ip,$port,$path) = $server;
		if ($fp = fsockopen($ip,$port,$errno,$errstr,0.5))
			return array($fp,$server);
	}
}

function getHttpResponseContents ($fp) {
	$data = fscanf($fp,"HTTP/%f %s %s");
	if ($data[1] != "200")
		return false;

	while (!feof($fp)) {
		$data = stream_get_line($fp,8192,"\r\n");
		if ($data == "")
			break;

		$header = explode(":",$data);
		if (stripos($header[0],"Content-Type") !== false)
			$contenttype = trim($header[1]);
	}
	if ($data = stream_get_contents($fp))
		return array($contenttype,$data);
	else
		return false;
}

function contentGet ($cmid) {
	global $CONTENT_URLS;
	list($fp,$server) = connectToContentServer("get");
	list($host,$port,$path) = $server;
	if ($fp) {
		$req = "GET " . $path . "?cmid=$cmid" . " HTTP/1.0\r\nConnection: close\r\n\r\n";
		if (fwrite($fp,$req))
			$data = getHttpResponseContents($fp);
			fclose($fp);
			return $data;
	}
	return false;
}

function contentPut ($filename,$contenttype) {
	global $CONTENT_URLS;
	$result = false;

	if (is_file($filename) && is_readable($filename) && ($filesize = filesize($filename)) > 0) {
		if ($fp_file = fopen($filename,"r")) {

			list($fpc,$server) = connectToContentServer("put");
			list($host,$port,$path) = $server;
			if ($fpc) {
				$req = "POST " . $path . " HTTP/1.0\r\nContent-Length: $filesize\r\nContent-Type: $contenttype\r\nConnection: close\r\n\r\n";
				fwrite($fpc,$req);
				while ($data = fread($fp_file,8192)) {
					fwrite($fpc,$data);
				}

				$result = getHttpResponseContents($fpc);
				$result = (int)$result[1];
				fclose($fpc);
			}
			fclose($fp_file);
		}
	}
	return $result;
}

function renderTts ($text,$language,$gender) {
	global $CONTENT_URLS;
	list($fp,$server) = connectToContentServer("tts");
	list($host,$port,$path) = $server;
	if ($fp) {
		$req = "POST " . $path. "?language=" . urlencode($language)
				. "&gender=" . urlencode($gender) . " HTTP/1.0\r\nContent-Length: " . strlen($text) . "\r\nConnection: close\r\n\r\n" . $text;
		if (fwrite($fp,$req)) {
			$data = getHttpResponseContents($fp);
			fclose($fp);
			return $data;
		}
	}
	return false;
}


?>