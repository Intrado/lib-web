<?

function connectToContentServer($type) {
	global $SETTINGS;

	$serversstr = explode(";",$SETTINGS['content'][$type]);

	foreach($serversstr as $str) {
		list($ip,$port,$path) = $server = explode(",",$str);
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
		return array((isset($contenttype) ? $contenttype : NULL),$data);
	else
		return false;
}

function contentGet ($cmid, $base64 = false) {
	global $SETTINGS;
	if (!$SETTINGS['content']['externalcontent']) {
		$c = new Content($cmid);
		if (strlen($c->data) > 0) {
			$contenttype = $c->contenttype;
			if (!$base64)
				$data = base64_decode($c->data);
			else
				$data = $c->data;
			return array($contenttype,$data);
		}

	} else {
		list($fp,$server) = connectToContentServer("get");
		list($host,$port,$path) = $server;
		if ($fp) {
			$req = "GET " . $path . "?cmid=$cmid" . " HTTP/1.0\r\nConnection: close\r\n\r\n";
			if (fwrite($fp,$req))
				$data = getHttpResponseContents($fp);
				fclose($fp);
				if (!$base64)
					return $data;
				else
					return base64_encode($data);
		}
	}
	return false;
}

function contentPut ($filename,$contenttype, $base64 = false) {
	global $SETTINGS;
	$result = false;

	if (!$SETTINGS['content']['externalcontent']) {
		$content = new Content();

		if (!$base64)
			$content->data = base64_encode(file_get_contents($filename));
		else
			$content->data = file_get_contents($filename);

		$content->contenttype = $contenttype;
		if ($content->update()) {
			$result = $content->id;
		}
	} else {
		if (is_file($filename) && is_readable($filename) && ($filesize = filesize($filename)) > 0) {
			if ($fp_file = fopen($filename,"r")) {

				list($fpc,$server) = connectToContentServer("put");
				list($host,$port,$path) = $server;
				if ($fpc) {
					$req = "POST " . $path . " HTTP/1.0\r\nContent-Length: $filesize\r\nContent-Type: $contenttype\r\nConnection: close\r\n\r\n";
					fwrite($fpc,$req);

					if (!$base64)
						$data = file_get_contents($filename);
					else
						$data = base64_decode(file_get_contents($filename));

					fwrite($fpc,$data);

					$result = getHttpResponseContents($fpc);
					$result = (int)$result[1];
					fclose($fpc);
				}
				fclose($fp_file);
			}
		}
	}
	return $result;
}

function renderTts ($text,$language,$gender) {
	list($fp,$server) = connectToContentServer("tts");
	list($host,$port,$path) = $server;
	if ($fp) {
		// tts priority 0=normal, 1=high
		$req = "POST " . $path. "?language=" . urlencode($language)
				. "&gender=" . urlencode($gender) . "&priority=1"
				. " HTTP/1.0\r\nContent-Type: text/plain; charset=utf-8\r\nContent-Length: " . strlen($text) . "\r\nConnection: close\r\n\r\n" . $text;
		if (fwrite($fp,$req)) {
			$data = getHttpResponseContents($fp);
			fclose($fp);
			return $data;
		}
	}
	return false;
}


?>