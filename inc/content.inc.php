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
	$c = new Content($cmid);
	if (strlen($c->data) > 0) {
		$contenttype = $c->contenttype;
		if (!$base64)
			$data = base64_decode($c->data);
		else
			$data = $c->data;
		return array($contenttype,$data);
	}

	return false;
}

function contentPut ($filename,$contenttype, $base64 = false) {
	global $SETTINGS;
	$result = false;

	$content = new Content();

	if (!$base64)
		$content->data = base64_encode(file_get_contents($filename));
	else
		$content->data = file_get_contents($filename);

	$content->contenttype = $contenttype;
	if ($content->update()) {
		$result = $content->id;
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

// This function processes $_FILES[$formitemname] and puts a successful file upload into the `content` table, returning an associative array of information upon success. An error message (string) is returned upon failure.
// TODO: make alternative error messages for $foremailattachment.
// Returns array('contentid' => $contentid, 'filename' => $filename, 'sizebytes' => $sizebytes) if successful, otherwise returns $errormessage; the client should check for is_string() and is_array() on the return value.
// Either $unsafeext == null or $allowedext == null; both are arrays of file extensions. If $allowedext is set, only those file extensions are accepted.
// Recommended $maxfilesizebytes is 2048K = 2 * 1024 * 1024.
// $formitemname is also used for secure_tmpname($formitemname, 'dat').
// $foremailattachment is boolean; if false, error messages are more generic. Otherwise the error messages specifically mention "email attachment."
function handleFileUpload($formitemname, $maxfilesizebytes, $unsafeext = null, $allowedext = null, $foremailattachment = false) {
	$errormessage = '';
	$uploaderror = true;
	
	if (isset($_FILES[$formitemname]['error']) && $_FILES[$formitemname]['error'] != UPLOAD_ERR_OK) {
		switch($_FILES[$formitemname]['error']) {
		case UPLOAD_ERR_INI_SIZE:
		case UPLOAD_ERR_FORM_SIZE:
			$errormessage .= $foremailattachment ? _L('The file you uploaded exceeds the maximum email attachment limit of 2048K') : _L('The file you uploaded exceeds the maximum size limit'); // TODO: Should not hard code 2048K for email attachment's error message.
			$uploaderror = true;
			break;
		case UPLOAD_ERR_PARTIAL:
			$errormessage .= _L('The file upload did not complete').' '._L('Please try again').' '._L('If the problem persists').' '._L('please check your network settings');
			$uploaderror = true;
			break;
		case UPLOAD_ERR_NO_FILE:
			if (CheckFormSubmit($form,"upload")) {
				$errormessage .= "Please select a file to upload";
				$uploaderror = true;
			}
			break;
		case UPLOAD_ERR_NO_TMP_DIR:
		case UPLOAD_ERR_CANT_WRITE:
		case UPLOAD_ERR_EXTENSION:
			$errormessage .= _L('Unable to complete file upload. Please try again');
			$uploaderror = true;
			break;
		}
	} else if(isset($_FILES[$formitemname]) && $_FILES[$formitemname]['tmp_name']) {
		$newname = secure_tmpname($formitemname,".dat");

		$filename = $_FILES[$formitemname]['name'];
		$sizebytes = $_FILES[$formitemname]['size'];
		
		$extdotpos = strrpos($filename,".");
		if ($extdotpos !== false)
			$ext = substr($filename,$extdotpos);

		$mimetype = $_FILES[$formitemname]['type'];
		$uploaderror = true;
		if(!move_uploaded_file($_FILES[$formitemname]['tmp_name'],$newname)) {
			$errormessage .= _L('Unable to complete file upload. Please try again');
		} else if (!is_file($newname) || !is_readable($newname)) {
			$errormessage .= _L('Unable to complete file upload. Please try again');
		} else if ($extdotpos === false) {
			$errormessage .= _L('The file you uploaded does not have a file extension. Please make sure the file has the correct extension and try again');
		} else if ((is_array($allowedext) && !in_array($ext, $allowedext)) || (is_array($unsafeext) && array_search(strtolower($ext),$unsafeext) !== false)) {
			$errormessage .= _L('The file you uploaded may pose a security risk and is not allowed. ').' '._L('Please check the help documentation for more information on safe and unsafe file types');
		} else if ($_FILES[$formitemname]['size'] >= $maxfilesizebytes) {
			$errormessage .= _L('The file you uploaded exceeds the maximum email attachment limit of %s.', ($maxfilesizebytes / 1024) . 'K');
		} else if ($_FILES[$formitemname]['size'] <= 0) {
			$errormessage .= _L('The file you uploaded appears to be empty. Please check the file and try again');
		} else {
			$contentid = contentPut($newname,$mimetype);
			if ($contentid) {
				$uploaderror = false;
			} else {
				$errormessage .= _L('Unable to upload email attachment data, either the file was empty or there is a DB problem. ');
				$errormessage .= _L('Unable to complete file upload. Please try again');
			}
		}
		unlink($newname);	
	}
	
	if (!$uploaderror) {
		return array('contentid' => $contentid, 'filename' => $filename, 'sizebytes' => $sizebytes);
	} else {
		return $errormessage;
	}
}

?>